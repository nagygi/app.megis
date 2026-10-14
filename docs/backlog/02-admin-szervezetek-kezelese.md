# 02 · Admin felület – szervezetek, csapatok, felhasználók

## Cél

Az admin felveszi a szervezetet, hozzárendel egy terméket, létrehozza a csapatokat, és meghívja a felhasználókat. Az A1 setup (diagnózis indítása) erre épül: ott már egy meglévő szervezetből és annak felhasználói közül választ.

A szervezet terméke (Snapshot · Külső, Snapshot · Belső, Teljes) adja a diagnózis termékét: indításkor a diagnózisra is mentődik (17).

Szuperadmin és Admin egyaránt használhatja (lásd 01).

## Alfeladatok

### 2.1 Termékek

Bal oldali menüpont: *Termékek*. Lista, „+ Új termék” gomb, szerkesztés.

- Mezők: név (kötelező, egyedi), leírás (opcionális).
- Kezdő adatok (seeder): Snapshot · Külső, Snapshot · Belső, Teljes.
- Törölni csak olyan terméket lehet, amelyikhez nem tartozik szervezet.
- A termék tartalmát és folyamatait később fejtjük ki. Most csak a név és a leírás kell.

### 2.2 Szervezetek lista

Bal oldali menüpont: *Szervezetek*. A lista az NFR listaszabályai szerint működik.

- Oszlopok: név, típus, termék, felhasználók (pl. „8 / 10”, limit nélkül „8”), kapcsolattartó, állapot.
- Kereső: név és slug.
- Szűrők: típus, termék, kapcsolattartó, állapot.
- „+ Új szervezet” gomb.
- Sorműveletek: szerkesztés, inaktiválás / aktiválás.

### 2.3 Szervezet felvitele és szerkesztése

**Mezők**

- **Típus** (kötelező): Szervezet / Magánszemély. Csak létrehozáskor választható.
- **Név** (kötelező).
- **Slug** (kötelező, egyedi), ez adja a szervezet kliens oldali URL-jeit (`/{slug}/…`):
  - a névből képződik automatikusan: kisbetűs, ékezetek nélkül, kötőjellel;
  - alapból zárolt mező, a „Szerkesztés” gombbal írható át;
  - nem egyezhet meg rendszer-útvonallal (pl. `admin`, `login`, `api`);
  - módosításkor figyelmeztetés jelenik meg: „A régi linkek nem működnek tovább.”
- **Leírás** (opcionális).
- **Kapcsolattartó** (opcionális): egy aktív admin felhasználó kiválasztása.
- **Termék** (kötelező).
- **Létszám limit** (opcionális, pozitív egész szám):
  - ha üres, nincs limit;
  - nem állítható a jelenlegi létszám alá.

**Magánszemély típus**

- Nincs csapat.
- Pontosan egy felhasználója van, aki automatikusan „szervezet vezetője” címkét kap.
- A limit fixen 1, a limit mező nem jelenik meg.

**Megjelenés**

- A szervezet oldala hat fülből áll: *Adatok · Csapatok · Felhasználók · Értékek · Folyamatok · Diagnózisok*. Az *Értékek* fül a 08-as, a *Folyamatok* fül a 11-es, a *Diagnózisok* fül a 17-es jegyben készül.
- A szervezeten belül a fejléc a **„szervezet beállításai” színt** kapja, és mutatja a szervezet nevét. A Szervezetek lista még a globális admin színű.

**Inaktiválás:** az inaktív szervezet felhasználói nem érik el a szervezet adatait. A szervezet nem törlődik, visszaaktiválható.

### 2.4 Csapatok

A szervezet *Csapatok* fülén. Magánszemély típusnál ez a fül nem jelenik meg.

- Lista oszlopai: név, leírás, felhasználók száma, csapatvezető(k).
- Mezők: név (kötelező, a szervezeten belül egyedi), leírás (opcionális).
- Csapat törlése megerősítéssel. A felhasználók a szervezetben maradnak, csak a csapathoz rendelésük szűnik meg.

### 2.5 Felhasználók és meghívás

A szervezet *Felhasználók* fülén. A fül tetején a létszám látszik: „8 / 10 felhasználó”.

**Lista**

