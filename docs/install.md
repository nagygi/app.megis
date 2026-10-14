# Telepítés helyben

## Szükséges verziók

| Eszköz | Verzió | Megjegyzés |
|---|---|---|
| PHP | 8.4.1 vagy újabb | Kiterjesztések: `pdo_pgsql`, `pdo_sqlite`, `intl`, `redis`, `mbstring`, `fileinfo`, `zip`, `gd`. A Laravel Herd PHP 8.4-e mindet tartalmazza. A herd-lite 8.4.0 nem elég. |
| Composer | 2.8 | |
| Node.js | 22 | |
| PostgreSQL | 16 vagy újabb | Közös környezetben AWS RDS. Helyben sqlite is elég, amíg nincs RDS-hozzáférés. |
| Redis | 7 | Közös környezetben a cache, a session és a queue ezen fut. Helyben elhagyható. |

## Első indítás

```bash
composer install
cp .env.example .env
php artisan key:generate
```

A `.env`-ben állítsd be:

- az adatbázist (`DB_*`). Helyben sqlite-hoz: `DB_CONNECTION=sqlite`, és töröld a többi `DB_*` sort;
- Redis nélkül: `SESSION_DRIVER=database`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`;
- a levélküldőt (`MAIL_*`). Helyben Mailpit (`MAIL_HOST=127.0.0.1`, `MAIL_PORT=1025`) vagy `MAIL_MAILER=log`;
- a Turnstile tesztkulcsait, ezek mindig átengednek:
  - `TURNSTILE_SITE_KEY=1x00000000000000000000AA`
  - `TURNSTILE_SECRET_KEY=1x0000000000000000000000000000000AA`

Utána:

```bash
php artisan migrate --seed
npm install
npm run build
```

A seeder a három terméket hozza létre (Snapshot · Külső, Snapshot · Belső, Teljes). Többször is futtatható.

## Az első admin

```bash
php artisan megis:admin:create
```

- A parancs bekéri a nevet, az e-mail címet és kétszer a jelszót (rejtve, legalább 12 karakter, nem szerepelhet ismert adatszivárgásban). Az első admin alapból szuperadmin.
- `--role=admin`: sima admint hoz létre.
- `--invite`: jelszó helyett meghívó e-mailt küld (24 óráig él). Ehhez fusson a queue worker.
- Ha az e-mail cím már létezik (pl. ügyfélként), a parancs megkérdezi, megkapja-e a fiók a szerepkört.

Belépés: `/admin/belepes`.

## Jogi dokumentumok

Az ügyféloldali belépés és a meghívó elfogadása a három jogi PDF-et kéri (adatkezelési tájékoztató, ÁSZF, cookie-tájékoztató). Töltsd fel őket az adminban: *Beállítások → Jogi dokumentumok*. Amíg hiányoznak, a setup check figyelmeztet (élesben hibát jelez).

Fejlesztés közben a `composer run dev` egyszerre indítja a szervert, a Vite-ot, a queue workert és a naplót.

## Háttérfolyamatok helyben

- Queue worker: `php artisan queue:work`
- Ütemező: `php artisan schedule:work`

A `/health` és a setup check is figyeli, hogy fut-e a kettő.

## Ellenőrzés

```bash
php artisan megis:setup:check
```

Soronként OK, Figyelmeztetés vagy Hiba jelenik meg. Hibánál a kilépési kód nem nulla. Helyben a két `MEGIS_*` kötelező változó (`MEGIS_SUPPORT_EMAIL`, `MEGIS_ALERT_RECIPIENTS`), illetve a worker és az ütemező hiánya hibaként jelenik meg.

## Tesztek

```bash
php artisan test --compact
```

- A tesztek egyelőre sqlite memóriában futnak.
- A PostgreSQL-t (jsonb) igénylő tesztek a `pgsql` csoportba kerülnek. A `phpunit.xml` ezt a csoportot addig kihagyja, amíg nincs `megis_test` adatbázis.
- A tesztek nem hívnak külső szolgáltatást (`Http::preventStrayRequests()`), és nem kell hozzájuk Vite-build.

## Céges proxy vagy vírusirtó TLS-szűrése (pl. Avast)

Ha a `composer` vagy az `npm run build` „SSL certificate problem: unable to get local issuer certificate” hibát ad, a vírusirtó saját tanúsítvánnyal bontja a HTTPS-t. Exportáld a Windows megbízható gyökértanúsítványait egy PEM-fájlba, és add át a parancsnak:

```powershell
$env:COMPOSER_CAFILE = "C:\út\windows-roots.pem"     # composer
$env:NODE_EXTRA_CA_CERTS = "C:\út\windows-roots.pem" # npm run build (Bunny-fontok)
```

## Konzolparancsok

| Parancs | Mire való |
|---|---|
| `php artisan megis:setup:check` | A környezet ellenőrzése (docs/uzemeltetes.md · 7) |
| `php artisan megis:admin:create [--role=superadmin\|admin] [--invite]` | Admin létrehozása vagy meghívása (01 · 1.1) |
| `php artisan megis:admin:unlock {email}` | Zárolt admin feloldása 10 hibás belépés után. A zárolás csak így oldható fel (01 · 1.2). |
