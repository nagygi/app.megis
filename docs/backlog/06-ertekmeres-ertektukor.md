# 06 · Értékmérés – Értéktükör és automatikus diagnózis

## Cél

Hangfelvételből vagy szövegből a rendszer AI-jal kiolvassa az értékeket, és **„Jóváhagyásra vár” státuszú diagnózist** hoz létre (04). Három bemenet van:

1. **Nyilvános Értéktükör** (ET1–ET5): belépés nélkül. A regisztráció első része, az e-mail cím, a beküldés előtt jön.
2. **Értéktükör a saját menüből:** ugyanez bejelentkezett felhasználónak.
3. **Admin feltöltés:** hangfájl vagy leirat egy vagy több felhasználóhoz.

## Alfeladatok

### 6.1 Nyilvános Értéktükör (ET1–ET5)

Mobil-első publikus oldal (NFR: SEO, OG, sitemap, Turnstile).

**ET1 · nyitó**

- A manifesztó jelenik meg.
- Gombok: „Belenézek a tükörbe” és „Van fiókom”.

**ET2 · felvétel**

- Három bemenet:
  - böngészős hangfelvétel számlálóval, legfeljebb 10 percig (8 percnél figyelmeztet);
  - „Inkább leírom” szöveges bevitel;
  - fájl feltöltése: txt, md, doc vagy docx. A megengedett formátumok és a maximális méret ki van írva (NFR).
- Ösztönzés és több instrukció a bevitelnél: „Legalább egy percig mesélj, ha teheted, többet.”
- **Motiváló elem felvétel közben:** egy idővel telő kör vagy sáv, mellette rövid biztató szöveg, ami az idővel változik. Például:
  - 0–1 perc: „Mesélj tovább…”
  - 1 perc után: „Jó kezdet. Mi történt utána?”
  - 3 perc után: „Ebből már gazdag tükör lesz.”
- **Támogató kérdések:** ha a felhasználó felvétel közben 5 másodpercig hallgat, megjelenik egy támogató kérdés az admin által felvett listából (6.7). Egyszerre egy kérdés látszik, és amikor újra beszél, a kérdés halványan a helyén marad. A következő csendnél a következő kérdés jön.
- Ha a felvétel egy percnél rövidebb, finoman jelezzük, de beküldhető.
- **E-mail és elfogadás, a beküldés előtt** (a regisztráció első része, 2026.10.26.):
  - A mód-választó fölött egy kártya: „Hova küldjük a tükröd?”, e-mail mező.
  - Alatta jelölőnégyzet: **„Elfogadom az ÁSZF-et és az adatvédelmi szabályzatot”**.
    - A két dokumentumnév link a 05-ös jegyben feltöltött hatályos verzióra (ÁSZF, Adatkezelési tájékoztató). Ha egy dokumentum még nincs feltöltve, a neve link nélkül áll.
    - Alapból nincs bejelölve. Bejelölés és érvényes e-mail nélkül a tükör nem küldhető be.
    - Rögzítjük, mikor és melyik dokumentumverziót fogadta el. A fiókhoz a megerősítéskor kerül.
  - A beküldéssel azonnal megy a megerősítő levél. Ha a cím már egy aktív fiókhoz tartozik, nem megy levél: a felhasználó az ET5-ön lép be.
- Beküldés Turnstile-lal, IP-alapú rate limittel.
- A feldolgozás alatt várakozó állapot látszik. Ha a feldolgozás nem sikerül, ez jelenik meg: „Nem tudtuk feldolgozni. Próbáld újra.”

**ET3 · az értékeid**

- Az értékek idézetekkel jelennek meg, szám és értékelés nélkül.
- A „nem az enyém” jelöléssel a felhasználó kivehet egy értéket. A kivett érték nem kerül a diagnózisba, a nyers detektálásban jelölve megmarad.
- Minden megtartott érték neve az ET4-re visz. Lent két gomb: az első érték neve (→ ET4) és „A teljes kép” (→ ET5).

**ET4 · elmélyülés** (NM-06, 2026.10.26.)

