# 07 · Admin felület – Képességtár

## Cél

Globális képességtár, ahol az admin látja és karbantartja a mégis képességeit. Erre épül később a képességmérés ([NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md)) és a riport képesség-fejezete ([NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md)).

A tartalom mestere a mégis „Képességrendszer · tárak” Google Sheetje: a mégis ott építi és hagyja jóvá a képességeket. Az app ezt tölti be (7.6). A képesség rekordja más, mint az értéké: a mezők a mégis Részletes leírását követik (v0.3.3, 5. pont; összefoglaló: `docs/kepessegrendszer_osszefoglalo.md`).

Szuperadmin és Admin egyaránt használhatja (lásd 01).

## Alfeladatok

### 7.1 Képességek lista

Bal oldali menüpont: *Képességtár*. Almenük: *Képességek*, *Csoportok*. A lista az NFR listaszabályai szerint működik.

- Oszlopok: azonosító, név, csoport, elsődleges szint, státusz, verzió, utolsó módosítás, állapot (Aktív / Archivált).
- Kereső: azonosító, magyar és angol név, szinonimák és kulcsszavak.
- Szűrők: csoport, elsődleges szint, státusz, jelleg, állapot.
- Sorműveletek: szerkesztés, verziók, archiválás / visszaállítás.

### 7.2 Képesség adatlapja és szerkesztése

- **Azonosító** (kötelező, egyedi): RC-xx a mégis 72-es listájából, ÚJ-xx az új képességeknél. Létrehozás után nem változik, a név igen. Az adminban és az AI-nál látszik, a felhasználói oldalon nem (M3).
- **Név** magyarul (kötelező, egyedi) és angolul (opcionális).
- **Slug**: mint az Értéktárban (03 · 3.2).
- **Csoport** (kötelező): a 7.4 listájából.
- **Elsődleges szint** (kötelező): egyén / csapat / szervezet / ökoszisztéma.
- **Státusz** (kötelező): használt / jelölt / csak irodalom / félretett. A mégis állítja: azt mutatja, mennyire igazolt a képesség valós esetekben.
- **Jelleg**: képesség / készség-jellegű / kérdéses.
- **Csábító tétel**: igen / nem. Igen, ha szinte bármire ráhúzható; a mérésben szigorúbb küszöböt kap.
- **Rövid definíció** (kötelező): egy mondat arról, mit tud ismételhetően, akinél megvan.
- **Kimondható forma** (opcionális): „Mostantól tudunk X-et, Y nélkül.”
- **Mi nem ez** és **Átfedés** (opcionális listák): összetéveszthető viselkedések és képességek, és miben különböznek. Ebből dönt a mérés a szomszédos képességek között.
- **Megjelenés** (opcionális): szintenként (egyén, csapat, szervezet, ökoszisztéma) egy-egy szöveg.
- **Szükséges előfeltételek** (opcionális, többes választás): az E1–E9 listából (7.6), és ha kell, egy másik képesség „képesség-függőségként”.
- **Lépcsők** (opcionális): a hat lépcső (Csak szavak … Továbbadjuk) egy-egy mondata erre a képességre szabva.
- **Szinonimák, kulcsszavak** (opcionális lista): ezekből ismeri fel később az AI a képességet egy szövegben.
- **Részletek:** a Sheet többi mezője (fejlesztés, nyomáspróba, források, mérőeszköz, mégis-terület, fa-elem, ajánlati megfogalmazás stb.). Betöltéskor kerülnek be, az adatlapon csak olvashatók.

A listák az Értéktár lista-szerkesztőjével működnek (03 · 3.2), de egy elem legfeljebb 1000 karakter lehet, mert a mégis szövegei több mondatosak.

### 7.3 Verziók

Ugyanúgy működik, mint az Értéktárban (03 · 3.3):

- Minden változást tartalmazó mentés és betöltés új verziót hoz létre.
- Mentéskor opcionális megjegyzés: mi változott és miért. Betöltésnél ez automatikus: „Frissítés a mégis tárából, {dátum}”.
- Ahol a képességet használják, ott rögzítjük a használt verziót.
- Bármely két verzió összehasonlítható egymás mellett.
- Az archiválás nem hoz létre új verziót, de eseményként látszik a verziótörténetben.

