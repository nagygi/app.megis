#!/usr/bin/env bash
#
# update.sh — éles / staging frissítés (mégis)
#
# A legfrissebb kód lehúzása a gitből, függőségek telepítése, frontend build,
# adatbázis-migráció, a fordított cache újraépítése, a queue workerek
# újraindítása, végül a setup check és a /health ellenőrzése.
# A lépések a docs/deploy.md „Kiadás lépései” fejezetét követik.
#
# Használat (a szerveren, a projekt gyökeréből):
#   ./update.sh
# vagy:
#   bash update.sh
#
# Környezeti kapcsolók:
#   FULL=1               minden lépés lefut akkor is, ha a bemenete nem változott
#                        (composer install, npm ci, Vite build)
#   FULL_CACHE_CLEAR=1   a teljes alkalmazás-cache ürítése (optimize:clear). Eldobja
#                        a worker és az ütemező heartbeatjét és a riasztások
#                        deduplikálását is, ezért a /health a következő heartbeatig
#                        fail-t adhat. Csak akkor kell, ha tényleg mindent nulláról.
#   SKIP_CHECKS=1        a setup check és a /health ellenőrzés kihagyása a végén
#   SITE_URL=<url>       a /health címe, ha a szerverről nem az APP_URL-en érhető el
#   PHP_FPM_SERVICE=<n>  a php-fpm systemd szolgáltatás neve
#                        (alapból: php8.4-fpm, utána php-fpm)
#
set -euo pipefail

# --- A projekt gyökere = a skript könyvtára (bárhonnan futtatható) ---
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

PROJECT="$(basename "$SCRIPT_DIR")"           # pl. app.megis.team
PHP="$(command -v php)"

CURRENT_STEP=""
STEP_START=0
CHECKS_FAILED=0

# Lezárja az előző fázis időmérőjét. Így a deploy végén látszik, melyik lépés
# mennyi ideig tartott.
end_step() {
  if [ -n "$CURRENT_STEP" ]; then
    printf '    \033[2m└ %s: %ds\033[0m\n' "$CURRENT_STEP" "$((SECONDS - STEP_START))"
    CURRENT_STEP=""
  fi
}

log() {
  end_step
  CURRENT_STEP="$1"
  STEP_START=$SECONDS
  printf '\n\033[1;34m==> %s\033[0m\n' "$1"
}

warn() { printf '\033[1;33m  ! %s\033[0m\n' "$1"; }

# Igaz, ha a `git pull` során a megadott útvonalak bármelyike változott.
# FULL=1 esetén mindig igaz, így egy kézi teljes újratelepítés is kikényszeríthető.
changed() {
  if [ "${FULL:-0}" = "1" ]; then
    return 0
  fi
  if [ "$BEFORE" = "$AFTER" ]; then
    return 1
  fi
  [ -n "$(git diff --name-only "$BEFORE" "$AFTER" -- "$@")" ]
}

# Igaz, ha a vendor-ban dev csomagok is vannak: valaki kézzel futtatott egy
# `composer install`-t vagy `composer update`-et --no-dev nélkül. Ilyenkor a
# `dump-autoload --no-dev` kihagyja őket az osztálytérképből, a Laravel
# csomagfelderítése viszont az installed.json alapján betöltené a providerüket
# (Laravel\Boost\BoostServiceProvider), és az artisan el sem indul.
vendor_has_dev() {
  "$PHP" -r 'exit((json_decode((string) @file_get_contents("vendor/composer/installed.json"), true)["dev"] ?? false) ? 0 : 1);'
}

