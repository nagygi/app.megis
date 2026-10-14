# 04 · Admin felület – Diagnózisok

## Cél

Az admin rögzíti a korábbi diagnózisok eredményeit, például egy offline workshopét vagy egy online értékmérését, egy szervezethez, csapathoz vagy felhasználóhoz. Egy diagnózis azt rögzíti, mely értékek és mennyire jellemzők az adott szervezetre, csapatra vagy felhasználóra.

Később a diagnózisok az értékmérés folyamatából automatikusan is létrejönnek. A felhasználók csak a publikált diagnózisokat látják.

Szuperadmin és Admin egyaránt használhatja (lásd 01).

## A két mutató

| Mutató | Skála | Honnan |
|---|---|---|
| **Mérőszám**: mennyire jellemző | PHI 1–6: 1 nincs · 2 csak szavak · 3 ad-hoc · 4 néha · 5 többnyire · 6 beépült. Ha nincs nyoma: „–”, ez nem szám, és nem számít bele az átlagokba (18 · 18.4) | BT4 |
| **Saját megélés**: mennyire jellemző | 1–6 vagy „nem tudom” | V5, T4 |

- Kézi felvitelnél a mérőszám egész szám, az aggregált érték egy tizedesre kerekített.
- A kettő különbsége a **rés**. A diagnózis táblázatában csak olvasható oszlopként látszik („5-nek látjátok, 2-t mutattok”).

## Alfeladatok

### 4.1 Diagnózis típusok

Menü: *Diagnózisok → Diagnózis típusok*. Lista, „+ Új típus” gomb, szerkesztés.

- Mezők: név (kötelező, egyedi), leírás (opcionális).
- Kezdő adatok: Kezdő diagnózis, Workshop, Értéktükör, Megélés, Folyamat.
- Törölni csak olyan típust lehet, amelyikhez nem tartozik diagnózis.

### 4.2 Diagnózisok lista

Menü: *Diagnózisok*. A lista az NFR listaszabályai szerint működik.

- Oszlopok: név, szervezet, szint (és a csapat, a felhasználó vagy a folyamat neve), diagnózis típusa, diagnózis ideje, készítő, státusz.
- Kereső: név.
- Szűrők: szervezet, szint, diagnózis típusa, készítő, státusz.
- „+ Új diagnózis” gomb.

### 4.3 Diagnózis felvitele és szerkesztése

- **Név** (kötelező).
- **Leírás** (opcionális).
- **Diagnózis ideje** (kötelező): egy dátum. Az „Időszak” kapcsolóval kezdő és záró dátum adható meg, ilyenkor a záró dátum is kötelező.
- **Szervezet** (kötelező): aktív szervezet.
- **Fő diagnózis** (opcionális): a szervezet „Fut” vagy „Olvasat” szakaszú fő diagnózisai közül. Ha ki van választva, ez a diagnózis annak részdiagnózisa lesz (17 · 17.4).
- **Szint** (kötelező): Szervezet / Csapat / Felhasználói / Folyamat.
  - Csapat szintnél a szervezet csapataiból kell választani egyet.
  - Felhasználói szintnél a szervezet felhasználói közül kell választani egyet.
  - Folyamat szintnél a szervezet folyamatai közül kell választani egyet (11). A sorok a szervezet értékei, *A márkád alapja* blokk nincs (13).
  - Magánszemély típusú szervezetnél (02) a Csapat szint nem választható.
- **Diagnózis típusa** (kötelező): a 4.1-es listából.
- **Készítő**: a rendszer állítja be, nem választható.
  - **Admin**: kézi felvitel, a felvivő admin nevével.
  - **Aggregált**: az adatok betöltésével jött létre (4.5).
  - **Automatikus**: az Értéktükörből vagy admin feltöltésből (06), illetve a folyamatok alkalmaiból (13). Ilyenkor a diagnózis „Jóváhagyásra vár” státusszal jön létre.
- **Státusz**: Draft → Jóváhagyásra vár → Publikált.
  - Bármelyik admin léptetheti, a saját diagnózisát is.
  - A publikált diagnózis visszavonható Draftba. Ilyenkor eltűnik a felhasználóknál, javítható, majd újra publikálható.
  - Minden státuszváltás rögzül és látszik: ki és mikor léptette.

### 4.4 Értékek és a márkád alapja a diagnózisban

A diagnózis oldalán egy táblázat két blokkal.

**Értékek**, egy sor egy érték:

- „+ Érték” gomb: választás az Értéktár aktív értékei közül (03).
- Ha az érték nincs az Értéktárban, a nevét beírva új érték vehető fel. Ez „Diagnózisnál felvett” címkével kerül az Értéktárba (03 · 3.7).
- A hozzárendelés rögzíti az érték aktuális verzióját.
- Egy érték egy diagnózisban csak egyszer szerepelhet.

