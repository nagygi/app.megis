# 14 · Ügyféloldal – regisztráció és belépés

## Cél

Az ügyfél a meghívóból regisztrál (02), és utána e-mailben kapott kóddal vagy jelszóval lép be (B1–B2). Belépés után oda érkezik, ahol tart. Ugyanez a jegy rögzíti a jogi dokumentumok elfogadását, és itt van az a lehetőség is, hogy az admin a felhasználó nevében dolgozzon.

Az admin belépése külön működik: 01.

## Alfeladatok

### 14.1 Meghívó és regisztráció

- A meghívó linkje (02) **14 napig él**, és egyszer használható. Új meghívó küldésekor a régi link érvényét veszti.
- A link érvénytelen, ha a felhasználót vagy a szervezetet inaktiválták.
- **Lejárt vagy érvénytelen meghívó:** „Ez a meghívó már lejárt. Kérj újat attól, aki meghívott.” Újat a felhasználó maga nem kérhet. Az admin a lista „Meghívó küldése” gombjával küld újat (02).
- **Regisztrációs lap**, B1 fejléccel. A kísérősor: „{Meghívó} meghívott a {Szervezet} felhasználójaként.”
  - Vezetéknév, keresztnév: kötelező. Ha az admin megadta, előre kitöltve jelenik meg, és átírható.
  - Jelszó és megerősítése: opcionális (14.3). Alatta ez a szöveg áll: „Ha nem adsz meg jelszót, e-mailben kapott kóddal lépsz be.”
  - Jelölőnégyzet: „Elfogadom az ÁSZF-et és az adatkezelési tájékoztatót”, linkkel a hatályos verziókra (05). Alapból nincs bejelölve, és amíg nincs bejelölve, a gomb szürke.
  - Turnstile.
- Mentés után a felhasználó „Aktív” állapotba kerül, belép, és a 14.4 szerinti lapra érkezik.
- Ha valakit meglévő fiókkal adtak hozzá egy új szervezethez (02, értesítő e-mail), az ő linkje a belépésre visz, nem regisztrál újra.
- **A link megnyitása még nem használja el a tokent.** A céges levelezők linkellenőrzője előre megnyitja a linkeket, ezért a token csak a lapon végzett műveletnél (mentés, gombnyomás) használódik el. Ugyanez érvényes a belépő linkre is (14.2).

### 14.2 Belépés (B1–B2)

**B1 · Belépés**

- Mezők: e-mail és Turnstile.
- Elsődleges gomb: „Belépek”. Ez kódot küld.
- Szöveges link: „Jelszóval lépek be”. Erre egy jelszómező jelenik meg.
- Ha a felhasználó meghívóból vagy az Értéktükörből jön, a kísérősor mondja meg, ki hívta meg, és hová. Egyébként csak a „Belépés” cím áll.
  - Meglévő fióknál ezt a „hozzáadtunk” levél linkje és a régi meghívó linkje hozza: „{Meghívó} meghívott a(z) {Szervezet} felhasználójaként.” A link alá van írva, 14 napig él, a kísérősor máshonnan nem állítható elő.
- Ismeretlen, inaktív vagy jelszó nélküli fiók esetén ugyanaz a válasz jelenik meg, így kívülről nem derül ki, kinek van fiókja:
  - Kódkérésnél: „Ha ehhez a címhez tartozik fiók, elküldtük a belépő kódot.” Alatta: „Még nincs fiókod? Nézz bele a tükörbe.” (ET1).
  - Jelszónál: „Hibás e-mail cím vagy jelszó.”

**B2 · Nézd meg a leveled**

- A levélben egy link és egy 6 jegyű kód van. Mindkettő **15 percig** él, és egyszer használható. Új kérés a korábbit érvényteleníti.
- A lapon van a kód beviteli mezője. Ha a felhasználó másik eszközön olvassa a levelet, ide írja be a kódot.
- Gombok: „Újraküldöm” (60 másodperc után aktív) és „Másik e-mail”.
- 5 hibás kód után a kód érvényét veszti, újat kell kérni.
- **Lejárt vagy már használt belépő link** (az „Extra inputok” lap 2. állapota): a B2 lapja „Ez a link már lejárt” fejléccel, „Újat kérek” gombbal. A belépő kódot, a meghívóval ellentétben, a felhasználó maga kérheti újra. A gomb Turnstile-t kér, és a kódkérés óránkénti keretébe számít.

**Védelem**

- Rate limit e-mail és IP alapján:
  - kódkérés: óránként legfeljebb 5;
  - belépési próbálkozás: percenként legfeljebb 5.
- 10 egymást követő hibás jelszó után a jelszavas belépés 15 percre szünetel. Kóddal közben is be lehet lépni. A számlálót a sikeres belépés nullázza.
  - A szünet ismeretlen és jelszó nélküli címre is ugyanígy jár, így az üzenetből sem derül ki, kinek van fiókja.

**Session**

