# Változásnapló

A legfrissebb verzió van felül. Formátum: `## {verzió} · {commit-hash} · {commit dátuma}`.

## 0.50 · 225e376 · 2026-10-14

M4 · Fő diagnózis indítása és kitöltés (17, 23, 24, 08 kliens, 11, 10, 09, 25, 31.5–31.6 részben).

- **Fő diagnózis (17):**
  - A1 élő előnézettel: szervezet, kapcsolattartó, alany (csapat, egy ember, magánszemély), csapat vagy új csapat, vezető vagy új vezető, társak kivehetően, időablak, idézet-kapcsoló;
  - a termék a kulcsa szerint adja a lépéseket (`config/diagnosis.php`), és indításkor a diagnózisra mentődik;
  - szakaszgép (Vázlat → Meghívva → Kitöltés → Fut → Olvasat → Publikálva, bárhonnan Lezárva) naplóval; a szakasz adja a státuszt, a részdiagnózisok követik;
  - részdiagnózisok: Értéktükör (6.2, több futó diagnózisnál kérdez), megélés, kézi és feltöltött diagnózis a „Fő diagnózis” mezővel; *Részdiagnózisok* fül;
  - vezetőcsere megerősítéssel, időablak és társak módosítása az olvasatig; aki futás közben a csapatba kerül, társ lesz;
  - szakasz oszlop és szűrő a Diagnózisok listán; *Diagnózisok* és *Folyamatok* fül a szervezeten.
- **Kitöltés kerete (23):** V1 és V1·b lépéslistával, percekkel és „A végén” képpel; lépésszámláló a fejlécben; „Mentve” visszajelzés; csak olvasható lépés; V9 „Indul a diagnózis”, a szervezet értékszett-verziójának rögzítésével.
- **Saját értékek (24):** V2 három úttal (Értéktükörből, Elmondom, hozzáadás vagy választás), V2·b kártyákkal és állapottal (erősen jelen · részben · még nincs beépítve), V2·c; „Felhasználó által felvett” Értéktár-eredet; verzió a lépés elhagyásakor; magánszemélynél a szervezet értékei is.
- **A szervezet értékei és a márkád alapja (08):** V3 3–5 értékkel és „a tiéd is” jelöléssel, V4 négy dobozzal, V5 megélés 1–6 vagy „nem tudom” skálával → Megélés részdiagnózis a rögzített verzióval.
- **Folyamatok (11, 10, 09):**
  - V6 belépő, V6·d detektált lista négy csoporttal (átnevezés nyommal, típusváltó, alfolyamat, elfogadás, kézi hozzáadás), V6·e adatlap (felelős, résztvevők, csapatok, saját mérőszám értékekkel), V6·f összegzés; Snapshot · Belsőben legfeljebb 2 mért folyamat;
  - beszélgetés négy kérdéssel, válaszonként AI-kivonattal, chipekkel és töréspont-jelölttel (`beszelgetes-kivonat` v1);
  - naptár: iCal link, ICS fájl, Google és Microsoft OAuth csak olvasási joggal, titkosított hozzáféréssel; szabályalapú mintakeresés és AI-névjavaslat nevek és e-mailek nélkül (`naptar-elnevezes` v1); összevonási javaslat a beszélgetés folyamataival; napi frissítés, 90 napos metaadat, szétkötés;
  - Tárak → Folyamatok; admin *Folyamatok* fül naptár-sorral, átnézendő blokkal és „a mégis javaslata” szöveggel.
- **Társ útja (25):** meghívó „Fut”-kor, T1 hozzájárulással (szövegváltozattal, naplóval), T2, T3, T4 a rögzített verzióból, T5 kezdőlap; Profil → Hozzájárulásaim és visszavonás a publikálásig (31.5, 31.6).
- **Diktálás:** a szöveges mezők mikrofonnal is kitölthetők (V3, V4, V6·c).
- **Belépés (14.4, 26.4):** a vezetett, majd a futó társ-diagnózis nyílik; a szervezet kezdőlapja a diagnózisokat listázza.
- **Ütemezett feladatok:** `sync:calendars`, `prune:calendar-metadata`. Új setup-check sor: Naptár-összekötés.
- **Új csomagok:** `sabre/vobject`, `laravel/socialite`, `socialiteproviders/microsoft`.

