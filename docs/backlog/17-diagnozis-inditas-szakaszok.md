# 17 · Diagnózis – indítás, részdiagnózisok, szakaszok

## Cél

Az admin elindít egy diagnózist egy csapatra vagy egy magánszemélyre (A1). Ez a **fő diagnózis**: egy 04-es diagnózis, kiegészítve mindazzal, ami a lefutásához kell (vezető, társak, időablak, szakasz). A diagnózis közben keletkező felhasználói és folyamat szintű diagnózisok (06, 08, 13) a fő diagnózis **részdiagnózisai** lesznek: vele együtt hagyja jóvá és publikálja őket az admin (20, 21).

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 17.1 A fő diagnózis

- Egy 04-es diagnózis, **„Fő diagnózis”** típussal (új, nem törölhető kezdő típus a 04 · 4.1-ben).
- **Szint:**
  - csapatnál: Csapat, a szervezet egy csapatával (02);
  - magánszemélynél: Szervezet, a magánszemély típusú szervezeten (02);
  - egy embernél a cégen belül: Felhasználói, a kiválasztott felhasználóra.
- **Termék:** a szervezet terméke (02). Indításkor a diagnózisra is mentődik, így ha később a szervezet terméke változik, a futó és a korábbi diagnózisoké nem.
- **Név:** automatikusan „{Csapat} · {Termék} · {kezdés}”, átírható.
- **Sorok:** a szervezet értékei és a márkád alapja; a részleteket a 18 adja.

### 17.2 Indítás (A1)

Belépő: a *Diagnózisok* lista „+ Új diagnózis” gombja két lehetőséget kínál: „Diagnózis indítása” (A1) és „Korábbi diagnózis rögzítése” (04 · 4.3).

Egy lap, a prototípus A1-e szerint:

- **Szervezet** (kötelező): aktív szervezet.
- **Kit diagnosztizálunk?**
  - Magánszemély típusú szervezetnél (02): magánszemély, nem választható.
  - Szervezet típusnál: **Csapat**, vagy **Egy ember, a cég értékeivel**. Az utóbbi egyfős diagnózis: társ nélkül, a szervezet értékeihez mérve. A riportját csak a felhasználó és a mégis látja (27.1), és a Szervezet nézet átlagába nem számít bele (18.5).
- **Csapat** (csapatnál kötelező): a szervezet csapatai közül, vagy „+ Új csapat” névvel.
- **Vezető** (kötelező):
  - csapatnál a szervezet egy felhasználója, vagy új (név, e-mail), a 02 meghívási szabályaival; a csapatnál „csapat vezető” címkét kap (02 · 2.6);
  - csapatnál alapból a szervezet „szervezet vezetője” címkéjű felhasználója. Ha más lesz a vezető, kis figyelmeztetés jelzi, hogy a szervezet értékeit és a márkád alapját csak olvassa (17.3);
  - magánszemélynél a szervezet egyetlen felhasználója, nem választható;
  - egy embernél maga a diagnosztizált felhasználó.
- **Társak** (csak Teljes, csak csapat):
  - a csapat felhasználói a vezetőn kívül, címkétől függetlenül (munkatárs, partner);
  - csapathoz embert csak az admin rendel (02), a vezető nem hív meg senkit ([NM-16](../backlog_nem_MVP/NM-16-tarsak-meghivasa-vezetotol.md));
  - az A1 listázza őket, és egyenként kivehető, ki ne legyen társ;
  - aki a futás alatt kerül a csapatba, az olvasat indításáig automatikusan társ lesz;
  - a meghívásukat a 25 írja le.
- **A szervezet értékei:** csak „szervezet vezetője” címkéjű felhasználó szerkesztheti (08).
  - Ha a diagnózis vezetője nem ilyen, a 2. és a 3. lépést csak olvassa (17.3).
  - Ha ilyenkor a szervezet értékszettje még üres, a „Meghívó küldése” szürke: „A szervezet értékeit előbb a szervezet vezetője vagy az admin adja meg (08 · 8.5).”