**A márkád alapja**, fix négy sor: Értékek (gyökér) · Purpose (törzs) · Értékajánlat (korona) · Impact (árnyék). Mindegyik kitöltése opcionális.

**Soronként rögzített adatok** (értékeknél és a fa elemeinél is)

- Mérőszám, 1–6 vagy „–” (nincs nyoma). Kötelező, kivéve a **Megélés** típusú diagnózist (08), ahol csak a saját megélés van kitöltve.
- Saját megélés (mind opcionális):
  - mennyire jellemző rá: 1–6 vagy „nem tudom”;
  - szöveges példa, mikor teljesült nála;
  - szöveges példa, mikor nem teljesült nála.
- Indoklás (opcionális szöveg).
- Hozzárendelés ideje: alapból a diagnózis ideje, átírható.

### 4.5 Aggregált adatok betöltése

Csapat és Szervezet szintű diagnózisnál „Aggregált adatok betöltése” gomb jelenik meg. Az adatok kézzel is felvihetők, a betöltés nem kötelező.

**Algoritmus**

1. **Forrás**
   - Csapat szintű diagnózisnál a csapat aktív felhasználói, szervezeti diagnózisnál a szervezet összes aktív felhasználója.
   - Felhasználónként a **legutolsó publikált felhasználói diagnózis** számít, ha legfeljebb **12 hónappal** régebbi a diagnózis idejénél.
   - Mindenki egyszer és egyenlő súllyal számít, akkor is, ha több csapatban van.
2. **Számítás**, értékenként és fa-elemenként külön:
   - Mérőszám: a felhasználói mérőszámok számtani átlaga, egy tizedesre kerekítve.
   - Saját megélés: az 1–6 válaszok átlaga. A „nem tudom” válaszok kimaradnak.
   - Minden sor mellett látszik az **n** (hány felhasználó adatából számoltunk) és a **szórás**.
   - Ha a szórás nagyobb, mint 1,5, a sor „megosztott” jelzést kap: a felhasználók nagyon eltérően látják.
   - Az értékeket verziótól függetlenül vonjuk össze. A hozzárendelés a legfrissebb verziót rögzíti.
   - A szöveges mezők (példák, indoklás) üresen maradnak.
3. **Előnézet**: a betöltés előtt egy táblázat mutatja soronként az n-t, az átlagot, a szórást és a figyelmeztetéseket. Alatta „Betöltöm” gomb.
4. **Betöltés után**
   - A sorok kézzel módosíthatók. A módosított sor „kézzel módosítva” jelet kap.
   - Újratöltés előtt megerősítést kér, mert minden sort felülír.

**Az aggregálás leírása a felületen**

Az előnézetben, és betöltés után a diagnózis oldalán is, egy doboz írja le pontosan, hogyan számoltunk:

- **Forrás:** szint, időablak (dátumtól dátumig), hány felhasználó adata került bele, és a forrás diagnózisok listája linkkel.
- **Kimaradt felhasználók** és az okuk: nincs publikált diagnózisa, a diagnózisa 12 hónapnál régebbi, vagy inaktív.
- **Számítás:** számtani átlag egy tizedesre, egyenlő súllyal; a „nem tudom” válasz kimarad.
- **Küszöbök:** n < 3, szórás > 1,5, lefedettség < 50%.
- **Betöltés:** ki és mikor töltötte be.

A leírás a diagnózissal együtt mentődik. Ha később változik az algoritmus, a régi diagnózisnál is az eredeti módszer olvasható.

**Figyelmeztetés kevés adatnál**

- Soronként: ha **n < 3**, ez jelenik meg: „Kevés adat (2 felhasználó)”. A 3 a prototípus küszöbe a csapat-átlaghoz.
- A diagnózis egészére: ha az aktív felhasználók kevesebb mint felének van felhasználható diagnózisa, ez jelenik meg: „Alacsony lefedettség: 4 / 12 felhasználó”.
- A figyelmeztetés nem tiltja a betöltést vagy a publikálást.

## Nincs benne

- A márkád alapja további öt eleme (talaj, micélium, évgyűrűk, a kör, illat): [NM-14](../backlog_nem_MVP/NM-14-fa-tovabbi-ot-eleme.md).
- A fő diagnózis (A1), a részdiagnózisok és a szakaszok: 17. A fő diagnózis tartalma és számítása: 18.
- A diagnózisok megjelenítése a felhasználóknál: 26–29.
- Automatikus diagnózis az Értéktükörből és feltöltésből: 06.
- Súlyozás a diagnózisok frissessége szerint.