## 0.40 · 5d398e9 · 2026-10-14

M3 · Értékmérés-motor és Értéktükör (06, 12.7 előkészítés, 30 alap, 31.5 részben).

- **Értékmérés-motor (06 · 6.4):**
  - A: szabad detektálás értéklista nélkül, hosszú átiratnál darabolva;
  - B: illesztés az Értéktár név nélküli, csak jelentésből és példákból álló listájára;
  - C: pontozás „nincs adat” vagy 1–6, idézet-hivatkozással;
  - nyers detektálás minden eredménnyel és AI-hívással; a nem illeszkedő értékek „AI által detektált” értékként kerülnek az Értéktárba, a névszabályokkal;
  - promptok: `ertektukor-detektalas`, `ertektukor-illesztes`, `ertektukor-pontozas` (v1).
- **Nyilvános Értéktükör (06 · 6.1):**
  - ET1 nyitó a kezdőlapon;
  - ET2 böngészős felvétel számlálóval, telő körrel, biztató szövegekkel, csendnél támogató kérdéssel; szöveg vagy fájl is;
  - ET3 értékek idézetekkel és „nem az enyém”; ET5 teljes kép kíváncsiság-kérdésekkel;
  - fiók e-mail-megerősítéssel, magánszemély-szervezettel és a jogi elfogadások rögzítésével; meglévő fiók helyben belép jelszóval vagy kóddal;
  - „Időpontot foglalok” Beérkező-sorral; Turnstile, IP-korlát; a névtelen tükör 24 óráig él, utána csak névtelen statisztika marad.
- **Értéktükör a saját menüből (6.2):** a diagnózis az aktív szervezethez kerül.
- **Automatikus diagnózis (6.5):** Értéktükör típus, Automatikus, Jóváhagyásra vár; a sorokon idézetek, „Egyéb detektált értékek”, a forrás átirata és hangja az admin oldalán.
- **Admin feltöltés (6.3):** hang vagy leirat, beszélők felismerése és hozzárendelése, beszélőnként felhasználói diagnózis.
- **Hangfelvételek (6.6, 31.5):** privát tárhely, admin lejátszás, törlés a Profil Felvételeim blokkjából vagy az adminból; csak a hang törlődik.
- **Támogató kérdések (6.7)** rendezéssel és aktív/inaktív kapcsolóval.
- **Ügyféloldali Értéktár (30 alap):** a szervezet értékei a saját definíciójukkal; az Értéktár böngészője keresővel és kategóriaszűrővel.
- **Átírás:** `Transcriber` interfész a `laravel/ai`-ra építve; minta-átíró helyi munkához; `megis:transcription:compare` a 12.7 PoC-hoz.
- **Ütemezett feladat:** `prune:mirror-sessions` óránként. Új setup-check sor: Átírás.

## 0.30 · 8256e0e · 2026-10-14

M2 · Tárak, értékszett, AI-réteg, kézi diagnózisok (03, 08 adatmodell, 04, 16, 22 alap).

- Értéktár (03):
  - értékek listája keresővel (név, szinonimák), szűrőkkel, rendezéssel; kategóriák;
  - érték-űrlap a lista-szerkesztővel (húzás és nyilak), slug, kötelező 1 és 6;
  - verziók minden változással, csak olvasható régi verzió, két verzió összehasonlítása (hozzáadott, törölt, áthelyezett elemek, szöveg szószinten);
  - archiválás, visszaállítás, „Átnézve”, eredet-címkék (03 · 3.7);
  - `megis:values:import`: a kezdő értékek betöltése táblázatból.
- Diagnózisok (04):
  - típusok; lista; diagnózis szervezet, csapat vagy felhasználó szintre, dátummal vagy időszakkal;
  - értékek az Értéktárból a használt verzióval, vagy név szerint új „Diagnózisnál felvett” értékként; a márkád alapja négy sora;
  - mérőszám (1–6 vagy „–”), saját megélés (1–6 vagy „nem tudom”), példák, indoklás, rés;
  - státuszok (Draft → Jóváhagyásra vár → Publikált, visszavonás) naplóval;
  - aggregált adatok betöltése előnézettel, leírással, n-nel, szórással, figyelmeztetésekkel.
