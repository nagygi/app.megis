# 31 · Ügyféloldal – Profil és adatkezelés

## Cél

A felhasználó egy lapon látja (M6):

- a saját adatait;
- a diagnózisa keretét;
- ami rá vár;
- a beállításait és a hozzájárulásait.

Itt kezeli a jelszavát, a naptárát és a felvételeit, itt vonhatja vissza a hozzájárulását, és itt törölheti a fiókját és az adatait.

**Belépő:** a menü alján „Profil” (15 · 15.3). **Fejléc:** fehér (15 · 15.2).

## Alfeladatok

### 31.1 Én

- Avatár (monogram), név, és egy kísérősor: „{szerep} · {Csapat}”, pl. „vezető · Value Core Team”.
- A név szerkeszthető.
- Az e-mail cím csak látszik. Módosítást a mégistől kérhet, a szuperadmin az adatlapon (33) írja át.
- **Jelszó:** beállítás és csere a 14 · 14.3 szerint.

### 31.2 A diagnózisod (vezetőnek)

- **Termék és időablak**, pl. „Teljes diagnózis · 03.02. → 04.18.”.
- **Kapcsolattartó a mégistől:** a szervezet kapcsolattartója (02).
- **Állapot:** a szakasz (17.5) egyszerű szavakkal: „fut · 3. hét a 7-ből”, „publikálva · 04.18.”.

### 31.3 Rád vár

Legfeljebb három sor, mindegyik a helyére visz. A lista az aktív szervezetből áll össze (15 · 15.4).

- „Új riport · {Termék} · {dátum}” (27);
- „{n} társ még nem válaszolt · emlékeztető küldhető” (26 · 26.1);
- „Megélés hiányzik · {Folyamat} · {dátum}” (12 · 12.5);
- „A {Csapat} diagnózisában társ vagy · Kezdem” (25).

Ha nincs teendő, a blokk nem jelenik meg.

### 31.4 Emberek (vezetőnek)

- „{n} társ · {k} kitöltötte”, és alatta a szabály: „névvel csak ők és a mégis látják · összesítve 3 kitöltő fölött”.
- „Emlékeztető” gomb, a 26 · 26.1 szabályával.
- Csak Teljes, csak csapat.

### 31.5 Beállítások és adatok

- **Naptár:** állapot és szétkötés a 09 · 9.5 szerint („Naptár összekötve · 03.02. · a metaadat 12.21-ig él · Szétkötés”).
- **Ki lát mit:** a V9 lapja (23.4), csak olvasható. A társ a saját változatát látja.
- **Felvételeim:** a saját hangfelvételei (Értéktükör, elmondott helyzet, feltöltött hang), dátummal. Egyenként törölhetők: csak a hang törlődik, az átirat és a diagnózis megmarad (06 · 6.6).
- **Hozzájárulásaim:**
  - a jogi dokumentumok elfogadása (14 · 14.5), csak olvasható;
  - diagnózisonként a társ-hozzájárulás (25.2): „A válaszaim névvel csak a mégisnél” és „Az idézeteim név nélkül”.
  - A diagnózis publikálásáig mindkettő visszavonható (31.6), utána csak olvasható.
- **Adataim törlése:** 31.7.

### 31.6 Hozzájárulás visszavonása (társ)

- **Ha a kötelező hozzájárulást vonja vissza** („A válaszaim…”):
  - megerősítést kér: „Ha visszavonod, a válaszaid és a helyzeteid kikerülnek a {Csapat} diagnózisából, és Útravalót sem kapsz.”;
  - utána a 25 · 25.6 szerint kerül ki a diagnózisból.
- **Ha csak az idézetekről szólót vonja vissza:** a válaszai a diagnózisban maradnak, csak az idézetei nem jelennek meg a vezetőnek. Az olvasatban ezek a nyomok kikerülnek (20).
- Rögzítjük, ki és mikor vonta vissza.

### 31.7 Adataim törlése

- **Megerősítés:**
  - egy lap leírja, mi törlődik és mi marad (lásd lent);
  - a felhasználó a jelszavával vagy egy frissen kért kóddal erősít meg (14 · 14.3).
- **Mi törlődik:** a fiók és minden személyes adat: felvételek, átiratok, saját értékek, megélések, Értéktükrök.
- **Mi marad:** a publikált diagnózisok összesített számai (átlagok). A szó szerinti idézetei kikerülnek a riportból, és a fejezeten „frissítve” jelzés áll (21.4). Az összesített szám már nem személyes adat, az idézet viszont az lehet.
- A szabály az adatkezelési tájékoztatóba (05) kerül, a szövegét jogásszal érdemes megerősíteni.
- **Mi történik a törlés után:**
  - a fiók azonnal inaktív lesz, a felhasználó kijelentkezik;
  - a végleges törlés ütemezett feladatként fut (`cron.md`);
  - a mentésekben az adat még legfeljebb 30 napig megvan ([docs/uzemeltetes.md](../docs/uzemeltetes.md) · 6), ezt az adatkezelési tájékoztató (05) tartalmazza.
- **Napló:** rögzül, ki, mikor és mit törölt. Az admin a felhasználó adatlapján (33) csak a naplót látja.
- **Admin a felhasználó nevében:** a fiók és az adatok törlése, valamint a hozzájárulás megadása vagy visszavonása nem érhető el (14 · 14.6).

## E-mailek

- **Törlés megtörtént:** „A fiókodat és az adataidat töröltük a mégis-ből. Ha nem te kérted, írj nekünk: {támogatási e-mail}.”
- **E-mail cím módosult** (az admin módosította, 33): a régi címre: „A mégis-fiókod e-mail címe {új címre} változott. Ha nem te voltál, írj nekünk.”

## Nincs benne

- A Coach megjelenítése: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- A társ-helyek: a társakat az admin rendeli a csapathoz (17).
- Értesítési beállítások.
- Számlázási adatok: [NM-02](../backlog_nem_MVP/NM-02-szamlazas.md).

## Nyitott kérdések

1. **Mi legyen a vezető és a magánszemély fiókjával?**

   **Javaslat:**
   - A vezető törlésekor a szervezet adatai (értékszett, folyamatok, publikált diagnózis) maradnak, mert a szervezeté. A neve helyén „korábbi vezető” áll.
   - Magánszemélynél a szervezete is a fiókkal együtt törlődik, mert egyfős.

2. **Látja-e a vezető a társak névsorát?** A prototípus szerint „névvel csak ők és a mégis”, a vezető számokat lát.

   **Javaslat:** a névsort látja, mert a csapatot az admin vele egyeztetve állítja össze. Azt viszont nem, hogy ki töltötte ki és ki nem, csak a számot.

3. **Türelmi idő a törlésnél.** Visszavonható-e a törlés néhány napig?

   **Javaslat:** nem. A fiók azonnal inaktív lesz, a végleges törlés legkésőbb 24 órán belül lefut. Türelmi idő helyett az erős megerősítés (jelszó vagy kód) véd a véletlen törlés ellen.

4. **Visszatérhet-e a társ a visszavonás után?**

   **Javaslat:** ugyanabba a diagnózisba nem, mert az adatai már kikerültek. Ha mégis kérné, az admin a Futás lapon (19) újra társnak jelölheti, és új meghívót kap, üres lappal.
