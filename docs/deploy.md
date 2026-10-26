# Kiadás és üzemeltetés

Kiegészíti a `docs/uzemeltetes.md`-t. Itt a konkrét lépések és konfigurációs pontok vannak.

## Környezetek

| Környezet | Indexelés | Levelek | Védelem |
|---|---|---|---|
| local | noindex | Mailpit vagy log | – |
| staging | noindex | valódi címekre; a `MEGIS_MAIL_ALLOWLIST` megadásával csak az ott felsoroltakra | `MEGIS_STAGING_USER`/`MEGIS_STAGING_PASSWORD` és/vagy `MEGIS_STAGING_ALLOWED_IPS` |
| production | csak a címlap | valódi címekre | – |

A noindex-et az `X-Robots-Tag` fejléc, a `robots.txt` és a lapok meta-tagje adja. Csak a production indexelhető, ott is csak a címlap: a `robots.txt` a `/`-t és a `/build/` alatti CSS-t, JS-t engedi, minden mást tilt. A sitemap is csak a címlapot sorolja fel.

## Konfigurációs pontok

| Csoport | Változók |
|---|---|
| Alkalmazás | `APP_ENV`, `APP_KEY`, `APP_DEBUG` (élesben `false`), `APP_URL` (élesben https), `APP_TIMEZONE` |
| Adatbázis | `DB_CONNECTION=pgsql`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`, `DB_SSLMODE=require` (RDS) |
| Redis | `REDIS_HOST`, `REDIS_PASSWORD`, `REDIS_PORT`, `REDIS_DB` (session és queue), `REDIS_CACHE_DB` (cache) |
| Drivers | `SESSION_DRIVER=redis`, `SESSION_LIFETIME=43200`, `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis` |
| Fájltár | `MATERIALS_DISK_DRIVER=s3`, `MATERIALS_BUCKET`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_DEFAULT_REGION=eu-central-1` |
| Levél | `MAIL_MAILER`, `MAIL_*`, `MAIL_FROM_ADDRESS` |
| Turnstile | `TURNSTILE_SITE_KEY`, `TURNSTILE_SECRET_KEY` (élesben nem lehet a tesztkulcs) |
| mégis | `MEGIS_SUPPORT_EMAIL`, `MEGIS_BOOKING_URL`, `MEGIS_ALERT_RECIPIENTS`, `MEGIS_HEALTH_TOKEN`, `MEGIS_HEALTH_MIN_FREE_DISK_MB`, `MEGIS_MAIL_ALLOWLIST`, `MEGIS_STAGING_*` |
| Napló | `LOG_CHANNEL=stack`, `LOG_STACK=app`, `LOG_LEVEL`, `LOG_DAILY_DAYS` (helyi rotáció) |
| AI (16) | `AI_DEFAULT_PROVIDER`, `AI_DEFAULT_MODEL`, `AI_SMALL_MODEL`, `AI_TRANSCRIPTION_PROVIDER`, `AI_TRANSCRIPTION_MODEL`; kulcsok: `ANTHROPIC_API_KEY`, `OPENAI_API_KEY` (`OPENAI_URL` alapból az EU-s `https://eu.api.openai.com/v1`), `GEMINI_API_KEY`; Claude EU-ban: Amazon Bedrock, `AWS_BEDROCK_REGION=eu-central-1` |
| Értéktükör, anyagok (06) | `MEGIS_TRANSCRIBER=ai` (élesben kötelező), `FFPROBE_PATH`, `MEGIS_INDIVIDUAL_PRODUCT` (a regisztrált magánszemély szervezetének alap terméke), `MEGIS_BOOKING_URL` (az „Időpontot foglalok” naptára), `LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=materials` (a nagy feltöltések közvetlenül az S3-ba mennek) |
| Naptár (09) | `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `GOOGLE_REDIRECT_URI` (alap: `/naptar/google/visszahivas`); `MICROSOFT_CLIENT_ID`, `MICROSOFT_CLIENT_SECRET`, `MICROSOFT_REDIRECT_URI` (alap: `/naptar/microsoft/visszahivas`), `MICROSOFT_TENANT=common`. Kulcsok nélkül csak iCal link, ICS fájl és beszélgetés választható. |
| Queue | `REDIS_QUEUE_RETRY_AFTER` / `DB_QUEUE_RETRY_AFTER` (alap: 330 másodperc, az AI-jobok 300 másodperces időkorlátja fölött) |
| Diagnózis (18) | `DIAGNOSIS_RECALCULATION_DELAY` (alap: 20 másodperc): ennyit vár a fő diagnózis újraszámolása, így az egymás után érkező anyagok és megélések egyszer számolódnak |
| PDF (28) | `LARAVEL_PDF_DRIVER=gotenberg`, `GOTENBERG_URL` (a Gotenberg konténer címe, például `http://gotenberg:3000`); Docker nélküli környezetben `LARAVEL_PDF_DRIVER=weasyprint` és `LARAVEL_PDF_WEASYPRINT_BINARY` (a bináris útvonala, például `/usr/local/bin/weasyprint`; nem lehet üres) |

