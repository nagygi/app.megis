# Változásnapló

A legfrissebb verzió van felül. Formátum: `## {verzió} · {commit-hash} · {commit dátuma}`.

## 0.70 · {hash} · 2026-10-14

M6 · Olvasat, publikálás, riport (20, 27, 29, 21, 26.3, 28, 32, 30, 31, 33, 07).

- **Olvasat (20):** *Olvasat* fül három oszloppal (blokkok és sorok · a sor részletei · napló); jóváhagyás soronként és blokkonként; átírás kötelező indoklással; a területek és a fa kézi pontozása egész pontokkal; „nincs elég adat”; nyomok kizárása, kiemelése, kézi hozzáadása és másolása; állítások vázlat → jóváhagyott állapottal, a sor nyomaira építve; md szövegek EasyMDE-vel (160, a márkád olvasata 600 karakter); a legnagyobb rés jóváhagyása; haladás-sáv. Ha egy jóváhagyott sor automatikus értéke változik, a sor „vár” állapotba esik, a napló megmondja, miért.
- **Riport (27):** R0 válasz, R1 összefoglaló gyűrűvel, R1·b belső kép, R2–R5 fejezetek (értékek, folyamatok, területek, márkád alapja) tételekre mélyítve, összehasonlító nézet (R1·c), zárás, Melléklet; nézetváltó (Csapat · Szervezet · Egyén); a Folyamatok fejezet kimarad, ha nincs folyamat; Riportok a menüben. A riport a fagyasztott pillanatképből olvas, a vezető és a szervezet vezetői látják.
- **Útravaló (29):** a társ négy blokkja automatikusan, skálával, a küszöbök alatt (2 helyzet, 3 másik kitöltő) rejtve; PDF-ben is.
- **Publikálás (21):** *Publikálás* fül ellenőrzőlistával, előnézettel telefonon és asztalon (iframe, társ-választóval), publikálás → fagyasztott `report_snapshots`, e-mail csak az első publikáláskor; javítás „frissítve · dátum · ok” jelzéssel; visszavonás indoklással (Olvasat szakasz, Draft státusz).
- **Kezdőlap (26.3):** V12 és V12·b a riporttal, Értékmérés-teaser, Útravaló a társ kezdőlapján.
- **Visszajelzés, Melléklet, PDF (28):** fejezetenkénti visszajelzés diktálással → Beérkező sor „Beépítem” (az olvasatra visz) és „Válaszolok” (e-mail) gombbal; a riport és az Útravaló PDF-je a pillanatképből, Gotenberg-gel.
- **Bölcsességtár (32):** BT1–BT4 és BT6 md-fájlokból (`resources/content/hu/bolcsessegtar`), „Nálatok:” sor a saját riport számával; „Bővebben” linkek.
- **Értéktár, Profil, adatlap (30, 31, 33):** a szervezet értékei mellett a legutóbbi riport pontja és a korábbi definíció jelzése, „Hozzáadom a sajátjaimhoz”; Profil: a diagnózisod állapota, Rád vár (legfeljebb 3 sor), Emberek emlékeztetővel, Adataim törlése jelszóval vagy kóddal; óránkénti végleges törlés névtelen sírkővel, a publikált riport idézetek nélküli javításával; az ügyfél adatlapján diagnózisok, részdiagnózisok, hozzájárulások, törölt felhasználónál csak a napló.
- **Képességtár (07):** *Képességtár* menü (Képességek, Csoportok, Frissítés a mégis tárából); a 12 munkacsoport és az E1–E9 előfeltételek kezdő adatként; adatlap a 7.2 mezőivel, 1000 karakteres listaelemekkel, előfeltételekkel és képesség-függőséggel, csak olvasható részletekkel; verziók megjegyzéssel és összehasonlítással; archiválás; betöltés a Sheet xlsx-exportjából előnézettel (új · változott · változatlan, mezőnként), `[JAVASLAT]` jelöléssel, a „nincs még” és a többi üres érték állapotával, a „félretett” archiválva. Csak a Képesség és a Képesség-részletek fül jön be.
- **Verziók:** a verzió megjegyzést kaphat (mi változott és miért); az Értéktár és a Képességtár verziótörténete közös komponens.
- **Ütemezett feladat:** `purge:deleted-accounts` (óránként). Új setup-check sor: PDF-motor.
- **Új csomagok:** `spatie/laravel-pdf` (Gotenberg), `easymde` (npm, csak az olvasat lapján töltődik be).