- Értékenként egy lap, lapozással: fent „‹ Az értékeid” és „EGYENESSÉG · 1 / 3”. Lent a következő érték neve és „A teljes kép”. A „nem az enyém”-mel kivett érték kimarad a lapozásból.
- Négy rész:
  - **„mégis Coach · mit jelent nálad”**: a Coach javaslata a kitöltő idézeteiből, szürke dobozban, coral örvénnyel. Két gomb: „Ez az” (elfogadja) és „Átírom” (saját mondat, legfeljebb 500 karakter).
  - **Miben látszik**: 2–4 rövid jel.
  - **Mi az 1, mi a 6**: a skála két vége nála, a saját szavaival.
  - **Ebből épült**: az idézetek, „a te felvételed” vagy „a te szöveged” felirattal.
- A Coach a mérés után, külön jobban dolgozik (`mirror_deepening`, prompt: `ertektukor-elmelyules`), tükrönként egy AI-hívással. Az ET3 nem vár rá. Amíg dolgozik, az ET4 ezt írja: „A mégis Coach a te szavaidra fordítja…”.
- Ha a Coach nem válaszol, az illesztett értéknél az Értéktár szavai állnak (Mit jelent, 1, 6), Coach-doboz nélkül. A hiba Feldolgozási hiba sort nyit az Értékmérések alatt, és az „Újra” csak a Coachot indítja újra.
- Kikapcsolható: `MIRROR_DEEPENING=false`. Ilyenkor az ET4 az Értéktár szavait mutatja.
- Az elfogadott vagy átírt definíció és a két skálavég a saját értékek lépésébe (24 · V2) készen érkezik, az Értéktár szövege helyett.

**ET5 · teljes kép** (csak az A változat)

- Charcoal sáv a manifesztó sorával: „Kell egy vízió, ami a belső forrásodtól a hatásodig tart.”
- A fa kilenc eleme egy körön a felhasználó értékei körül: gyökér, törzs, korona, árnyék, talaj, micélium, évgyűrűk, a kör, illat.
- Négy kíváncsiság-kérdés jelölőnégyzettel, mindegyik mellett a hozzá tartozó elemek jele (belül: gyökér, törzs · kívül: korona, árnyék · a kettő között: micélium · képesség: tűz). A jelölés azonnal mentődik; az admin a felhasználó adatlapján (33) látja.
- Alatta **„Elküldöm”** (2026.10.26.): a jelölt kérdések és a megtartott értékek e-mailben mennek a `MEGIS_INTEREST_EMAIL` címre (most: ng@exot.hu). A válasz a kitöltőnek megy; bejelentkezett felhasználónál a levélben link az adatlapjára (33).
  - Jelölés nélkül a gomb tiltott. Elküldés után: „Elküldtük. A mégis hamarosan jelentkezik ide: {e-mail}.” Új jelölés után újra küldhető, tükrönként óránként háromszor.
  - A saját menüben is megvan, a „Befejezem” mellett.
- **E-mail mező és időpontfoglalás nincs** (2026.10.26.). A foglalás (Beérkező-sor, levél, adatlap-jelvény) a kódban megmarad, egyelőre nem hívja semmi.
- Az alján:
  - megerősítésre vár: „Nézd meg a leveled”, a címmel, és hogy a megerősítés után a fiókban mi vár rá;
  - a cím egy meglévő fiókhoz tartozik: belépés helyben (lásd lent);
  - már a fiókban van: „Megnyitom a fiókomban”.

**Mi lesz az eredménnyel**

- **Új e-mail címnél** (a beküldéssel):
  - E-mail megy a megerősítő linkkel. A tükör közben elkészül, és a böngészőben az ET3–ET5 végignézhető.
  - A link egy rövid űrlapra visz:
    - vezetéknév, keresztnév (kötelező);
    - jelszó + megerősítés (opcionális). Ha nem ad meg jelszót, e-mailben kapott kóddal lép be. A jelszó később a profilban is beállítható. A jelszó szabályai a 14-es jegyben vannak.
  - Mentés után:
    - létrejön a fiók;
    - létrejön egy **Magánszemély típusú szervezet** (02) a felhasználó nevével, amelynek ő a vezetője;
    - a felhasználó belép, és **a tükre eredményére érkezik**: az értékei, értékenként kibontva (ET4), és a teljes kép (ET5).
  - Ebbe a szervezetbe kerül a felhasználói diagnózis. Ha a megerősítés a feldolgozás vége előtt jön, a diagnózis a feldolgozás végén épül meg.
  - Később is visszanézhető: a belépés a legutóbbi tükörre visz, az *Értéktükör* menüpont lapján pedig a „Korábbi tükreid” lista áll.