A titkok csak környezeti változóban vannak. AWS-en javasolt az AWS Secrets Manager vagy az SSM Parameter Store.

### Infrastruktúra-beállítások

- **Redis:** a session és a queue adatbázisa kiürítés nélkül fusson (`maxmemory-policy noeviction`). A cache külön adatbázisban van (`REDIS_CACHE_DB`).
- **Fájltár (S3, EU-régió):** privát bucket, nyilvános hozzáférés nélkül, verziózás bekapcsolva. A régi verziókra 30 napos életciklus-szabály kell.
- **Napló:** a `storage/logs/{app,auth,ai,mail,queue}-*.log` fájlok naponta rotálódnak. A központi log-tárba csatornánként kell szállítani őket, 365 napos megőrzéssel (AWS esetén CloudWatch Logs).
- **ffprobe:** az ffmpeg csomag része, a szerveren telepítve kell lennie. A böngészős webm-felvételekben nincs hossz, ezt méri.
- **Feltöltés:** a Livewire ideiglenes feltöltései S3-on közvetlenül a bucketbe mennek (aláírt URL), így a 300 MB-os hangfájlra a PHP és az nginx feltöltési korlátja nem vonatkozik. Helyi disknél az `upload_max_filesize` és a `post_max_size` legyen legalább 300M.
- **Naptár-alkalmazások:** a Google-alkalmazás (Calendar API, `calendar.events.readonly`) és a Microsoft Entra multi-tenant alkalmazás (`Calendars.Read`, `offline_access`) a mégis céges fiókján. A visszahívási cím: `{APP_URL}/naptar/google/visszahivas`, illetve `{APP_URL}/naptar/microsoft/visszahivas`. A Google-ellenőrzésig legfeljebb 100 tesztfelhasználó köthet össze.
- **Levélküldő:** a megis.hu domainen SPF, DKIM és DMARC kell. AWS esetén SES (eu-central-1), bounce- és complaint-értesítéssel.
- **PDF (28 · 28.3):** a riport és az Útravaló PDF-je a Gotenberg konténerben (Chromium) készül, a `spatie/laravel-pdf` Gotenberg-driverével. A konténer csak belső hálózaton legyen elérhető: `docker run -d --restart unless-stopped -p 127.0.0.1:3000:3000 gotenberg/gotenberg:8`. A setup check jelzi, ha nem válaszol. Docker nélküli környezetben (például a stagingen) a WeasyPrint az alternatíva, a `spatie/laravel-pdf` WeasyPrint-driverével (`pontedilana/php-weasyprint`): `LARAVEL_PDF_DRIVER=weasyprint`. Kell hozzá a `weasyprint` bináris (legalább 60-as, mert a driver átadja a `--timeout` kapcsolót; a staging a 70-est futtatja), és a Geologica fontot rendszerfontként kell telepíteni (például `/usr/local/share/fonts` alá, utána `fc-cache -f`; ellenőrzés: `fc-list | grep Geologica`), mert a PDF nem tölt be webfontot. A setup check a bináris `--version` hívásával ellenőrzi, hogy fut-e. Egy PDF legfeljebb 10 másodpercig készülhet (a `php-weasyprint` rögzített korlátja).
- **Képességtár (07 · 7.6):** a mégis tárát az adminban kell betölteni: *Képességtár → Frissítés a mégis tárából*, a „Képességrendszer · tárak” Sheet xlsx-exportjával. A fájl nem kerül a kódtárba és a szerverre sem: a betöltés után törlődik.
- **Uptime-figyelés:** UptimeRobot vagy Better Stack hívja percenként a `GET /health`-et. Hiba esetén e-mailt küld a riasztási listára.

## Kiadás lépései

### Előtte (NFR · 10)

- [ ] Verzió emelve a `config/megis.php`-ban.
- [ ] `docs/changelog.md` frissítve: verzió, commit-hash, dátum.
- [ ] A migrációk átnézve. Van-e visszafordíthatatlan lépés?
- [ ] Tesztek zöldek: `php artisan test --compact`.
- [ ] A rollback-verzió azonosítva (az előző release commit-hash-e).

### Telepítés

A szerveren, a projekt gyökeréből:

```bash
./update.sh
```

A skript karbantartó módban lehúzza a kódot (`git pull --ff-only`), majd lefuttatja a lenti lépéseket. A `composer install`, az `npm ci` és a Vite build csak akkor fut, ha a bemenetük változott. Beírja a `.env`-be a `MEGIS_DEPLOYED_AT`-ot (Rendszer → Health), kikapcsolja a karbantartó módot, és a végén lefuttatja a setup checket és a `/health`-et. Ha az ellenőrzés hibát jelez, a kilépési kód nem nulla. Kapcsolók a fájl fejlécében: `FULL=1` (minden lépés), `FULL_CACHE_CLEAR=1`, `SKIP_CHECKS=1`, `SITE_URL`, `PHP_FPM_SERVICE`.

