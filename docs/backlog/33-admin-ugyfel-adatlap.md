# 33 · Admin felület – az ügyfél adatlapja

## Cél

Az admin egy lapon látja egy ügyfél-felhasználó adatait, szervezeteit és diagnózisait, és mindazt, ami az adatkezeléséről nyomot hagy: elfogadások, hozzájárulások, a „nevében” végzett munka, törlések. Erre a lapra hivatkozik a 06 (kíváncsiság-kérdések), a 14 (14.5, 14.6) és a 31 (31.7).

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 33.1 Hol nyílik

- A szervezet *Felhasználók* fülén (02 · 2.5) a felhasználó nevére kattintva (NFR: az entitás nevére nyílik).
- A Diagnózisok, a Beérkező és a Futás lap soraiból, ahol felhasználó szerepel (19, 22).
- Az *Ügyfelek* listából (33.8).

### 33.2 Fejléc

- **Adatok:** név, e-mail, állapot (02), utolsó belépés.
- Ha a felhasználónak küldött levél nem kézbesíthető, ez is látszik: „A levél nem kézbesíthető” (`docs/uzemeltetes.md`).
- **Műveletek** a 02 · 2.5 szabályaival:
  - szerkesztés;
  - meghívó küldése vagy újraküldése;
  - inaktiválás vagy aktiválás;
  - belépés a nevében (14 · 14.6).

### 33.3 Szervezetek és diagnózisok

- **Szervezetenként:** címkék, csapatok (csapatvezető-jelöléssel), pozíció, állapot, link a szervezetre.
- **Diagnózisok:**
  - a fő diagnózisok, amelyekben vezető vagy társ: szerep, szakasz (17.5), link;
  - alattuk a felhasználó részdiagnózisai (17.4): Értéktükör, Megélés.

### 33.4 Értéktükör és érdeklődés

- A felhasználó Értéktükrei dátummal, linkkel a diagnózisra (06).
- Az ET5-ön jelölt kíváncsiság-kérdések, és hogy rákattintott-e az „Időpontot foglalok” gombra, mikor (06, 22).

### 33.5 Elfogadások és hozzájárulások

- **Jogi dokumentumok** (14 · 14.5): dokumentum, verzió, mikor fogadta el.
- **Hozzájárulások** (25.2, 31.6), diagnózisonként:
  - melyik szöveg;
  - mikor adta meg;
  - mikor vonta vissza, ha visszavonta.
- Csak olvasható. Az admin hozzájárulást nem adhat és nem vonhat vissza a felhasználó helyett (14 · 14.6).

### 33.6 Napló

Időrendben, csak olvasható:

- a „nevében” munkamenetek: ki indította, mikor kezdődött és ért véget, és mit módosított (14 · 14.6);
- felvétel törlése (31.5), hozzájárulás visszavonása (31.6), e-mail cím módosítása (31);
- fióktörlés (31.7).

### 33.7 Törölt felhasználó

- A törlés után (31.7) az adatlap csak a naplót mutatja, név és e-mail nélkül: „Törölt felhasználó · {belső azonosító} · {dátum}”.
- A szervezetekben és a diagnózisokban a helyén „korábbi vezető”, illetve „korábbi társ” áll (31 · 1. kérdés).

### 33.8 Ügyfelek lista

- Bal oldali menüpont: *Ügyfelek*, az NFR listaszabályai szerint.
- Oszlopok: név, e-mail, szervezet(ek), állapot, utolsó belépés.
- Kereső: név és e-mail. Szűrők: szervezet, állapot, „csak Értéktükör” (diagnózis nélküli felhasználók).
- A sor az adatlapra nyílik. Új felhasználót innen nem lehet felvenni, az a szervezeten át megy (02).

## Nincs benne

- Globális audit-napló az adminban (01).
- A felhasználó válaszainak kiírása a lapon: a részdiagnózisok linkjein érhetők el (lásd 1. kérdés).

## Nyitott kérdések

1. **Mennyit mutasson a lap a felhasználó válaszaiból?** A mégis mindent lát, névvel (V9).

   **Javaslat:** a lap ne írja ki a válaszokat (megélés, saját értékek), csak linkeljen a részdiagnózisokra (17.4). Így az adatlap áttekintő marad, a tartalom pedig ott látszik, ahol a diagnózis összefüggésében érthető.

2. **Szerkesztheti-e az admin a nevet és az e-mail címet?** A 02 csak a pozíciót, a címkéket és a csapatokat sorolja fel.

   **Javaslat:**
   - A nevet bármelyik admin szerkesztheti.
   - Az e-mail címet csak szuperadmin, ahogy az adminoknál (01 · 1.5). A felhasználó a régi és az új címére is értesítést kap.

3. **Mi marad a naplóban a fiók törlése után?**

   **Javaslat:** a napló sorai megmaradnak, mert vita esetén tudni kell, mi történt és mikor (ahogy a jogi dokumentumok verzióinál, 05). A személyes adat (név, e-mail) kikerül belőlük, a felhasználót csak a belső azonosító jelöli.

4. **Meddig őrizzük a naplót?**

   **Javaslat:** amíg a szervezet létezik, a fiók törlése után anonimizálva. A technikai logok 365 napos szabálya (NFR) erre nem vonatkozik, mert ez üzleti adat, nem log.