### 7.4 Csoportok

Almenü: *Képességtár → Csoportok*. Saját lista, nem azonos az Értéktár kategóriáival.

- Mezők: név magyarul (kötelező, egyedi), név angolul, leírás (opcionális).
- Egy képesség egy csoportba tartozik. A csoport bemutatásra és szűrésre való, nem pontozzuk.
- Törölni csak olyan csoportot lehet, amelyikhez nem tartozik képesség.
- Kezdő adatok: a mégis 12 munkacsoportja:
  - Belső megalapozottság és jóllét (Inner Grounding & Wellbeing)
  - Etikus cselekvés és hatalom (Ethical Agency & Power)
  - Rendszerek és komplexitás (Systems & Complexity)
  - Jövő és alkalmazkodás (Futures & Adaptability)
  - Kapcsolati intelligencia (Relational Intelligence)
  - Csapatmunka és részvétel (Teaming & Participation)
  - Cél és stratégia (Purpose & Strategy)
  - Működési rendszer és végrehajtás (Operating System & Execution)
  - Tanulás és innováció (Learning & Innovation)
  - MI, adat és technológia (AI, Data & Technology)
  - Regeneratív érték és ökológia (Regenerative Value & Ecology)
  - Irányítás, kockázat, elszámoltathatóság (Governance, Risk & Accountability)

### 7.5 Archiválás

Ugyanúgy működik, mint az Értéktárban (03 · 3.5): az archivált képesség új helyen nem választható, ahol már használják, ott megmarad, és visszaállítható. Végleges törlés nincs. A „félretett” státuszú képesség betöltéskor archiválva kerül be.

### 7.6 Betöltés a mégis tárából

- **Forrás:** a „Képességrendszer · tárak” Sheet xlsx-exportja (Képesség és Képesség-részletek fül). Indulásra 75 képesség: RC-01–RC-72 és ÚJ-01–ÚJ-03.
- A prototípus `data/kepessegtar-72.json`-ja nem forrás: régi munkaváltozat, minden sorban azonos sablonszövegekkel.
- **Feltöltés:** *Képességtár → Frissítés a mégis tárából*.
- **Előnézet:** hány új, változott és változatlan képesség van, és képességenként a változott mezők. Utána „Betöltöm”.
- **Párosítás azonosító szerint:**
  - új azonosító: új képesség, v1;
  - meglévő, eltérő tartalommal: új verzió (7.3);
  - ami a fájlból hiányzik, nem törlődik.
- A mégis „nincs még” jelölésű mezője üresen kerül be.
- **Kezdő adat:** az előfeltételek fix listája (E1–E9, név és egy mondat), csak olvasható. Például E1 „A fájdalom és a döntési jog egy helyen”, E2 „Gazda”, E3 „Védett idő”.

## Nincs benne

- A minták (amiről a képesség felismerhető) és a bizonyítékok: [NM-19](../backlog_nem_MVP/NM-19-mintatar.md).
- A képesség mérése, kézzel (04) és AI-jal: [NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md).
- A képességek megjelenítése a felhasználóknál (M3, R7): [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- A Bridge to Purpose és a fejlesztésre jelölés: [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- Másodlagos csoport: a mégisnél még megerősítendő.
- Visszaírás a Sheetbe.

## Nyitott kérdések

1. **Hol a mester?** A mégis a Sheetben építi a tárat, és az hetente bővül. Ha az adminban is szerkesztenek, a következő betöltés felülírja.

   **Javaslat:** a Sheet a mester. Az adminban csak sürgős javítás történik, és az adatlap jelzi, hogy a következő betöltés felülírja. A régi szöveg a verziótörténetben megmarad.

2. **Mikor és milyen formában adja át a mégis?** A mégis Építési tervének utolsó lépése egy `kepessegtar-75.json`, de az csak a teljes kutatás és a tesztek után készül el.

   **Javaslat:** a Sheet xlsx-exportja, mert már most elérhető, és később is így frissíthető.

3. **Csoportnevek.** A mégis anyagai angol csoportneveket használnak, a fenti magyar nevek a prototípus JSON-jából jönnek.

   **Javaslat:** a felületen a magyar név, az angol mellette. A magyar neveket a mégis nézze át.
