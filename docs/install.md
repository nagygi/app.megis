# Telepítés helyben

## Szükséges verziók

| Eszköz | Verzió | Megjegyzés |
|---|---|---|
| PHP | 8.4.1 vagy újabb | Kiterjesztések: `pdo_pgsql`, `pdo_sqlite`, `intl`, `redis`, `mbstring`, `fileinfo`, `zip`, `gd`. A Laravel Herd PHP 8.4-e mindet tartalmazza. A herd-lite 8.4.0 nem elég. |
| Composer | 2.8 | |
| Node.js | 22 | |
| PostgreSQL | 14 vagy újabb | A fejlesztői adatbázis AWS RDS-en van (`megis_dev`, eu-central-1), `DB_SSLMODE=require`-rel. A hozzáférést a projektvezető adja. Helyben sqlite is elég. |
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

## Az Értéktár kezdő értékei (03 · 3.6)

```bash
php artisan megis:values:import ertekek.xlsx --dry-run   # csak ellenőriz
php artisan megis:values:import ertekek.xlsx
```

- Minta: `docs/ertektar-import-minta.xlsx` (három példaérték, a második munkalapon útmutató).
- xlsx vagy csv, az első munkalap számít. Az első sor a fejléc, soronként egy érték.
- Oszlopok (a sorrend mindegy, a kis- és nagybetű és az írásjelek nem számítanak):
  - `Név` (kötelező);
  - `Kategória` (ha nincs ilyen, létrejön);
  - `Mit jelent?`, `Mit nem jelent?`;
  - `6 · Mikor teljesül nagyon?` és `1 · Mikor nagyon nem teljesül?` (mindkettőben legalább egy elem kell);
  - `Példák`, `Szinonimák, kulcsszavak`.
- A listák elemei egy cellán belül külön sorban vannak. Az elején álló `-`, `•` vagy sorszám lemarad. Egy elem 2–255 karakter.
- Minden érték v1-ként, átnézve kerül be. Ami már az Értéktárban van, kimarad, így a parancs újra futtatható. Ha bármelyik sor hibás, semmi nem kerül be.

## Értéktükör és értékmérés helyben (06)

- Az elemzés (A, B, C lépcső) az `ai` queue-n fut, ezért kell a queue worker. AI-kulcs nélkül a feldolgozás „Nem tudtuk feldolgozni” állapotba kerül.
- Hangot az átíró dolgoz fel. Amíg a 12.7 PoC nem választott szolgáltatót, helyben a minta-átíró használható: `MEGIS_TRANSCRIBER=fake`. Ez mindig ugyanazt a rövid átiratot adja, élesben nem indul el.
- A felvételek hosszát az `ffprobe` méri (`FFPROBE_PATH`). Nélküle a böngésző által mért hossz marad, és a setup check figyelmeztet.
- A névtelen tükrök takarítása: `php artisan megis:mirror:prune` (óránként ütemezve).

### Átíró szolgáltatók összevetése (12.7)

```bash
php artisan megis:transcription:compare felvetel.mp3 --model=openai:gpt-4o-transcribe-diarize --model=eleven:scribe_v1
```

- Ugyanazt a felvételt minden megadott `szolgáltató:modell` párral átíratja. Táblázatban mutatja az időt, a beszélők és a szavak számát, a perceket és a költséget.
- Az átiratok a `storage/app/private/transcription-poc/` alá kerülnek. A hívások a Rendszer → AI használat lapon is látszanak.
- **A felvétel személyes adatot tartalmaz:** ne kerüljön a kódtárba, és a próba után töröld.

## Diagnózis és folyamatok helyben (17–25, 09–11)

- Diagnózist az admin indít: *Diagnózisok → + Új diagnózis → Diagnózis indítása* (A1). A meghívó levelei helyben a Mailpitbe vagy a logba mennek.
- A termék kulcsa adja a lépéseket (`config/diagnosis.php`). A három kezdő terméket a `ProductSeeder` kulccsal együtt hozza létre; a Snapshot · Külsőhöz még nincs diagnózis-folyamat.
- A naptár iCal linkkel vagy ICS fájllal kulcsok nélkül is kipróbálható. A Google- és a Microsoft-összekötéshez kellenek a `GOOGLE_*`, illetve a `MICROSOFT_*` kulcsok; nélkülük ez a két lehetőség nem jelenik meg.
- A beszélgetés kivonata és a naptár névjavaslata az `ai` queue-n fut; AI-kulcs nélkül a kivonat elmarad, a naptár a címeket tartja meg.
- A mezők diktálása (mikrofon) a minta-átíróval helyben is működik: `MEGIS_TRANSCRIBER=fake`.

## Demó diagnózis és Képességtár (M7, 07)

- **Demó szervezet:** `php artisan db:seed --class=DemoDiagnosisSeeder`. Teljes diagnózis egy csapatról: 1 vezető, 4 társ, 2 mért folyamat, 4 helyzet pontokkal és idézetekkel, AI-hívás nélkül. Futó szakaszban áll, a küszöb teljesült: az adminban indítható az olvasat, onnan a publikálás, a riport és a visszajelzés. Az emberek `demo-…@example.com` címeket kapnak; belépni az adminból, „a nevében” lehet. Élesben nem fut, és egy második futtatás nem hozza létre újra.
- **Képességtár:** *Képességtár → Frissítés a mégis tárából*, a „Képességrendszer · tárak” Sheet xlsx-exportjával. A fájl személyes adatot tartalmazó füleket is tartalmaz (Bizonyíték, Esettár, Találat): ezek nem töltődnek be, de a fájl ne kerüljön a kódtárba.

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
- A tesztek mindig sqlite memóriában futnak (`phpunit.xml`, `force="true"`), akkor is, ha a `.env` az RDS-re mutat: a `RefreshDatabase` minden futáskor üríti az adatbázist.
- A PostgreSQL-t (jsonb) igénylő tesztek a `pgsql` csoportba kerülnek. A `phpunit.xml` ezt a csoportot addig kihagyja, amíg nincs külön `megis_test` adatbázis. A `megis_dev`-re tesztet futtatni tilos.
- A böngészős tesztek (`tests/Browser`) a buildelt asseteket használják, előttük `npm run build` kell.
- AI-hívás tesztben soha nem megy ki: `AiGateway::fake()`.
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
| `php artisan megis:values:import {file} [--dry-run]` | Az Értéktár kezdő értékeinek betöltése táblázatból (03 · 3.6) |
| `php artisan megis:mirror:prune` | A 24 óránál régebbi, fiókhoz nem kötött Értéktükrök törlése (06 · 6.1) |
| `php artisan megis:transcription:compare {file} --model=…` | Átíró szolgáltatók összevetése egy felvételen (12 · 12.7) |
| `php artisan megis:calendar:sync` | Az összekötött naptárak frissítése, új alkalmak az elfogadott folyamatokhoz, szétkötés az időablak végén (09 · 9.4, 9.5) |
| `php artisan megis:calendar:prune` | A 90 napnál régebbi naptár-metaadat törlése (09 · 9.5) |
| `php artisan megis:admin:unlock {email}` | Zárolt admin feloldása 10 hibás belépés után. A zárolás csak így oldható fel (01 · 1.2). |