- A szervezet értékei (08): adatmodell és verziók; admin *Értékek* fül 3–5 értékkel és a márkád alapjával.
- AI-réteg (16): `AiGateway` feladatonkénti konfigurációval, verziózott promptokkal, `ai_calls` naplóval (pontos modell, tokenek, költség); AI-jobok 3 próbával; Rendszer → AI használat (CSV-vel), AI konfiguráció, Promptok; setup-check sor.
- Beérkező (22 alap): sorok deduplikálással, lezárással és automatikus lezárással; lista szűrőkkel, „Lezártak”, számláló a menüben.
- Fejlesztői adatbázis: AWS RDS (PostgreSQL 14).

## 0.20 · 8015e33 · 2026-10-14

M1 · Identitás, szervezetek, belépés, keret (01, 02, 05, 14, 15, 31 alap, 33 alap).

- Admin (01):
  - `megis:admin:create` és `megis:admin:unlock`;
  - belépés, zárolás 10 hiba után, riasztás a többi szuperadminnak, 6 órás inaktivitási korlát;
  - meghívás, jelszó-visszaállítás, inaktiválás; minden jelszócsere és inaktiválás kilépteti a többi munkamenetet;
  - Adminok lista, profil, Rendszer menü (szuperadmin) a Health lappal.
- Szervezetek (02):
  - termékek; szervezetek slug-gal, magánszemély-szabályokkal és létszám-limittel;
  - csapatok csapatvezetőkkel;
  - tagság címkékkel és pozícióval; „Felvéve” → „Meghívó küldése”; meglévő felhasználó értesítése;
  - a szervezet fülei (Adatok, Csapatok, Felhasználók); a többi fül a saját jegyével jön.
- Jogi dokumentumok (05): verziók hatályba lépéssel, „újra el kell fogadni” kapcsolóval, állandó URL-ek, setup-check sor.
- Ügyféloldal (14, 15, 31):
  - meghívó elfogadása (14 nap) a jogi dokumentumokkal;
  - belépés kóddal vagy linkkel (15 perc), jelszóval opcionálisan; rate limitek űrlaphibaként;
  - 30 napos gördülő munkamenet; újra-elfogadás kérése; kezdőlap-választás az utolsó szervezet szerint;
  - fejléc, menü, szervezetválasztó; PWA (manifest, service worker, offline lap);
  - Profil: név automatikus mentéssel, jelszó beállítása és cseréje.
- Belépés a felhasználó nevében (14.6): külön munkamenet, coral sáv, naplózott változtatások, tiltott jelszócsere és elfogadás.
- Ügyfelek (33 alap): lista, adatlap szervezetekkel, elfogadásokkal és naplóval.
- Minimális nyilvános kezdőlap jogi linkekkel a láblécben.
- Ütemezett feladat: lejárt tokenek takarítása (`prune:user-tokens`).

## 0.10 · 8015e33 · 2026-10-14 (az M1-gyel közös commit)

M0 · Alapozás.

- PHP 8.4, Livewire 4 (class-alapú komponensek, Alpine a saját bundle-ből), S3-flysystem.
- Magyar nyelv és budapesti idő alapból, magyar validációs és rendszerüzenetek.
- Design system: a mégis tokenjei a Tailwind témában. Saját hostolt Geologica. Ikonok, szimbólumok és logók SVG-ként.
- Public layout SEO- és Open Graph-tagekkel. Brandelt hibalapok (403, 404, 419, 429, 500, 503). Ideiglenes kezdőlap.
- Üzemeltetés:
  - log-csatornák (app, auth, ai, mail, queue) érzékenyadat-szűréssel;
  - hibariasztás e-mailben, 15 perces deduplikálással;
  - `GET /health` heartbeattel;
  - `php artisan megis:setup:check`.
- Staging-védelem (jelszó vagy IP), noindex nem éles környezetben, levél-allowlist, `robots.txt` és `sitemap.xml`.
- Turnstile-ellenőrzés és validációs szabály. A foglalt slugok listája.
- Dokumentáció: `install.md`, `deploy.md`, `cron.md`, `changelog.md`.