## 0.60 · 1dbea23 · 2026-10-14

M5 · Futás (12, 13, 18, 19, 22, 26.1–26.2, 26.4).

- **Alkalmak (12):**
  - V11 Hozzáteszek: Értéktükör, „Egy helyzet” elmondva vagy leírva, feltöltés egy dobozban (hang, txt, md, docx, pdf), feldolgozás-lista állapotokkal, „Újra” és „Kiveszem”;
  - V11·b Hova tartozik: a jogosult mért folyamatok (társnak csak, amelyben résztvevő), a „nem mérjük” szaggatottan, a naptár legutóbbi alkalma előre kijelölve, dátum, felvétel-hozzájárulás (ki és mikor), „Egyikhez sem”;
  - V11·c A megélésed: soronként 1–6 vagy „nem tudom”, eredményesség, megjegyzés diktálással, „Beküldöm” vagy „Kihagyom”;
  - T5·b A helyzeteid: időrendben, „megélés hiányzik” linkkel, a saját hang törölhető;
  - admin: „Besorolásra vár” blokk a *Folyamatok* fülön, folyamatonkénti alkalom-lap az anyagokkal és a megélések számával, „+ Anyag”.
- **Folyamat-diagnózis (13):** Folyamat szintű részdiagnózis folyamatonként; alkalmanként egy pont az anyagok átlagából; n, szórás, minimum (Teljes 2, snapshot 1 „Egy alkalomból” jelzéssel), alfolyamat a szülőbe; megélés vezető és társak szerint; eredményesség-összesítés; kézi átírás kötelező indoklással, az automatikus érték mellette; alkalmak listája pontokkal és idézetekkel; összesítés-leírás; „N új alkalom a publikálás óta”.
- **Számítás (18):**
  - a fő diagnózis négy blokkja: értékek az összes anyagból, folyamatok a folyamat-diagnózisból, területek (Teljes) és a fa kézi pontozásra, megélés és rés;
  - gyűrű-%, lépcső és pont a kerekítetlen értékekből, „–” sorok nélkül; a legnagyobb rés; magánszemélynél és egy embernél az önkép és a helyzetek rése;
  - újraszámolás egyedi, késleltetett jobbal: feldolgozott anyag, megélés, visszavont hozzájárulás, indítás, vezetőcsere, kézi átírás;
  - küszöb (3 társ) és a „csapat-szám nélkül megy” döntés; nézetek adatai (Csapat · Egyén · Szervezet);
  - olvasat indulásakor AI-vázlat az állításokhoz (`diagnozis-allitasok` v1) és az olvasat szövegeihez (`olvasat-szovegek` v1), nevek nélkül; az admin munkája megmarad;
  - *Beállítások → Területek*.
- **Futás (19):** a fő diagnózis fejléce („23. nap / 47”, kapcsolattartó) és *Futás* füle a kapcsolattartónak és a szuperadminnak; mindig a szakasz füle nyílik; kert-nézet szakasz-oszlopokkal, coral kerettel és szűrőkkel, a lista váltóval; Futás lap: időablak, vezető, társak, folyamatok, anyagok, naptár; küszöb-sáv (Szólok · Várok 5 napot · Csapat-szám nélkül megy); emlékeztetők 48 órás korláttal; időablak-módosítás e-maillel; olvasat indítása; beszélgetések a mégisszel.
- **Beérkező (22):** napi átnézés (meghívó 5 napja vagy lejárt, kitöltés a kezdés napjáig, küszöb, publikálás napja), automatikus lezárás, soronkénti gombok (besorolás, újra, emlékeztetem, új meghívó, → Futás), 8:00-s összefoglaló a kapcsolattartóknak, azonnali e-mail foglalásnál.
- **Kezdőlap (26):** V10 és V10·b hetekkel vagy napokkal, onboarding, társak „k / n kitöltötte” és vezetői emlékeztető, beszélgetések, „Amit hozzátesztek”, olvasat és riport dátuma; olvasat alatt csak olvasható; coral plusz a V10-en és a T5-ön; a kezdőlap a vezetett, majd a társ-diagnózist nyitja (26.4).
- **Ütemezett feladatok:** `inbox:scan` (07:30), `inbox:digest` (08:00).
- **Javítás:** a véletlenül verziókezelt `megis_dev` SQLite-fájl kikerült, és a `.gitignore`-ba került.

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