Az alkalmazás-cache-t a skript nem üríti: abban van a worker és az ütemező heartbeatje.

Kézzel ugyanez:

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize          # config, route, view és event cache
php artisan queue:restart
```

### Utána

- [ ] `php artisan megis:setup:check`, hiba nélkül.
- [ ] `GET /health` → `ok`. Részletek: `X-Health-Token` fejléc.
- [ ] A kritikus oldalak kézi ellenőrzése.
- [ ] A napló és a hibaszint ellenőrzése. Ugyanaz a hiba 15 percen belül csak egyszer küld riasztást.

### Élesítés előtt (1.00)

A kód kész, az élesítéshez ezek kellenek a környezetben és a mégistől:

- [ ] **Staging-elfogadás a mégissel** a demó szervezeten (`DemoDiagnosisSeeder`): olvasat → publikálás → riport → visszajelzés → PDF → Útravaló.
- [ ] **Próba-visszaállítás** a stagingre a „Mentés és próba-visszaállítás” szerint, az eredmény beírva.
- [ ] **Levélküldő:** SES (eu-central-1) vagy más EU-s küldő, SPF, DKIM és DMARC a megis.hu-n. A visszapattanó levelek webhookja (02) a küldő kiválasztása után készül el; addig a „nem kézbesíthető” jelzést semmi nem állítja be.
- [ ] **Infrastruktúra:** Redis `noeviction`, S3 verziózással és 30 napos életciklussal, RDS-mentés 30 napra, Gotenberg konténer belső hálózaton (Docker nélkül WeasyPrint a Geologica fonttal), ffprobe, CloudWatch Logs 365 nappal, uptime-figyelés a `/health`-re.
- [ ] **Titkok és kulcsok:** `APP_DEBUG=false`, https-es `APP_URL`, éles Turnstile-kulcsok, EU-s AI-kulcsok, `MEGIS_TRANSCRIBER=ai` a 12.7 PoC döntése szerint, `MEGIS_HEALTH_TOKEN`.
- [ ] **Naptár-alkalmazások:** a Google-ellenőrzés lezárva (addig 100 tesztfelhasználó), Microsoft Entra alkalmazás.
- [ ] **Tartalom:** az első szuperadmin (`megis:admin:create`), a három jogi PDF, az 58 érték (`megis:values:import "docs/data/ÉRTÉKEK KITÖLTÖTT.xlsx"`), a Képességtár betöltése, a területek szövegei, a támogató kérdések, a Bölcsességtár szövegeinek átnézése, a foglalási link és a támogatási e-mail.
- [ ] **Nyitott kérdések a mégisnél:** a T1 és a V9 szövegének bővítése a szervezet vezetőivel (15 · 5.), a foglalási szolgáltatás webhookja (15 · 9.).

## Rollback

1. Az előző release kódjának visszaállítása az azonosított commit-hash-re, majd a „Telepítés” lépései, migráció nélkül.
2. Adatbázis:
   - Ha a kiadás migrációja csak bővített (új tábla vagy oszlop), az előző kód vele is fut, nem kell visszagörgetni.
   - Ha a migráció adatot alakított át, akkor `php artisan migrate:rollback --step=N` csak akkor, ha a `down()` lépés adatvesztés nélküli. Különben visszaállítás a kiadás előtti mentésből.
3. `php artisan optimize` és `php artisan queue:restart`.
4. Setup check és `/health`.

## Mentés és próba-visszaállítás

- **Adatbázis:** napi automatikus mentés, 30 napos megőrzéssel (AWS esetén az RDS automatikus mentése).
- **Fájltár:** S3-verziózás, 30 nap után törlődnek a régi verziók.
- **Negyedéves próba-visszaállítás a stagingre:**
  1. A legfrissebb mentés visszaállítása egy új staging-adatbázisba.
  2. A staging `DB_*` átállítása rá, majd `php artisan optimize`.
  3. Setup check, és néhány ismert rekord ellenőrzése.
  4. Az eredmény ide kerül:

| Dátum | Mentés | Eredmény | Ki |
|---|---|---|---|
| – | – | – | – |

## Háttérfolyamatok

- Ütemező: `* * * * * php artisan schedule:run`, cronból vagy supervisorból. A feladatok listája: `docs/cron.md`.
- Queue workerek supervisorral, újraindítással, queue-nként külön, hogy a hosszú AI-hívások ne tartsák fel a leveleket:
  - `php artisan queue:work --queue=mail,default --tries=3`
  - `php artisan queue:work --queue=ai --timeout=300` (16 · 16.1: 3 próba, növekvő várakozással; a jobok maguk állítják)
  - a `transcription` queue workere a 12.7 PoC után jön.
- A queue kapcsolat `retry_after` értéke (330 s) legyen nagyobb bármely worker `--timeout` értékénél, különben egy hosszú job kétszer futhat.
- A health check figyeli a worker és az ütemező heartbeatjét. 5 perc csend esetén `fail`.