- A felhasználó 30 napig belépve marad. A session minden használattal megújul.
- Kijelentkezni a menüből lehet (15).
- Jelszócsere vagy inaktiválás után minden más session megszűnik.

**Inaktív felhasználó vagy szervezet**

- Ha a felhasználó egyik szervezete inaktív, a többi szervezetét eléri.
- Ha egy aktív szervezete sincs: „A hozzáférésed szünetel. Keresd a mégis kapcsolattartódat.”

### 14.3 Jelszó

- Szabály: legalább 8 karakter, és ismert kiszivárgott jelszó nem használható. A szabályt az űrlap kiírja.
- Beállítás és csere a Profilban (M6, 31):
  - Ha már van jelszava, a jelenlegi jelszót kéri.
  - Ha nincs jelszava, vagy elfelejtette, egy frissen kért kóddal erősíti meg.
- Külön „Elfelejtett jelszó” folyamat nincs. Aki elfelejtette, kóddal lép be, és a Profilban új jelszót állít.
- Jelszócseréről e-mail értesítés megy.

### 14.4 Hová érkezik

A rendszer választja ki a lapot, nem a felhasználó (B2):

- a diagnózis még nem indult → Mi vár rád (V1);
- fut → Hol tartunk (V10, snapshotnál V10·b);
- elkészült → a diagnózis (V12, snapshotnál V12·b);
- társ egy futó diagnózisban → a meghívó (T1), később a saját kezdőlapja (T5, 25);
- csak Értéktükröt csinált → a saját értékei (M2, 30).

További szabályok:

- Több szervezetnél a legutóbb használt szervezet nyílik meg (15).
- Ha a felhasználó egy konkrét linkről jött (pl. egy e-mailből), belépés után oda jut vissza.
- A céllapokat a saját jegyeik adják. Amíg azok nincsenek kész, az app kezdőlapja nyílik meg (15).

### 14.5 A jogi dokumentumok elfogadása

- Az elfogadás a regisztrációkor (14.1) és az ET5-ön (06) történik. Rögzítjük:
  - ki fogadta el, mikor;
  - melyik ÁSZF- és melyik adatkezelési verziót, a kettőt külön sorban.
- **Új verzió (05):** a feltöltéskor az admin egy kapcsolóval dönti el: „Újra el kell fogadni”. A kapcsoló alapból ki van kapcsolva.
  - Ha be van kapcsolva, a hatálybalépés után a felhasználó a következő megnyitáskor egy lapot kap: „Változott az ÁSZF.” vagy „Változott az adatkezelési tájékoztató.”, linkkel és „Elfogadom” gombbal.
  - Amíg nem fogadja el, nem megy tovább. Mellette ott van a „Kijelentkezem” gomb.
- Az adminban, a felhasználó adatlapján (33) látszik, melyik verziót mikor fogadta el.
- A cookie tájékoztatót nem kell elfogadni.

### 14.6 Belépés a felhasználó nevében (admin)

- A 02-es jegy Felhasználók listájának sorművelete: „Belépés a nevében”.
  - Admin és Szuperadmin használhatja.
  - Csak aktív felhasználónál érhető el. Admin szerepkörű fióknál nincs ilyen sorművelet.
- Az ügyféloldal új lapon nyílik meg. Felül végig egy coral sáv látszik: „{Név} nevében dolgozol · Kilépés”.
- Az admin teljes joggal dolgozhat, kivéve:
  - a jelszó és az e-mail cím módosítása;
  - a jogi dokumentumok elfogadása és bármely hozzájárulás megadása vagy visszavonása;
  - a fiók és az adatok törlése;
  - a naptár összekötése. A vezető kérésére az admin a *Folyamatok* fülről töltheti be a naptárat, naplózva (09 · 9.2).
- **Napló:**
  - Rögzítjük az indítást, a befejezést, és minden módosításnál, hogy melyik admin végezte a felhasználó nevében.
  - A napló a felhasználó adatlapján (33) látszik az adminban. A felhasználó nem látja.
- A munkamenet a „Kilépés” gombbal, az admin kijelentkezésével vagy az admin session lejártával (01) ér véget.

## E-mailek

- **Belépő kód:** „A belépő kódod: {123456}. [Belépek] A kód és a link 15 percig él. Ha nem te kérted, hagyd figyelmen kívül.”
- **Jelszó megváltozott:** „A jelszavad {időpont}-kor megváltozott. Ha nem te voltál, írj nekünk: {támogatási e-mail}.”
- A meghívó és a hozzáadás szövege a 02-es jegyben van.

## Nincs benne

- Önkiszolgáló új meghívó.
- A társ meghívása a vezetőtől (V8): [NM-16](../backlog_nem_MVP/NM-16-tarsak-meghivasa-vezetotol.md).
- Kétfaktoros azonosítás az ügyfeleknek.
- Belépés Google- vagy Microsoft-fiókkal.
- Fiók és adatok törlése: 31.
- Regisztrációs emlékeztető: [NM-04](../backlog_nem_MVP/NM-04-regisztracios-emlekezteto.md).
