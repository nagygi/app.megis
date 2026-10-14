# 05 · Admin felület – Jogi dokumentumok

## Cél

Az admin a *Beállítások* alatt feltölti a jogi dokumentumokat PDF-ben. A publikus oldalak és a regisztráció mindig a hatályos verzióra linkelnek.

Három dokumentum van:

- Adatkezelési tájékoztató
- ÁSZF
- Cookie tájékoztató

## Alfeladatok

### 5.1 Beállítások → Jogi dokumentumok

Bal oldali menüpont: *Beállítások → Jogi dokumentumok*. Egy lap, dokumentumonként egy blokk. A *Beállítások* menüt a Szuperadmin és az Admin is eléri.

- Minden blokkban látszik:
  - a hatályos verzió: fájlnév, hatályba lépés dátuma, ki és mikor töltötte fel;
  - egy „Megnyitás” link.
- „Új verzió feltöltése” gomb:
  - Csak PDF tölthető fel, legfeljebb 10 MB. A szabály ki van írva (NFR).
  - Meg kell adni a hatályba lépés dátumát (alapból a mai nap, jövőbeli is lehet).
  - Jövőbeli dátumnál a dátumig a régi verzió marad hatályos.
  - „Újra el kell fogadni” kapcsoló, alapból kikapcsolva. Ha be van kapcsolva, a hatálybalépés után a felhasználóknak újra el kell fogadniuk a dokumentumot (14 · 14.5). A Cookie tájékoztatónál nincs ilyen kapcsoló.
- Korábbi verziók listája: dátum, fájl, ki töltötte fel. Csak olvasható, nem törölhető, mert vita esetén tudni kell, melyik szöveg volt hatályos.
- Ha egy dokumentum még nincs feltöltve, a blokk figyelmeztetést mutat. A setup check parancs (NFR) is jelzi.

### 5.2 Publikus linkek

- Minden dokumentumnak állandó URL-je van, ami mindig a hatályos verziót adja vissza:
  - `/adatkezelesi-tajekoztato`
  - `/aszf`
  - `/cookie-tajekoztato`
- Ezekre linkelnek:
  - az Értéktükör ET5 lapja (06),
  - a publikus oldalak lábléce,
  - a regisztráció (14).
- Ha a dokumentum még nincs feltöltve, a link nem jelenik meg.

## Nincs benne

- Cookie hozzájárulás-kezelő (banner).
- Annak rögzítése, melyik felhasználó melyik verziót fogadta el: az ET5-ön a 06-os, egyébként a 14-es jegyben.
