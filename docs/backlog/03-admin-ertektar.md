# 03 · Admin felület – Értéktár

## Cél

Globális értéktár, ahol az admin felveszi és karbantartja az értékeket. Erre épül később:

- a szervezeti, csapat és egyéni értékek: ezek kiegészíthetik a globális értéket, vagy változtatás nélkül használhatják;
- a felhasználó „Az Értéktárból választom” lehetősége (V2·c);
- az AI, amely feltöltött vagy felmondott szövegben ismeri fel az értékeket.

Ezért minden szerkesztés új verziót hoz létre, és a verziók egy felületen visszanézhetők.

Szuperadmin és Admin egyaránt használhatja (lásd 01).

## Alfeladatok

### 3.1 Értékek lista

Bal oldali menüpont: *Értéktár*. Almenük: *Értékek*, *Kategóriák*. A lista az NFR listaszabályai szerint működik.

- Oszlopok: név, kategória, verzió (pl. „v3”), utolsó módosítás (ki, mikor), állapot (Aktív / Archivált), eredet-címke: „Diagnózisnál felvett”, „AI által detektált” vagy „Szervezet által felvett” (lásd 3.7).
- Kereső: azonosító, név (magyarul és angolul), valamint szinonimák és kulcsszavak.
- Szűrők: felsőbb szint, kategória, szakirodalmi alátámasztottság, SVS-index, állapot, eredet-címke. Az archivált értékek alapból rejtve vannak.
- „+ Új érték” gomb.
- Sorműveletek: szerkesztés, verziók, archiválás / visszaállítás.

### 3.2 Érték felvitele és szerkesztése

A mezők a V2·c értékkártyát követik.

- **Név** (kötelező, egyedi) és **Név (angol)** (opcionális).
- **Azonosító** (opcionális, egyedi): a mégis táblázatának állandó kódja, a betöltés ez alapján frissít (3.6). Megadás után nem változik; az ügyfél és az AI nem látja.
- **Slug** (kötelező, egyedi): a névből képződik automatikusan, és a „Szerkesztés” gombbal írható át, ugyanúgy, mint a szervezetnél (02).
- **Kategória** (opcionális): választás a kategórialistából. Egy értéknek csak egy kategóriája lehet.
- **Definíció** és **Definíció (angol)** (opcionális, egy mondat): az ügyfél az Értéktárban a név alatt látja, az AI is megkapja.
- **Mit jelent?** (opcionális lista).
- **Mit nem jelent?** (opcionális lista).
- **Hamis pozitívok** (opcionális lista): ami hasonlít rá, de nem ez az érték. Az AI megkapja, az ügyfél nem látja.
- **Átfedések más értékekkel** (opcionális lista): a szomszédos értékek és a határ. Az AI a más értékek neve helyett a kódjukat kapja (06 · 6.4).
- **6 · Mikor teljesül nagyon?** (lista, legalább 1 elem kötelező).
- **1 · Mikor nagyon nem teljesül?** (lista, legalább 1 elem kötelező).
- **Példák** (opcionális lista): konkrét helyzetek, mint a V2·b kártyán a „Például” sor.
- **Szinonimák, kulcsszavak** (opcionális lista): ezekből ismeri fel az AI az értéket egy szövegben.
- **Szakirodalom, mérési eszközök** (opcionális lista), **szakirodalmi alátámasztottság** és **transzponálhatóság** (erős / közepes / gyenge), **SVS-index** (igen / nem (X-kódolt) / nem (ellenőrizendő)). Csak az adminban látszanak.

**Listák** (az NFR lista-szerkesztője szerint):

- Egy elem 2–255 karakter lehet, egy szó vagy egy-két mondat. A szakirodalom eleme legfeljebb 500 karakter.
- Az elemek sorrendje drag and droppal állítható. A sorrend a prioritás: az első a legfontosabb.

Az 1 és a 6 a skála két vége, a diagnózis ehhez mér (R2·b). Az űrlapon ugyanaz a jelölés látszik, mint a V2·c-n: coral 1, zöld 6.

### 3.3 Verziók

