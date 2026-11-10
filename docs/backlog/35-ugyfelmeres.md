# 35 · Ügyfélmérés – a szervezet ügyfeleinek hangja

## Cél

A szervezet értékeit és folyamatait a csapat belülről méri (17, 23–25). Az ügyfélmérés a külső hangot adja hozzá: azt, hogyan éli meg a szervezetet az az ügyfél, akinek szolgáltat („az ügyfél szemével”, `kepessegrendszer_osszefoglalo` §6.3).

- Opcionális, szervezetenként kapcsolható be. A legtöbb szervezetnél nincs.
- Az ügyfélmérés mindig egy diagnózishoz tartozik (17).

Az „ügyfél” itt a szervezet ügyfele, nem a mégis ügyfél-felhasználója (14, 33). A kódban végig `ExternalClient` / `external_client` a neve.

## Alfeladatok

### 35.1 Kapcsoló és az Ügyfelek fül

- A szervezet *Adatok* fülén (02 · 2.3) „Ügyfélmérés” kapcsoló van, alapból kikapcsolva.
- Bekapcsolva a szervezet oldalán megjelenik az **Ügyfelek** fül a *Folyamatok* és a *Diagnózisok* között. Kikapcsolva a fül nem látszik, és a címe 404-et ad. A meglévő adatok megmaradnak.
- A lista oszlopai: név (alatta a megjegyzés), felhasználók száma, „Mit mérünk” (`{n} érték · {m} folyamat`), állapot.
- Sorműveletek: szerkesztés, inaktiválás vagy aktiválás, törlés.
  - Inaktív ügyfél nem rendelhető új diagnózishoz. A korábbi válaszai megmaradnak.
  - Törléskor a felhasználói fiókok megmaradnak, csak az ügyfélhez rendelésük szűnik meg.
- Az új ügyfél és a szerkesztés saját oldalon nyílik. Fölötte a morzsa: „Ügyfelek”.

### 35.2 Az ügyfél adatai és felhasználói

- **Adatok:** név (a szervezeten belül egyedi), megjegyzés, aktív.
- **Felhasználók:** vezetéknév, keresztnév, e-mail.
  - Ha már van fiók ezzel a címmel, azt használjuk, a neve nem változik.
  - Admin fiók nem lehet ügyfél-felhasználó.
  - Az ügyfél-felhasználó **nem tagja** a szervezetnek: nem számít a létszámba (02 · 2.2), és nem látja a szervezet oldalait.
  - Minden sor mutatja, belépett-e már.
- Az admin *Felhasználók* adatlapján (33 · 33.3) a szervezetek alatt az ügyfélkapcsolatai is látszanak: „{ügyfél} · Ügyfél · {szervezet}”.

### 35.3 Mit mérünk, milyen kérdéssel

- **Értékek:** a szervezet aktuális értékei (08). Új ügyfélnél mindegyik ki van jelölve.
- **Folyamatok:** a szervezet mért (rutin, elfogadott, felső szintű) folyamatai (11). Alapból egyik sincs kijelölve. A lista alatt link az „Új folyamat” oldalra: az admin előre is felvehet folyamatot.
- **Kérdés:** minden kijelölt elemhez tartozik egy saját kérdés, kötelező. Az alapszöveg:
  - értéknél: „Mesélj egy helyzetről, amikor a közös munkánkban megmutatkozott – vagy épp hiányzott – ez: „{érték}”. Mi történt, és milyen volt ezt megélni?”
  - folyamatnál: „Gondolj a legutóbbi alkalomra, amikor részese voltál ennek nálunk: „{folyamat}”. Mi történt, mi ment jól, és min változtatnál?”
- Az értéket a szervezeti értékkészlet elemének állandó kulcsa azonosítja, így az értékkészlet új verziója után is megmarad.

### 35.4 Diagnózishoz rendelés