- **Ha az e-mail már létező fiókhoz tartozik:**
  - Nem jön létre új fiók, és nem megy megerősítő levél. Az ET5 alján ez áll: „Ehhez az e-mailhez már van fiókod. Lépj be, és a tükröd hozzá kerül.”
  - Kétféleképpen léphet be, helyben, a tükör elvesztése nélkül:
    - **e-mail + jelszó**;
    - **e-mailben kapott kód**.
  - Belépés után a diagnózis ugyanúgy kerül a fiókhoz, mint a saját menüből indított tükörnél (6.2): az aktív szervezethez. Az ET2-n elfogadott dokumentumverziók is a fiókhoz kerülnek, és a felhasználó a tükör eredményére érkezik.
  - A belépés részletei (Turnstile, rate limit, a kód érvényessége) a 14-es jegyben vannak.
- **Ha nincs megadott vagy megerősített e-mail:**
  - Nem lesz diagnózis.
  - Az eredmény 24 óráig él, utána törlődik. A tükör és a megerősítő link egyszerre jár le.
  - Csak a névtelen statisztika marad meg: a szabad értéknév és az illeszkedés, idézet nélkül.

### 6.2 Értéktükör a saját menüből

- A bejelentkezett felhasználó *Értéktükör* menüpontja ugyanezt az utat nyitja meg, az ET4-gyel együtt. Az ET2-n nincs e-mail mező és elfogadó jelölőnégyzet. Az ET5-ön a kíváncsiság-kérdések megmaradnak, alul „Befejezem”.
- Az *Értéktükör* lapján a felhasználó korábbi tükrei ebben a szervezetben („Korábbi tükreid”, a legutóbbi 10): dátum, a megtartott értékek, és egy link az eredményre.
- A diagnózis a felhasználó **aktív szervezetéhez** kerül (szervezetválasztó). Ha csak egy szervezete van, ahhoz.
- Ha a felhasználó vezetőként vagy társként benne van egy futó fő diagnózisban, a tükör annak részdiagnózisa lesz (17 · 17.4).

### 6.3 Admin feltöltés

Menü: *Diagnózisok → „+ Feltöltés”*.

**Mezők**

- Szervezet, diagnózis típusa, diagnózis ideje.
- Fő diagnózis (opcionális): a szervezet „Fut” vagy „Olvasat” szakaszú fő diagnózisai közül. Ha ki van választva, a létrejövő diagnózisok annak részdiagnózisai lesznek (17 · 17.4).
- Fájl:
  - hang: mp3, m4a, wav vagy webm, legfeljebb 90 perc és 300 MB;
  - leirat: txt, md, doc vagy docx, legfeljebb 10 MB.
- A megengedett formátumok és a maximális méret az űrlapon ki van írva (NFR).

**Beszélők**

- A rendszer felismeri a beszélőket (Beszélő 1, Beszélő 2, …), és mindegyiknél mutat néhány idézetet.
- Az admin minden beszélőhöz a szervezet egy felhasználóját rendeli, vagy kihagyja a beszélőt.
- Leiratnál a meglévő beszélő-címkéket használjuk. Ha nincs címke, az egész szöveg egy beszélő.

**Eredmény:** minden hozzárendelt beszélőhöz külön felhasználói diagnózis jön létre, a forrásfájlra mutató linkkel.

### 6.4 A motor (spec 2.10)

Három lépcső:

- **A · Szabad detektálás**
  - A prompt nem kap értéklistát.
  - Kimenet soronként: beszélő · szó szerinti idézet · szabad értéknév · rövid indoklás · erősség 1–6.
- **B · Illesztés**
  - A szabad értékeket a motor az Értéktár (03) értékeire illeszti, **csak definíció és példák alapján, névegyezés alapján soha**. A hamis pozitívok és az átfedések is mennek, de az átfedésekben más értékek neve helyett a kódjuk áll (03 · 3.2).
  - Egy szabad érték 0, 1 vagy több Értéktár-értékre illeszkedhet, konfidenciával.
  - A fő diagnózis anyagainál a szervezet értékeire is illeszt (13, 18). A csapat és az egyéni értékszettek bekötése később jön.