- **Termék:** csak olvasható, mellette link a szervezet szerkesztésére. Snapshot · Külső termékű szervezetnek nem indítható diagnózis, az A1 ezt írja ki: „Ehhez a termékhez még nincs diagnózis-folyamat.”
- **Időablak:** kezdés napja (a vezető kitöltési határideje) és publikálás napja. Az alap hossz: Belső snapshot 14 nap, Teljes 42 nap. Az olvasat a publikálás előtti 4. napon kezdődik (konfigurálható), ezt a vezető csak tájékoztatásul látja (V10). A kezdés napja üres is lehet: ilyenkor a vezető „egyeztetés alatt” feliratot lát (V1).
- **Hozzáférés:** „A társak idézetei a vezetőnek, szereppel és dátummal, név nélkül” kapcsoló, alapból bekapcsolva.
- **Élő előnézet** jobbra: amit a vezető az első belépéskor lát (V1), a 17.3 szerint szűkülő lépésekkel.
- **Gombok:**
  - „Meghívó küldése” (elsődleges): a vezető e-mailt kap, a szakasz „Meghívva” lesz. Ez az 1. kapu: a vezető meghívója csak innen megy ki.
  - „Mentés vázlatként”: e-mail nem megy ki.

### 17.3 Lépések termék és alany szerint

A lépések a V1 sorrendjében.

| Lépés | Belső snapshot, csapat | Belső snapshot, magánszemély | Teljes, csapat | Teljes, magánszemély |
|---|:---:|:---:|:---:|:---:|
| 1 · Saját értékek | ✅ | ✅ | ✅ | ✅ |
| 2 · A szervezet értékei | ✅ | – (a saját értékekből, 08) | ✅ | – |
| 3 · A márkád alapja | ✅ | ✅ | ✅ | ✅ |
| 4 · A megélésed | ✅ | ✅ | ✅ | ✅ |
| Képességek (NM-12) | ✅ 1 helyzet | ✅ 1 helyzet | ✅ 3 helyzet | ✅ 3 helyzet |
| 5 · Folyamatok | ✅ | ✅ | ✅ | ✅ |
| 8 · Ki lát mit | ✅ | ✅ | ✅ | ✅ |

- A 6. lépés (csatornák és assetek) egyeztetés alatt áll, MVP-ben sehol nem jelenik meg.
- A 7. lépés (társak) sincs: a társakat az admin rendeli a csapathoz ([NM-16](../backlog_nem_MVP/NM-16-tarsak-meghivasa-vezetotol.md)).
- Ha a vezető nem „szervezet vezetője”, a 2. és a 3. lépés csak olvasható (23).
- Egy ember a cég értékeivel (17.2): a csapat lépései, a 7. lépés nélkül.
- A táblázat konfigurációban van; az A1 előnézete és a V1 ugyanebből olvas.
- A Képességek lépés csak akkor van ott, ha a képességmérés be van kapcsolva, globálisan és a terméknél is (1.32). Kihagyható, mint a Folyamatok.

### 17.4 Részdiagnózisok

A meghívó kiküldésétől az olvasat indításáig a fő diagnózis részdiagnózisa lesz:

- a vezető és a társak Értéktükre (06 · 6.2), ha ebben az időszakban készül;
- a vezető megélése (08 · 8.3, V5) és a társak megélése (T4, 25);
- a folyamatok diagnózisai (13);
- a kézzel rögzített (04 · 4.3) és az admin által feltöltött (06 · 6.3) diagnózis, ha az admin az űrlap „Fő diagnózis” mezőjében kiválasztja. Így a mégis interjúi és a workshopok is bizonyítékok lesznek.

Szabályok:

- A fő diagnózis oldalán *Részdiagnózisok* fül: név, szint, típus, felhasználó vagy folyamat, státusz.
- A részdiagnózist nem kell külön jóváhagyni. A fő diagnózissal együtt lesz „Publikált” (21), és vele együtt vonható vissza.
- A fő diagnózisnál az aggregálás a saját részdiagnózisokból számol, nem a „legutolsó publikált” felhasználói diagnózisból (04 · 4.5). A részletek: 18.

### 17.5 Szakaszok