# A teljes törzs egy függvényben van, a hívása a fájl legvégén. Így a bash a
# futtatás megkezdése előtt beolvassa és értelmezi az egész fájlt, és a `git pull`
# nem tudja a saját lába alól kirántani a szőnyeget azzal, hogy menet közben
# átírja ezt az update.sh-t.
main() {
  # --- Előfeltételek ---
  if [ ! -f artisan ]; then
    echo "HIBA: nem Laravel projekt gyökér (nincs ./artisan): $SCRIPT_DIR" >&2
    exit 1
  fi
  if [ ! -f .env ]; then
    echo "HIBA: nincs .env — az első telepítés a docs/install.md szerint megy, nem ezzel." >&2
    exit 1
  fi
  # A csomagok a gitben lévő composer.lock szerint települnek. Egy kézi
  # `composer update` a szerveren átírja: a git pull elakadhat rajta, és a
  # szerveren más csomagverziók futnak, mint amit a tesztek lefedtek.
  if [ -n "$(git status --porcelain -- composer.json composer.lock)" ]; then
    echo "HIBA: a composer.json / composer.lock helyben módosult (kézi composer update?). A gitben lévő változat: git checkout -- composer.json composer.lock" >&2
    exit 1
  fi

  log "[$PROJECT] Karbantartó mód BE"
  # Karbantartás alatt a /health is 503-at ad: ha a deploy egy percnél tovább tart,
  # az uptime-figyelő riaszthat.
  "$PHP" artisan down || true
  # Hiba esetén is hozzuk vissza az oldalt
  trap '"$PHP" artisan up || true' EXIT

  log "Kód frissítése a gitből"
  BRANCH="$(git rev-parse --abbrev-ref HEAD)"
  BEFORE="$(git rev-parse HEAD)"
  git fetch --prune origin
  git pull --ff-only origin "$BRANCH"
  AFTER="$(git rev-parse HEAD)"
  echo "  branch: $BRANCH @ $(git rev-parse --short HEAD)"
  if [ "$BEFORE" = "$AFTER" ]; then
    echo "  (nem jött új commit)"
  fi

  log "PHP függőségek (éles, dev nélkül)"
  if [ ! -d vendor ] || changed composer.lock composer.json; then
    composer install --no-dev --optimize-autoloader --no-interaction
  elif vendor_has_dev; then
    warn "A vendor-ban dev csomagok is vannak (kézi composer install/update) — eltávolítom őket."
    composer install --no-dev --optimize-autoloader --no-interaction
  else
    # A lockfile áll, tehát a csomagokat nem kell újratelepíteni — de jöhettek új
    # osztályok a saját kódban, ezért az osztálytérképet újraépítjük.
    echo "  composer.lock változatlan — csak osztálytérkép"
    composer dump-autoload --no-dev --optimize --no-interaction
  fi

  log "Frontend függőségek"
  if [ ! -d node_modules ] || changed package-lock.json package.json; then
    # --ignore-scripts: ahogy a `composer setup` is; a buildhez egyik csomagnak sem
    # kell telepítési szkript (a natív binárisok opcionális csomagként jönnek).
    npm ci --ignore-scripts --no-audit --no-fund
  else
    # Az `npm ci` törli és nulláról újratelepíti a node_modules-t; változatlan
    # lockfile mellett ez a deploy legnagyobb fölösleges tétele.
    echo "  package-lock.json változatlan — kihagyva"
  fi

  log "Frontend build (Vite)"
  # A Tailwind 4 a teljes projektet átnézi osztálynevekért (a .gitignore-ban
  # szereplőket kivéve), és a vendor lapozó nézeteit is (@source). Ezért nem csak a
  # resources/ változása számít. A build a Geologica fontot a Bunny Fonts-ról tölti
  # le, ehhez kimenő HTTPS kell.
  if [ ! -f public/build/manifest.json ] \
    || changed resources app lang config routes composer.lock package.json package-lock.json vite.config.js; then
    npm run build
  else
    echo "  nem változott frontend forrás — kihagyva"
  fi

  log "Adatbázis-migráció"
  "$PHP" artisan migrate --force

  log "Storage link"
  "$PHP" artisan storage:link || true

  log "Fordított cache újraépítése"
  # A deploy ideje a Rendszer → Health lapon látszik (config/megis.php: deployed_at).
  # A config:cache előtt kell beírni, különben a régi érték marad a cache-ben.
  set_deployed_at

  # Az alkalmazás-cache-t (Redis, REDIS_CACHE_DB) NEM ürítjük: ebben van a queue
  # worker és az ütemező heartbeatje, és a hibariasztások 15 perces deduplikálása.
  # Ürítés után a /health a következő heartbeatig fail-t adna. A kódfrissítéshez a
  # fordított cache elég: az `optimize` a config, event, route és view cache-t
  # építi újra, és mindegyik előbb a saját régi párját törli.
  if [ "${FULL_CACHE_CLEAR:-0}" = "1" ]; then
    warn "FULL_CACHE_CLEAR=1 — teljes cache-ürítés (a heartbeatek és a riasztás-dedup is elvész)."
    "$PHP" artisan optimize:clear
  fi
  "$PHP" artisan optimize

  log "Queue workerek újraindítása"
  # A workereket a supervisor futtatja, queue-nként külön (docs/deploy.md ·
  # Háttérfolyamatok). A jelzés után mindegyik befejezi az aktuális jobját — egy
  # AI-job akár 300 másodpercig is tart —, kilép, és a supervisor az új kóddal
  # indítja újra.
  "$PHP" artisan queue:restart

  # php-fpm opcache ürítés (ha van rá jog)
  reload_php_fpm

  log "Karbantartó mód KI"
  "$PHP" artisan up
  trap - EXIT

  # --- Innentől az oldal ÉL: ami itt fut, az már nem állásidő. ---

  # Az új modellek ára rögtön, ne csak a 03:45-ös ütemezett futáskor (16 · 16.4).
  # A setup check előtt fut, hogy ne jelezzen hiányzó árat; hiba esetén a deploy megy tovább.
  log "AI-egységárak frissítése"
  if ! "$PHP" artisan megis:prices:sync --backfill; then
    warn "Az AI-árak frissítése nem sikerült, a régi árak maradnak. Később: php artisan megis:prices:sync --backfill"
  fi

  if [ "${SKIP_CHECKS:-0}" != "1" ]; then
    log "Ellenőrzés: setup check"
    if ! "$PHP" artisan megis:setup:check; then
      CHECKS_FAILED=1
      # Karbantartás alatt sem a worker, sem az ütemező nem fut, így a heartbeat
      # öt perc után elavulttá válik. Az `up` után egy percen belül helyreáll.
      warn "A setup check hibát jelzett. Ha csak a worker vagy az ütemező heartbeatje, egy perc múlva futtasd újra: php artisan megis:setup:check"
    fi

    log "Ellenőrzés: /health"
    check_health || CHECKS_FAILED=1
  fi

  end_step
  if [ "$CHECKS_FAILED" = "1" ]; then
    printf '\n\033[1;33m==> [%s] Frissítés kész, de az ellenőrzés hibát jelzett  (%s, összesen: %ds)\033[0m\n' \
      "$PROJECT" "$(git rev-parse --short HEAD)" "$SECONDS"
    exit 1
  fi
  printf '\n\033[1;34m==> [%s] Frissítés kész ✔  (%s, összesen: %ds)\033[0m\n' \
    "$PROJECT" "$(git rev-parse --short HEAD)" "$SECONDS"
}

