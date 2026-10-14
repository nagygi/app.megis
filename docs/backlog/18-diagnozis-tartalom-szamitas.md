# 18 · Diagnózis – tartalom és pontszámítás

## Cél

Mi kerül a fő diagnózisba (17), és hogyan áll elő minden szám és mondat, amit a riport mutat: a mérőpontok, a PHI, a %, a rés, a bizonyíték, az állítások és az olvasat. Az értékeknél a rendszer számol és AI-vázlatot ad, a területeket és a fát az admin pontozza kézzel. Mindenről a mégis dönt az olvasatban (20).

## Alfeladatok

### 18.1 Mérőpontok

A fő diagnózis sorai négy blokkban, a riport sorrendjében:

| Blokk | Sorok | Teljes | Belső snapshot |
|---|---|---|---|
| Értékek | a szervezet értékei (08), a diagnózisban rögzített értékszett-verzióval | 3–5 | 3–5 |
| Folyamatok | a diagnózisban mért folyamatok (11), a pontjuk a Folyamat-diagnózisból (13) | ahány mért | 1–2 |
| Területek | Stratégia · Kultúra és közösség · Értékajánlat · Kommunikáció · Növekedés és tanulás | 5 | – |
| A márkád alapja | Értékek (gyökér) · Purpose (törzs) · Értékajánlat (korona) · Impact (árnyék) | 4 | 4 |

- Példa: Teljes, 5 érték és 5 folyamat esetén 19 mérőpont; Belső snapshot, 5 érték és 2 folyamat esetén 11. A Belső snapshotban legfeljebb 2 folyamat mérhető (11), területblokk nincs.
- A fő diagnózis értéksorai nem az Értéktárból jönnek (04 · 4.4), hanem a szervezet értékszettjéből (08 · 8.4). A „+ Érték” gomb itt nem jelenik meg.
- A területek fix listája kezdő adat. A szövegüket a mégis az adminban szerkeszti: *Beállítások → Területek*, területenként a „Mit nézünk” mondat, a „nyoma” (miből olvassuk) és a 6-os leírása. A riport (R4·b) és a Bölcsességtár (BT3) innen olvas. A *Beállítások* menüt az Admin is eléri (05).
- A fa további öt eleme: [NM-14](../backlog_nem_MVP/NM-14-fa-tovabbi-ot-eleme.md).

### 18.2 Soronként rögzített adatok

A 04 · 4.4 mezőin felül:

- **PHI** (a 04 mérőszáma, 1–6, vagy „–”, ha nincs nyoma): az értékeknél és a folyamatoknál a számításból, a területeknél és a fánál kézzel (18.4). Az olvasatban mindegyik átírható (20).
- **Megélés:** az értékeknél és a fa elemeinél a V5 és a T4 alapján, a folyamatoknál a V11·c alapján (13.3). A területeknél nincs megélés és rés.
- **Rés:** megélés és PHI különbsége, csak olvasható (04).
- **n és szórás:** hány anyagból vagy alkalomból számoltunk (04 · 4.5).
- **Bizonyíték:** a nyomok listája. Egy nyom adatai:
  - idézet vagy megfigyelés (pl. „A retró jegyzetében a »mi nem sikerült« pont üres, 2 alkalomból 2-szer.”);
  - forrás típusa: beszélgetés, felvétel, jegyzet, dokumentum, Értéktükör, naptár, megélés;
  - az anyag és az alkalom, linkkel;
  - szerep (vezető, társ, külsős), dátum. A név csak az adminnál látszik.
- **Mit nézünk:** a folyamatoknál és a területeknél egy mondat a sor mércéjéről (R3·b, R4·b).

### 18.3 Állítások és olvasat

- **Állítás:** egy mondat a riportban, bizonyítékkal.
  - Típus: erősség, repedés vagy sejtés. A Belső snapshotban a repedés neve „töréspont”.
  - Mindig egy sorhoz tartozik, és legalább egy nyom kell hozzá.
  - Sejtésnél kiírjuk, hány nyomra épül („Kevés még a nyom: 2 felvétel.”).
  - Darabszám-korlát nincs: annyi állítás kerül a riportba, amennyit az admin jóváhagy.
- **A legnagyobb rés:** az az érték vagy fa-elem, amelyiknél a legnagyobb a megélés és a PHI különbsége. Automatikusan számolódik, az olvasatban felülírható.
- **Az olvasat szövegei:**
  - vezetői összefoglaló (R1), blokkonként egy mondat;
  - a márkád alapjának olvasata (R5);
  - a mégis javaslata: egy mondat és a mérőpont, amit mozdítana („Vezetői kör · 2 → 3”). A Teljesben a vezetői összefoglaló végén áll („Hol kezdenénk”), a Belső snapshotban önálló blokk (S1), és ugyanez jelenik meg a zárólapon (27) és az Útravalóban (29).
- Az állítások és az olvasat szövegei AI-vázlatként érkeznek, az admin átírja. Csak jóváhagyás után kerülnek a riportba (20).

### 18.4 Számítás

