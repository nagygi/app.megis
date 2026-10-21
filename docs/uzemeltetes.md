# Üzemeltetés – környezetek, health, logok, mentés, dokumentáció

## Cél

Az üzemeltetés alapjai az NFR szerint, az első éles ügyfél előtt. Ez nem backlog jegy, hanem az NFR-t (`nfr_exot_laravel.md`, `nfr_project.md`) kiegészítő üzemeltetési leírás. A Boostban és az NFR-ben leírt szabályokat itt nem ismételjük, csak azt, ami a mégisre konkrétan vonatkozik.

Minden adat az EU-n belül van: az adatbázis, a fájlok, a mentések és a logok is.

## 1. Szolgáltatások

- PostgreSQL.
- Redis. Ezen fut a cache, a session és a queue.
- **Fájlok:** privát, S3-kompatibilis objektumtár, nyilvános hozzáférés nélkül.
  - Ide kerülnek a hangfájlok, az átiratok, a dokumentumok és a jogi PDF-ek.
  - Letölteni és lejátszani csak jogosultság-ellenőrzés után lehet, rövid ideig élő aláírt linkkel.
- **E-mail:** külső tranzakciós levélküldő szolgáltató.
  - Küldő domain: megis.hu. SPF, DKIM és DMARC beállítva.
  - A visszapattanó és panaszolt címek automatikusan megjelölődnek. Az adminban a felhasználó sorában látszik: „A levél nem kézbesíthető.” (02).
- Queue worker és ütemező (cron).
- Minden konfigurációs pont a `deploy.md`-be kerül.

## 2. Környezetek

- **Local:** a fejlesztő gépe. A leírás az `install.md`-ben van.
- **Staging:**
  - Itt nézi át a mégis a kiadást.
  - Jelszóval vagy IP-szűréssel védett, és noindex.
  - A levelek valódi címekre mennek, hogy a mégis a meghívókat és az értesítéseket is kipróbálhassa. A `MEGIS_MAIL_ALLOWLIST` megadásával szűkíthető a címzettek köre; ilyenkor az admin felület sávban jelzi, kik kapnak levelet.
  - Saját AI-kulcsai vannak, alacsony kerettel.
- **Production:** csak itt indexelhetők a nyilvános oldalak.

## 3. Health check

**Végpont: `GET /health`**

- Gyorsan válaszol: 200, ha minden rendben van, 503, ha nem.
- Kívülről csak „ok” vagy „fail” látszik. A részletek tokennel kérhetők.
- **Mit ellenőriz:**
  - az adatbázist;
  - a Redist;
  - a fájltárat (egy kis próbafájl olvasásával);
  - hogy él-e a queue worker (heartbeat);
  - hogy lefutott-e az ütemező az elmúlt 5 percben;
  - hogy van-e elég szabad lemezhely.
- **Figyelés:** UptimeRobot vagy Better Stack percenként hívja. Hiba esetén e-mailt küld a riasztási címlistára (5. pont).

**Rendszer → Health** (admin, csak szuperadmin)

- Ugyanezek az ellenőrzések, részletesen.
- Mellettük: app verzió, környezet, az utolsó deploy ideje, a queue hossza, a sikertelen jobok száma.
- Az AI-szolgáltatók utolsó sikeres hívásának ideje (16).

## 4. Logolás

- Külön log-csatornák:
  - app;
  - auth (belépések, sikertelen próbálkozások, zárolás, belépés a felhasználó nevében);
  - ai;
  - mail;
  - queue.
- A logok központi log-tárba kerülnek, csatornánként külön, **365 napos** megőrzéssel. A szerveren naponta rotálódnak, rövid megőrzéssel.
- A logot a fejlesztő nézi. Az adminban nincs logböngésző.
- Logba nem kerülhet: jelszó, belépő kód, token, API-kulcs, átirat vagy felvétel tartalma.

## 5. Hibariasztás

- **Mikor megy riasztás:**
  - kritikus hiba (500-as válasz);
  - egy job az újrapróbálások után is sikertelen;
  - az ütemező nem fut;
  - a health check hibát jelez.
- **Kinek:** a konfigurációban megadott címlistára, alapból a fejlesztőknek.
- Ugyanaz a hiba 15 percen belül csak egyszer megy ki.
- **A levél tartalma:** környezet, hibaüzenet, URL, időpont, a log azonosítója. Személyes adat nincs benne.

## 6. Mentés

- Az adatbázisról naponta automatikus mentés készül, 30 napos megőrzéssel.
- A fájltáron verziózás van bekapcsolva. A régi verziók 30 nap után törlődnek.
  - Ezért egy törölt felvétel (06 · 6.6) a mentésekben még legfeljebb 30 napig megvan.
  - Ezt az adatkezelési tájékoztatónak (05) tartalmaznia kell.
- Negyedévente próba-visszaállítás a stagingre. A lépései a `deploy.md`-ben vannak, és az eredménye is oda kerül.

## 7. Setup check parancs

`php artisan megis:setup:check`

- **Mit ellenőriz:**
  - a kötelező környezeti változókat;
  - a kapcsolatot az adatbázissal, a Redisszel, a fájltárral és a levélküldővel;
  - a Turnstile kulcsokat;
  - az AI-kulcsokat feladatonként (16);
  - hogy fel van-e töltve mindhárom jogi dokumentum (05);
  - hogy fut-e a queue worker és az ütemező;
  - hogy a noindex és a levelező beállítása illik-e a környezethez.
- Soronként OK, Figyelmeztetés vagy Hiba jelenik meg.
- Hiba esetén a kilépési kód nem nulla, így CI-ban és deploy után is futtatható.

## 8. Dokumentáció és verzió

- **Fájlok:**
  - `install.md`;
  - `deploy.md`, benne a rollback is;
  - `changelog.md`;
  - `cron.md`;
  - a readme kiegészítése: a projekt döntései és a külső integrációk (levélküldő, Turnstile, AI-szolgáltatók, naptár OAuth: 09).
- Minden jegy, amely ütemezett feladatot hoz (pl. 09, 06, 05), bejegyzi azt a `cron.md`-be.
- **Verzió:**
  - A verziószám egy helyen van megadva, és az admin menü alján látszik (01).
  - A verzióemelés és a release checklist az NFR szerint működik.

## Nincs benne

- Logböngésző az adminban.
- Külső hibakövető szolgáltatás (pl. Sentry).
- Automatikus skálázás.

## Nyitott kérdések

1. Hol lesz a kódtár és a CI (GitHub, GitLab, Bitbucket)? Az NFR szerint merge-feltétel, hogy a tesztek zöldek legyenek.