- A diagnózis beállításán (A1, 17.2) „Ügyfélmérés” szakasz jelenik meg, ha a kapcsoló be van kapcsolva, és van aktív ügyfél.
- Itt az admin kijelöli a résztvevő ügyfeleket. Új diagnózisnál alapból minden aktív ügyfél ki van jelölve.

### 35.5 Meghívó és belépés

- **Mikor:** amikor a diagnózis „Fut” szakaszba lép (23.4), a kijelölt ügyfelek minden felhasználója e-mailt kap.
- **Futás közben:** ha a futás alatt ügyfél kerül a diagnózishoz, vagy egy ügyfélhez felhasználó, ő azonnal megkapja a meghívót.
- **Újraküldés:** az admin felhasználónként újraküldheti a meghívót.
- **A link:** a levélben személyes link van (14 napig él), jelszó nem kell.
  - Megnyitáskor csak egy gomb látszik (a levelezőszkennerek miatt, 14 · 14.2), és a jogi dokumentumok elfogadása (14 · 14.5).
  - A gombra a fiók aktiválódik, a felhasználó belép, és a kérdőív nyílik.
- **Visszatérés:** később a */belepes* oldalon belépőkóddal jön vissza. Ha nincs más szervezete, belépés után a nyitott ügyfélmérésére jut, a „szünetel” lap helyett.

### 35.6 Az ügyfél kitöltése

- **Bevezető:** a szervezet neve, mire kell a válasz, és a hozzájárulás. Ez kötelező jelölőnégyzet, alapból nincs bejelölve.
- **Kérdések:** elemenként egy képernyő a testreszabott kérdéssel.
  - A válasz elmondható, leírható vagy fájlból feltölthető: Leírom · Elmondom · Feltöltöm.
  - A piszkozat mentődik.
- **Összegzés** és „Beküldöm”.
- Válaszolni csak a „Fut” szakaszban lehet. Addig a beküldött válasz módosítható, és a módosított válasz lép a régi helyére.
- Az olvasat indulása után a lap ezt mutatja: „A mérés lezárult.”

### 35.7 Feldolgozás

- Minden beküldött válaszból egy anyag készül, a kérdéssel és a válasszal együtt, és ugyanúgy értékelődik, mint a csapat anyagai (18 · 18.4). Az AI tudja, hogy egy ügyfél válaszát olvassa.
- Az anyag a válaszon keresztül tartozik a diagnózishoz, nem a diagnózis anyagaként. Így a csapat nézetei (Futás, riport, vezetői kezdőlap) nem számolnak vele.
- **A csapat számai nem változnak:** az ügyfelek anyaga nem számít bele a fő diagnózis Mérés és Megélés értékeibe, sem a képességmérésbe.
- **Ügyfelenként egy részdiagnózis** készül a fő diagnózis alá (17.4), automatikusan.
  - Értéksoronként: az ügyfél anyagainak pontátlaga a kijelölt értékre. Adat nélkül „nincs adat”.
  - Folyamatsoronként: az arra a folyamatra adott válaszok értékpontjainak átlaga (13).
  - Ha egy ügyfél lekerül a diagnózisról, a részdiagnózisa törlődik.

### 35.8 Az admin nézetei

- **Ügyfélmérés fül** a fő diagnózison, ha a diagnózisnak van ügyfele:
  - ügyfelenként a felhasználók állapota (meghívva · elkezdte · beküldte), és futás közben új link küldése;
  - a válaszok: kérdés, válasz, link az anyagra;
  - link a részdiagnózisra.
- **Futás** (19): „Ügyfelek: {x} / {y} válaszolt”.
- **Részdiagnózisok** (19.3): az ügyfél részdiagnózisa „Ügyfél · {név}” címkével. Az adatlapja nem kínál aggregált betöltést.
- **Olvasat** (20): a kiválasztott érték- vagy folyamatsornál „Ügyfél szemével: {átlag} · {n} válasz”. Az átlagban minden ügyfél egyszer számít.
- **A vezető riportja egyelőre nem változik.** Az „Ügyfél szemével” blokk a riportban későbbi jegy.