- Minden mentés, amely változást tartalmaz, új verziót hoz létre (v1, v2, …). Változás nélküli mentés nem hoz létre új verziót.
- A verzió a teljes tartalmat őrzi: minden mezőt, a listák sorrendjével együtt.
- A verzió mellett látszik, ki és mikor mentette.
- **Használat:** ahol az értéket használják, ott rögzítjük, melyik verziót használták. Ilyen hely a szervezet, a diagnózis és az AI elemzés.
  - Az új verzió nem változtatja meg a már futó diagnózist vagy elemzést.
  - Az új felhasználások már az új verziót kapják.
  - A felhasználó helyeket a későbbi jegyek valósítják meg, de erre a szabályra kell épülniük.
- **Verziók felület** (az érték oldaláról, „Verziók” fül vagy gomb):
  - A verziók listája: sorszám, ki, mikor, mely mezők változtak.
  - Egy régi verzió csak olvasható nézetben megnyitható.
  - Bármely két verzió összehasonlítható egymás mellett. A felület kiemeli a hozzáadott, törölt és átrendezett listaelemeket és a szövegváltozásokat.
- Az archiválás és a visszaállítás nem hoz létre új verziót, de eseményként látszik a verziótörténetben.

### 3.4 Kategóriák

Almenü: *Értéktár → Kategóriák*. Lista, „+ Új kategória” gomb, szerkesztés.

- Mezők: név (kötelező, egyedi), angol név, felsőbb szint (Schwartz felsőbb rendű értéke, pl. „Megőrzés (Conservation)”), leírás (mind opcionális).
- A kategóriák Schwartz alapértékei (Önállóság, Stimuláció … Univerzalizmus); a felsőbb szint a kategória tulajdonsága, nem külön szint.
- Törölni csak olyan kategóriát lehet, amelyikhez nem tartozik érték.

### 3.5 Archiválás

- Az archivált érték új helyen nem választható: sem a felhasználónál (V2·c), sem a szervezeti értékeknél.
- Ahol már használják, ott megmarad.
- Visszaállítható. Végleges törlés nincs.

### 3.6 Kezdő adatok

- A mégis átadta az 58 értéket, az Oszi által bővített Schwartz-féle rendszer szerint, a kategóriákkal és a felsőbb szintekkel együtt: `docs/data/ÉRTÉKEK KITÖLTÖTT.xlsx`. Oszloponként a fenti mezők; a formátum a `docs/install.md`-ben.
- Az új értékek v1-ként kerülnek be. Az újratöltés az azonosító (ennek hiányában a név) alapján frissít: változásnál új verzió készül megjegyzéssel, a változatlan érték változatlan marad.

### 3.7 Máshonnan felvett értékek

Három úton kerülhet érték az Értéktárba, nem az admin űrlapjáról:

- **„Diagnózisnál felvett”**: az admin egy diagnózisnál (04) vesz fel új értéket, csak a nevével.
- **„AI által detektált”**: az értékmérés motorja (06) olyan értéket talál, ami semmilyen Értéktár-értékre nem illeszkedik. Ilyenkor a név mellé az AI indoklása kerül a „Mit jelent?” listába. Az előfordulások (melyik diagnózisban, milyen idézettel) az érték oldalán látszanak.
- **„Szervezet által felvett”**: a szervezet vezetője vesz fel új értéket a szervezet értékei közé (08). Ilyenkor a végpontokat is kitölti.

Mindhárom esetben:

- A két végpont (1 és 6) még hiányozhat. Amíg hiányzik, az érték:
  - diagnózisokhoz hozzárendelhető;
  - a felhasználói oldalon (V2·c) nem választható.
- Az Értéktárban az első szerkesztéskor már a 3.2 szabályai érvényesek, a végpontok kötelezők.
- A címke megmarad, és mutatja, honnan jött az érték.
- **Átnézés:** amíg az admin nem jelöli „Átnézve”-nek (sorművelet), az érték csak ott választható, ahol létrejött. Más szervezet vagy felhasználó addig nem választhatja ki.

## Nincs benne

- A szervezet értékei (08) és a saját értékek (24): a globális érték egy adott verziójára hivatkoznak. Csapat-értékek: később.
- Az AI-alapú értékfelismerés: 06.
- AI-javaslat az admin űrlapon: nem lesz, az admin kézzel tölti ki.
- Visszaállítás korábbi verzióra.
- Végleges törlés.
