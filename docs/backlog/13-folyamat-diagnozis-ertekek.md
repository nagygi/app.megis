# 13 · Folyamat-diagnózis – értékek az alkalmakból

## Cél

Az alkalmak anyagaiból (12) a 06-os motor kiolvassa, mennyire hordozza a folyamat a szervezet értékeit. Folyamatonként egy **Folyamat szintű diagnózis** áll össze (04), ez adja a riport folyamat-mérőpontjait (R3, R3·b). Ez a jegy köti be a motor illesztését a szervezet értékszettjére (08) is.

## Alfeladatok

### 13.1 Folyamat szint a Diagnózisokban (04)

- **Új szint:** Szervezet / Csapat / Felhasználói / **Folyamat**. Folyamat szintnél a szervezet egy folyamatát kell választani (11).
- **Új diagnózis típus** a kezdő adatok között: **Folyamat**.
- **Sorok:** a szervezet értékei, a diagnózisban rögzített verzióval (08 · 8.4). *A márkád alapja* blokk ennél a szintnél nincs.
- **A folyamat pontja** (PHI 1–6, vagy „–”, ha nincs nyoma) a diagnózis fejlécében áll. Ez a mérőpont.
- **A diagnózis adatai:**
  - Készítő: Automatikus.
  - Státusz: a fő diagnózist követi (17 · 17.4–17.5). A futás alatt Draft, az olvasat indulásától Jóváhagyásra vár. A váltás a részdiagnózis saját naplójába is bekerül.
  - Név: „{Folyamat} · {időszak}”. Amíg nincs feldolgozott alkalom: „{Folyamat} · még nincs alkalom”.
  - Időszak: az első és az utolsó feldolgozott alkalom dátuma. Alkalom nélkül a diagnózis kezdőnapja.
  - Az adatai (szint, folyamat, fő diagnózis, név, időszak) a számításból jönnek, az *Adatok* űrlapon nem írhatók át.
- Folyamatonként egy Folyamat szintű diagnózis készül, a fő diagnózis részdiagnózisaként (17 · 17.4). Ha a folyamat kikerül a fő diagnózisból, a számolt diagnózisa is törlődik.
- Folyamat szintű diagnózis kézzel is felvehető (pl. egy workshop alapján), a 04 szabályai szerint.
  - A folyamatválasztó csak a mért (elfogadott, felső szintű) folyamatokat kínálja.
  - A sorok a szervezet értékei, a hatályos értékszett-verzió rögzítésével. A márka-sorok nincsenek.
  - A pont a sorok pontjainak átlaga, egy tizedesre. Kötelező indoklással átírható.
  - A számítás a kézzel felvett diagnózist nem veszi át és nem írja felül, akkor sem, ha a fő diagnózis részdiagnózisa.

### 13.2 Alkalmanként: a motor (06 · 6.4)

- **Pontozási egység:** a motor anyagonként fut egyszer (átirat, leirat, dokumentum, elmondott helyzet). Külön alkalom-szintű AI-hívás nincs, mert a dupla pontozás kétszer annyiba kerülne, és a két pont eltérhetne (`mvp-terv.md`, 15 · 3. kérdés).
- **Bemenet anyagonként:**
  - az anyag szövege;
  - a folyamat neve, ritmusa és a szülő folyamat neve: ezt a detektálás (A) és a pontozás (C) kapja meg, a helyzet megértéséhez;
  - a szervezet értékei: definíció, az 1 és a 6, példák, a fő diagnózisban rögzített verzióból.
- **A · szabad detektálás:** a 06 szerint, a folyamat-kontextussal.
- **B · illesztés:** a szervezet értékeire is illeszt, mellettük az Értéktárra. Az illesztés itt is csak definíció és példák alapján történik, névegyezés alapján soha. Ezért az érték saját neve a szervezet szövegeiben sem jelenik meg a promptban: helyette „ez az érték” áll.
- **C · pontozás:** a szervezet minden értékére 1–6 vagy „nincs adat”, idézet-hivatkozással. Ha a válasz egy értéket kétszer pontoz, az első számít.
- Egy alkalom értékenként egy pontot ad, akárhány anyag tartozik hozzá: az anyagai pontjainak átlagát, a „nincs adat” nélkül.
- A nem illeszkedő értékek és a nyers detektálás a 06 szerint kezelendők: ami semmihez nem illeszkedett, „AI által detektált” érték lesz. A detektálás az anyagán át az alkalomra és a folyamatra hivatkozik.
- **Besorolatlan és *Egyéb* anyagok:** a besorolatlan („Egyikhez sem”) és az *Egyéb* anyagokat is feldolgozzuk, de Folyamat szintű diagnózisba nem kerülnek.
  - A nyers detektálásuk megmarad.
  - Az olvasatban (20) nyomként használhatók.

### 13.3 Összesítés

