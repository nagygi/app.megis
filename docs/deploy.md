# Kiadás és üzemeltetés

Kiegészíti a `docs/uzemeltetes.md`-t. Itt a konkrét lépések és konfigurációs pontok vannak.

## Környezetek

| Környezet | Indexelés | Levelek | Védelem |
|---|---|---|---|
| local | noindex | Mailpit vagy log | – |
| staging | noindex | csak a `MEGIS_MAIL_ALLOWLIST` címeire, vagy levélcsapdába | `MEGIS_STAGING_USER`/`MEGIS_STAGING_PASSWORD` és/vagy `MEGIS_STAGING_ALLOWED_IPS` |
| production | indexelhető | valódi címekre | – |

A noindex-et az `X-Robots-Tag` fejléc, a `robots.txt` és a lapok meta-tagje adja. Csak a production indexelhető.

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

A titkok csak környezeti változóban vannak. AWS-en javasolt az AWS Secrets Manager vagy az SSM Parameter Store.

### Infrastruktúra-beállítások

- **Redis:** a session és a queue adatbázisa kiürítés nélkül fusson (`maxmemory-policy noeviction`). A cache külön adatbázisban van (`REDIS_CACHE_DB`).
- **Fájltár (S3, EU-régió):** privát bucket, nyilvános hozzáférés nélkül, verziózás bekapcsolva. A régi verziókra 30 napos életciklus-szabály kell.
- **Napló:** a `storage/logs/{app,auth,ai,mail,queue}-*.log` fájlok naponta rotálódnak. A központi log-tárba csatornánként kell szállítani őket, 365 napos megőrzéssel (AWS esetén CloudWatch Logs).
- **ffprobe:** az ffmpeg csomag része, a szerveren telepítve kell lennie. A böngészős webm-felvételekben nincs hossz, ezt méri.
- **Feltöltés:** a Livewire ideiglenes feltöltései S3-on közvetlenül a bucketbe mennek (aláírt URL), így a 300 MB-os hangfájlra a PHP és az nginx feltöltési korlátja nem vonatkozik. Helyi disknél az `upload_max_filesize` és a `post_max_size` legyen legalább 300M.
- **Naptár-alkalmazások:** a Google-alkalmazás (Calendar API, `calendar.events.readonly`) és a Microsoft Entra multi-tenant alkalmazás (`Calendars.Read`, `offline_access`) a mégis céges fiókján. A visszahívási cím: `{APP_URL}/naptar/google/visszahivas`, illetve `{APP_URL}/naptar/microsoft/visszahivas`. A Google-ellenőrzésig legfeljebb 100 tesztfelhasználó köthet össze.
- **Levélküldő:** a megis.hu domainen SPF, DKIM és DMARC kell. AWS esetén SES (eu-central-1), bounce- és complaint-értesítéssel.
- **Uptime-figyelés:** UptimeRobot vagy Better Stack hívja percenként a `GET /health`-et. Hiba esetén e-mailt küld a riasztási listára.

## Kiadás lépései

### Előtte (NFR · 10)

- [ ] Verzió emelve a `config/megis.php`-ban.
- [ ] `docs/changelog.md` frissítve: verzió, commit-hash, dátum.
- [ ] A migrációk átnézve. Van-e visszafordíthatatlan lépés?
- [ ] Tesztek zöldek: `php artisan test --compact`.
- [ ] A rollback-verzió azonosítva (az előző release commit-hash-e).

### Telepítés

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