| Szakasz | Mikor lép ide | 04-es státusz |
|---|---|---|
| Vázlat | „Mentés vázlatként” | Draft |
| Meghívva | „Meghívó küldése” | Draft |
| Kitöltés | a vezető első belépése | Draft |
| Fut | a vezető a V9-en „Indul a diagnózis” (23) | Draft |
| Olvasat | az admin „Olvasat indítása” gombja (19) | Jóváhagyásra vár |
| Publikálva | „Publikálom” (21) | Publikált |
| Lezárva | az admin lezárja, az átbeszélés után | Publikált |

- Minden szakaszváltás rögzül: ki és mikor.
- A „Fut” a V9 gombjával indul, akkor is, ha a kezdés napja még nem jött el. Ha a vezető a kezdés napjáig nem indította el, a Beérkezőben sor jelenik meg (22).
- Az „Olvasat” szakaszt az admin indítja a Futás lapon (19). Ekkor a vezetőnek és a társaknak lezárul a feltöltés, a kitöltés és a megélés. Dátum alapú automatikus váltás nincs.
- A „Fut” kezdetén a társak megkapják a meghívójukat (25).
- A *Diagnózisok* listán (04 · 4.2) új oszlop és szűrő: szakasz. Ez adja az MVP-s áttekintőt (19).

### 17.6 Párhuzamos diagnózisok

- Egy szervezetben egyszerre több fő diagnózis is futhat, csapatonként egy.
- Egy ember több futó diagnózisban is részt vehet, más-más szereppel: az egyikben vezető, a másikban társ, vagy két csapatban is társ. A részleteket a 25.7 és a 26.4 adja.
- A kitöltés lapjai (V2–V9, T1–T4) mindig egy diagnózison belül futnak, ott nincs kétség.
- A saját menüből indított Értéktükörnél, ha a felhasználónak több futó diagnózisa van, a felület megkérdezi: „Melyik diagnózishoz tartozzon?”
- Az admin-feltöltésnél az űrlap „Fő diagnózis” mezője dönt (17.4).

### 17.7 Módosítás a meghívó után

- Az olvasat indításáig módosítható az időablak, a társak listája és az idézetek láthatósága.
- A vezető megerősítéssel cserélhető, a vezető melletti „Szerkesztem” gombbal:
  - a régi vezető kitöltése megmarad;
  - az új vezető nem kap automatikusan e-mailt. A meghívót a csere után az „Elküldöm a meghívót az új vezetőnek” gomb küldi el;
  - ha az új vezető nem „szervezet vezetője”, ugyanaz a figyelmeztetés jelenik meg, mint az indításnál.
- A csapat és a szervezet nem módosítható: ilyenkor a diagnózist le kell zárni, és újat indítani.

### 17.8 Diagnózisok fül a szervezeten

- A szervezet oldala (02) hatodik fület kap: *Diagnózisok*, a szervezet fő és kézi diagnózisaival (név, típus, termék, szakasz, dátum).
- „Diagnózis indítása” gomb: az A1 a szervezettel előre kitöltve nyílik.

## E-mailek

- **Vezető, új felhasználó:** „{Admin} elindította a {Csapat} diagnózisát a mégis-ben, és téged kért meg, hogy vezesd. Kb. {perc} perc, {kezdés}-ig. [Kezdjük]” A link a regisztrációra visz (14).
- **Vezető, meglévő felhasználó:** „Elindult a {Csapat} diagnózisa a mégis-ben. Kb. {perc} perc, {kezdés}-ig. [Kezdjük]”

## Nincs benne

- A V1 és a kitöltés kerete: 23. A társ útja: 25.
- Tartalom és számítás: 18. Futás, olvasat, publikálás, Beérkező: 19–22.
- A társak meghívása a vezetőtől (V8): [NM-16](../backlog_nem_MVP/NM-16-tarsak-meghivasa-vezetotol.md).
- Csatornák (V7) és Snapshot · Külső: egyeztetés alatt.
- Képesség, Bridge to Purpose, átbeszélés (A6): [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- A Coach és a Coach-kapcsoló az A1-ben: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- Több vezető egy csapatban.