- **Értéksor mérőszáma:** az alkalmak pontjainak számtani átlaga, egy tizedesre kerekítve.
  - A „nincs adat” alkalmak kimaradnak.
  - Ha egyik alkalomban sem volt adat, a sor „–” („nincs nyoma”), és nem számít bele a folyamat pontjába (18 · 18.4).
  - Soronként látszik az n (hány alkalomból számoltunk) és a szórás, ahogy a 04 · 4.5-ben.
- **A folyamat pontja:** az értéksorok átlaga, egy tizedesre kerekítve. A kézzel átírt sor az átírt értékével számít.
- **Minimum:** a Teljesben legalább 2 feldolgozott alkalom kell, a Belső snapshotban 1 is elég, „Egy alkalomból” jelzéssel. Ennél kevesebbnél a diagnózis létrejön, de a pont üres („–”), és ez látszik: „Kevés alkalom (1)”.
- **Alfolyamat:** az alkalmai a szülő folyamatba számítanak. A sornál látszik, melyik alfolyamatból jött.
- **Saját megélés:** értékenként a V11·c válaszok átlaga (12.4), a „nem tudom” válaszok nélkül.
  - A sor részleteiben külön látszik a vezető és a társak átlaga, kitöltőszámmal.
  - A PHI-be nem számít, a rést adja (R3·b: „4-nek látjátok, 2-t mutattok”).
- **Eredményesség:** a diagnózis oldalán összesítve, pont nélkül: „6 alkalom · eredményes 3 · részben 2 · nem 1” (R3).
  - Egy alkalomnak egy eredményessége van: a válaszok többsége, a „nem tudom” nélkül. Holtversenyben „részben”.
  - A válasz nélküli alkalom külön szám: „· 1 válasz nélkül”. Így a számok összege az alkalmak száma.
  - Az alkalmak listáján az alkalom eredménye mellett a válaszok bontása is látszik.
- **Mire épül:** a megélés és az eredményesség csak ennek a diagnózisnak a válaszaiból jön, a felhasznált alkalmakról. A visszavont hozzájárulású társ kimarad. A „kitöltő” a különböző emberek száma, nem a válaszoké.
- **Jelzések a soron**, ahogy a 04 · 4.5-ben: 3 alkalom alatt „Kevés adat (n alkalom)”, 1,5 feletti szórásnál „megosztott”. A szórás két tizedesre.
- A márkád alapja négy elemének alkalmankénti megélése az alkalomnál marad, diagnózisba nem kerül.

### 13.4 Frissítés

- Amíg a diagnózis nincs publikálva, minden feldolgozott alkalom után újraszámol. A sikertelen anyag után is, hogy a kimaradt alkalmak listája rögtön frissüljön.
- A kézzel átírt sort és pontot nem írja felül. Ha az automatikus érték eltér, mellette jelzés látszik.
- Publikálás után az új alkalmak nem módosítják a diagnózist, de látszik rajta: „2 új alkalom a publikálás óta”.
  - Új alkalom: a diagnózis által még nem használt alkalom, amelynek a publikálás után jött feldolgozott anyaga. A naptárból előre felvett alkalom is ide tartozik.
  - Egy már felhasznált alkalom új anyaga nem számít új alkalomnak.
- Ha a diagnózist visszavonják Draftba (04), rögtön újraszámol, a közben jött anyagokkal együtt.

### 13.5 Admin: a diagnózis oldala

- Az értéksorok és a folyamat pontja átírható, kötelező indoklással. Rögzítjük, ki és mikor írta át, és mi volt az automatikus érték. A nyom a visszaállítás után is megmarad.
- **Az alkalmak listája:** dátum, anyagok, alkalmankénti pontok, eredményesség, link az átiratra. Soronként az idézetek is látszanak.
- **Az összesítés leírása**, ahogy a 04 · 4.5-ben, a diagnózissal együtt mentve:
  - forrás: a folyamat, az időszak és a felhasznált alkalmak;
  - a kimaradt alkalmak és az okuk, alkalmonként egyszer: feldolgozási hiba, még feldolgozás alatt, visszavont hozzájárulás, nincs anyag. A futás kezdetétől máig tartó alkalmak számítanak, a naptár jövőbeli alkalma még nem;
  - a számítás: számtani átlag, egyenlő súly, legalább 2 alkalom. A szöveg a számításkor mentődik, így egy későbbi módszerváltás után is az eredeti olvasható;
  - ki és mikor írt át kézzel.
- A fejlécben látszik a rögzített értékszett-verzió („Értékszett: v3”).
- **Egyéb detektált értékek:** a felhasznált alkalmak anyagaiból, a sornál az alkalom dátumával és a folyamattal.

## Nincs benne

- Képességmérés: [NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md).
- Beszélőnkénti felhasználói diagnózis a leiratból: [NM-11](../backlog_nem_MVP/NM-11-beszelok-hozzarendelese.md).
- Az olvasat (A3) és a publikálás: 20, 21.
- A riport folyamat-fejezete (R3, R3·b): 27.
- Szervezeti idő, értékkép: [NM-13](../backlog_nem_MVP/NM-13-folyamatok-ertekmeresben.md).