- Oszlopok: név, e-mail, pozíció, címkék, csapat(ok), állapot (Felvéve / Meghívva / Meghívó lejárt / Aktív / Inaktív), utolsó belépés.
- „Felvéve” állapotú sorban jól látható **„Meghívó küldése”** gomb van.
- Sorműveletek: szerkesztés (pozíció, címkék, csapatok), meghívó küldése vagy újraküldése, inaktiválás / aktiválás, belépés a nevében (14 · 14.6). A név az adatlapra nyílik (33).
- Ha a felhasználónak küldött levél nem kézbesíthető, a sorban „A levél nem kézbesíthető” jelzés látszik ([docs/uzemeltetes.md](../docs/uzemeltetes.md)).

**Meghívás**

A „+ Felhasználó meghívása” gombbal, vagy egy csapat oldaláról. Utóbbi esetben a csapat előre ki van választva.

- **Mezők:**
  - E-mail (kötelező).
  - Címkék (legalább egy kötelező): szervezet vezetője · munkatárs · partner.
  - Csapat(ok) (opcionális, több is választható). Csapatonként beállítható, hogy a felhasználó a csapat vezetője-e.
  - Pozíció (opcionális): autocomplete a korábban megadott pozíciókból, új is beírható.
  - Vezetéknév, keresztnév (opcionális). Ha hiányzik, a felhasználó a regisztrációkor adja meg.
- **„Meghívó küldése most”** kapcsoló, **alapból kikapcsolva**.
  - Kikapcsolva: a felhasználó „Felvéve” állapotba kerül, e-mail nem megy ki. A meghívót később a lista „Meghívó küldése” gombjával lehet elküldeni.
  - Bekapcsolva: a meghívó e-mail mentéskor azonnal kimegy, az állapot „Meghívva” lesz.
- A regisztráció és a link kezelése (14 napos lejárat) a 14-es jegyben van.
- Ha az e-mail már létezik a rendszerben (pl. egy másik szervezetből), nem jön létre új fiók. A meglévő felhasználó kerül a szervezetbe. Ő meghívó helyett értesítő e-mailt kap, ugyanúgy a kapcsoló vagy a „Meghívó küldése” gomb szerint.
- Egy felhasználó több szervezetben is lehet, és szervezeten belül több csapatban is. A pozíció és a címkék szervezetenként külön állíthatók.

**Létszám limit**

- Beleszámít minden felvett, meghívott és aktív felhasználó, a partnert is beleértve. Az inaktív felhasználó nem számít bele.
- A limit elérésekor a meghívás gomb szürke, ezzel az indokkal: „Elértétek a létszám limitet (10 / 10).”
- Inaktív felhasználót is csak akkor lehet visszaaktiválni, ha van szabad hely.

**Inaktiválás**

- A felhasználó nem éri el a szervezetet.
- A diagnózisokban rögzített adatai megmaradnak.
- Végleges törlés nincs.

### 2.6 Címkék és jogosultság

A címkék jogosultságot is adnak a kliens oldalon:

| Címke | Hol állítható | Mit lát (részletek a kliens oldali jegyekben) |
|---|---|---|
| Szervezet vezetője | a szervezetnél | a szervezeti nézetet |
| Csapat vezető | a csapatnál | a saját csapata(i) nézetét |
| Munkatárs | a szervezetnél | a saját nézetét |
| Partner | a szervezetnél | a saját nézetét |

Egy felhasználónak több címkéje is lehet.

## E-mailek

- **Meghívó (új felhasználó):** „{Admin} meghívott a mégis-be, a {Szervezet} felhasználójaként. [Regisztráció]”
- **Hozzáadás (meglévő felhasználó):** „Hozzáadtunk a {Szervezet} szervezethez a mégis-ben. [Belépés]”

## Nincs benne

- Regisztráció és a meghívó link kezelése (lejárat): 14.
- A termék tartalma és folyamatai: később.
- Számlázás: [NM-02](../backlog_nem_MVP/NM-02-szamlazas.md).
- CSV import: [NM-03](../backlog_nem_MVP/NM-03-felhasznalo-csv-import.md).
- Regisztrációs emlékeztető: [NM-04](../backlog_nem_MVP/NM-04-regisztracios-emlekezteto.md).
- A szervezet értékei és a márkád alapja: 08.
- A vezető által küldött társ-meghívó (V8): [NM-16](../backlog_nem_MVP/NM-16-tarsak-meghivasa-vezetotol.md).
- Szervezetválasztó a kliens oldalon: 15.
- Végleges törlés.