- **Értékek:** a 06-os motor a fő diagnózis összes anyagán fut, a szervezet értékeire illesztve (13 · 13.2).
  - **Az anyagok:**
    - alkalmak anyagai (12) és elmondott helyzetek (12.3);
    - besorolatlan és *Egyéb* anyagok (13.2);
    - a diagnózis ideje alatt készült Értéktükrök (17.4);
    - a részdiagnózisként csatolt interjúk és feltöltések (17.4).
  - Anyagonként és értékenként 1–6 vagy „nincs adat”.
  - A sor PHI-je az anyagok pontjainak számtani átlaga, egy tizedesre. Ha egyik anyagban sincs adat, a sor „–” („nincs nyoma”): ez nem szám, és nem számít bele a gyűrű-%-ba és a lépcsőbe, így a mérőpontok száma diagnózisonként eltérhet.
- **Folyamatok:** a Folyamat-diagnózis pontja (13 · 13.3).
- **Területek:** kézzel. Az admin az olvasatban (20) adja meg a PHI-t, és a diagnózis nyomai közül választja ki a bizonyítékot. Az AI-pontozás: [NM-17](../backlog_nem_MVP/NM-17-ai-pontozas-teruletek-fa.md).
- **A márkád alapja:** kézzel, ugyanígy. Az elem mércéje a vezető válasza (V4).
- **Megélés és rés:** a csapat-megélés a vezető (V5) és a társak (T4) megélés-részdiagnózisainak egyenlő súlyú átlaga (a vezető egy főnek számít), a „nem tudom” válaszok nélkül; a rés ennek és a PHI-nek a különbsége. Magánszemélynél és az egyfős, cégen belüli diagnózisnál (17.2) a rés az önkép (V5) és a helyzetenkénti megélések (V11·c) átlaga között van. Soronként külön is látszik a vezető és a társak átlaga, kitöltőszámmal („vezető 5 · társak 5 (4 kitöltő)”).
- **Gyűrű-%:** a sorok PHI-jének összege osztva a mérőpontok számának hatszorosával, a „–” sorok nélkül, egészre kerekítve. Az optimális állapot 80%, ez átlag 4,8. Egy gyűrű van (Belső működés), a fa kívül-oldala sehol nem jelenik meg, szaggatott helyőrzőként sem.
- **Lépcső:** az átlagos PHI lefelé kerekítve (1 nincs … 6 beépült).
- **Kerekítés a riportban:** soronként egészre (a ,5 felfelé), az összesítőknél egy tizedes, a % egész. A % és a lépcső a kerekítetlen értékekből számol. A kézi pontok egészek.
- Az AI-eredmények modell- és promptverzióval mentődnek (16).
- **Frissítés:** a 13 · 13.4 szerint.
  - Amíg a diagnózis nincs publikálva, minden feldolgozott anyag után újraszámol.
  - A kézzel átírt sort nem írja felül, csak jelzi az eltérést.
  - Publikálás után nem változik.

### 18.5 Nézetek: Egyén · Csapat · Szervezet

A riport nézetváltójának adatai (a megjelenítés: 27):

- **Csapat** (alapnézet): a fő diagnózis sorai.
- **Egyén:** a PHI a néző saját Értéktükreiből és az általa feltöltött és elmondott anyagokból jön (a vezető feltöltött meetingjei is, akkor is, ha mások is beszélnek benne), a megélés a saját V5 vagy T4. Ha 2-nél kevesebb ilyen anyag van, a sor „nincs elég adat”. A felületen „a te anyagaidból” felirat áll.
- **Szervezet:** a szervezet publikált fő diagnózisai, csapatonként a legutolsó, legfeljebb 12 hónapos (a 04 · 4.5 ablaka). Csak a közös sorok számítanak: értékek (azonos név esetén, verziótól függetlenül), területek, fa; a folyamat-blokk nem jelenik meg. Az egyfős, cégen belüli diagnózis nem számít bele. Amíg csak egy diagnózis van, egyezik a Csapattal.
- Magánszemélynél nincs Csapat nézet, az Egyén és a Szervezet ugyanaz.

### 18.6 Küszöb

- Ha a vezetőn kívül 3-nál kevesebb társ töltötte ki a megélést (T4), az ügyfél nem lát csapat-megélést és rést. A vezetővel együtt ez 4 fő, ahogy a mégis Képességrendszere is rögzíti.
  - A sorban szürkén ez áll: „Még {n} társ hangja kell, hogy lássátok a rést.”
  - A nézetváltón a Csapat szürke.
- Az admin a Futás lapon dönthet úgy, hogy a diagnózis „csapat-szám nélkül megy” (19). Ilyenkor a riport a PHI-t mutatja, a csapat-megélés és a rés helyén a szürke szöveg marad („Még {n} társ hangja kellett volna, hogy lássátok a rést.”), a Csapat nézet szürke, és a vezető a saját megélését az Egyén nézetben látja. Az ellenőrzőlista (21) ezt „tudatosan felülírva” sorként fogadja el.
- Ha egy társ visszavonja a hozzájárulását, az adatai kikerülnek a számításból, és a sorok újraszámolódnak.

## Nincs benne

- A fa további öt eleme: [NM-14](../backlog_nem_MVP/NM-14-fa-tovabbi-ot-eleme.md).
- A területek és a fa AI-pontozása: [NM-17](../backlog_nem_MVP/NM-17-ai-pontozas-teruletek-fa.md).
- Képesség-jelöltek, lépcső, Bridge: [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- A Brand-oldal (értékek a megjelenéseken, csatornák, fix assetek, a fa kívül-pontjai): egyeztetés alatt.
- Az olvasat felülete: 20. A riport lapjai: 27.
- Beszélőnkénti bontás: [NM-11](../backlog_nem_MVP/NM-11-beszelok-hozzarendelese.md).