# MEGIS_DEPLOYED_AT="2026-10-18 21:45 (abc1234)" a .env-be. A régi sort kiveszi,
# az újat a végére teszi. A fájlt a helyén írja felül (cat >), így a tulajdonosa
# és a jogai megmaradnak.
set_deployed_at() {
  local now value tmp
  # A PHP saját időzóna-adatbázisát használja; a `TZ=… date` tzdata nélkül
  # csendben UTC-t adna.
  now="$("$PHP" -r 'echo (new DateTime("now", new DateTimeZone("Europe/Budapest")))->format("Y-m-d H:i");')"
  value="$now ($(git rev-parse --short HEAD))"
  tmp="$(mktemp)"

  grep -v '^MEGIS_DEPLOYED_AT=' .env > "$tmp" || true
  # Ha a .env utolsó sora nem sortöréssel végződik, az új sor hozzáragadna.
  if [ -s "$tmp" ] && [ -n "$(tail -c 1 "$tmp")" ]; then
    echo >> "$tmp"
  fi
  printf 'MEGIS_DEPLOYED_AT="%s"\n' "$value" >> "$tmp"

  cat "$tmp" > .env
  rm -f "$tmp"
  echo "  MEGIS_DEPLOYED_AT=\"$value\""
}

reload_php_fpm() {
  local service
  for service in ${PHP_FPM_SERVICE:-php8.4-fpm php-fpm}; do
    if systemctl reload "$service" 2>/dev/null; then
      echo "  reloaded: $service"
      return 0
    fi
  done
  warn "php-fpm reload kihagyva (nincs jog, nem fut, vagy más a neve: PHP_FPM_SERVICE=…)."
}

# A /health a web csoporton kívül van (session és staging-védelem nélkül), így a
# szerverről is hívható. Kívülről csak „ok” vagy „fail” látszik; a részletek a
# setup check kimenetében vannak.
check_health() {
  local url body

  url="${SITE_URL:-$(sed -n 's/^APP_URL=//p' .env | tail -n 1 | tr -d "\"'\r")}"
  url="${url%/}"
  if [ -z "$url" ]; then
    warn "Nincs APP_URL a .env-ben — /health ellenőrzés kihagyva (SITE_URL=…)."
    return 0
  fi

  if body="$(curl -fsS --max-time 15 "$url/health")"; then
    echo "  $url/health → $body"
  else
    warn "$url/health nem ok (fail vagy nem érhető el a szerverről; SITE_URL=… felülírja a címet)."
    return 1
  fi
}

main "$@"