- **C · Pontozás**
  - Illesztett értékenként „nincs adat” vagy 1–6, idézet-hivatkozással.
  - A skála: 1 nincs és mégis van · 2 csak szavak · 3 ad-hoc · 4 néha · 5 többnyire · 6 beépült.

**Nyers detektálás:** minden detektált érték nyersen is megmarad, a következő adatokkal:

- beszélő, idézet, szabad név;
- melyik Értéktár-értékre illeszkedett (vagy semmire), konfidencia;
- prompt- és modellverzió.

Ebből látszik később, mely értékek térnek vissza, és mi hiányzik az Értéktárból.

**Nem illeszkedő értékek**

- A nem illeszkedő értékek azonnal az Értéktárba kerülnek, **„AI által detektált”** címkével (03 · 3.7). A név mellé az AI indoklása kerül a „Mit jelent?” listába.
- Ha a név már létezik:
  - „AI által detektált” címkés értéknél nem jön létre új érték, a meglévőhöz adódik az új előfordulás.
  - Rendes Értéktár-értéknél, amire a B lépcső nem illesztette, új érték jön létre „(detektált)” utótaggal. Így látszik, hogy ugyanaz a szó mást jelent.

**AI szabályok (NFR 11)**

- A modell konfigurációból cserélhető.
- Minden eredmény mellé mentjük a modell és a prompt verzióját.
- A tokenhasználatot modellenként és időpontonként mérjük.

### 6.5 A létrejövő diagnózis (04)

**A diagnózis adatai**

- Szint: Felhasználói.
- Készítő: Automatikus.
- Státusz: **Jóváhagyásra vár**.
- Név: „Értéktükör · {dátum}”, vagy feltöltésnél a fájl neve.
- Diagnózis típusa: „Értéktükör”, vagy feltöltésnél amit az admin választott.

**A sorok**

- Soronként: az illesztett érték, a mérőszám (a C lépcső 1–6 értéke), az indoklás (a motorból) és a sorhoz csatolt idézetek (csak olvasható).
- A „nincs adat” értékek nem kerülnek be.
- A nem illeszkedő értékek a diagnózis oldalán „Egyéb detektált értékek” listában látszanak, mérőszám nélkül.
- A diagnózis oldalán az admin látja az átiratot, és lejátszhatja a hangot.

**Megjelenés a felhasználónál**

- A tükörben (ET3–ET5) azonnal látja az eredményt.
- A platformon a diagnózis csak admin publikálás után jelenik meg.

### 6.6 Hangfelvételek tárolása

- A hangfájl az átirattal együtt megmarad, amíg a felhasználó nem törli.
- Nem nyilvános tárhelyen van, csak jogosult felhasználó érheti el.
- A felhasználó a Profiljából (31 · 31.5) törölheti a felvételét. Ilyenkor csak a hang törlődik, az átirat és a diagnózis megmarad.
- Az admin is törölheti a felvételt.
- Fiók törlésekor minden felvétel törlődik.

### 6.7 Támogató kérdések (admin)

Menü: *Diagnózisok → Támogató kérdések*. Lista, „+ Új kérdés” gomb, szerkesztés.

- Mezők: kérdés szövege (kötelező, legfeljebb 255 karakter), aktív / inaktív kapcsoló. Az inaktív kérdés nem jelenik meg a felvételnél.
- A sorrend drag and droppal állítható, a felvételnél ebben a sorrendben jönnek a kérdések.
- Kezdő példák (a végleges szöveget a mégis adja):
  - „Ki volt még ott?”
  - „Mit éreztél abban a pillanatban?”
  - „Mit tettél végül?”
  - „Miért volt ez fontos neked?”

## Nincs benne

- Az ET5·b változat (ismételhető tükör) és az A/B teszt: [NM-05](../backlog_nem_MVP/NM-05-ertektukor-ismetles-ab-teszt.md).
- A „Csak elküldöm” (eredmény e-mailben, fiók nélkül): [NM-07](../backlog_nem_MVP/NM-07-ertektukor-csak-elkuldom.md).
- A regisztráció és a belépés: 14.
- A csapat és az egyéni értékszettek az illesztésben.
- Az Értéktár konszolidációja (a hasonló AI által detektált értékek összevonása).

## Nyitott kérdések

1. Melyik szolgáltatás készítse az átiratot beszélő-felismeréssel? A döntést a 12-es jegy PoC-ja (12.7) készíti elő.
