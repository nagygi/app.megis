# 33 · Admin felület – az ügyfél adatlapja

## Cél

Az admin egy lapon látja egy ügyfél-felhasználó adatait, szervezeteit és diagnózisait, és mindazt, ami az adatkezeléséről nyomot hagy: elfogadások, hozzájárulások, a „nevében” végzett munka, törlések. Erre a lapra hivatkozik a 06 (kíváncsiság-kérdések), a 14 (14.5, 14.6) és a 31 (31.7).

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 33.1 Hol nyílik

- A szervezet *Felhasználók* fülén (02 · 2.5) a felhasználó nevére kattintva (NFR: az entitás nevére nyílik).
- A Diagnózisok, a Beérkező és a Futás lap soraiból, ahol felhasználó szerepel (19, 22):
  - a Futáson a vezető, a társak, a naptár és a beszélgetések felhasználója;
  - a diagnózis adatlapján a vezető, a felhasználói szintű diagnózis alanya és az anyag feltöltője; a Diagnózisok listáján a felhasználói szintű sor alanya;
  - a Beérkezőben és az *Értékmérések › Beérkező*ben a sor szövege alatt az a felhasználó, akiről a sor szól (meghívott, vezető, visszajelző, besorolandó anyag feltöltője).
- Az *Ügyfelek* listából (33.8).

### 33.2 Fejléc

- **Adatok:** név, e-mail, állapot (02), utolsó belépés.
- Ha a felhasználónak küldött levél nem kézbesíthető, ez is látszik: „A levél nem kézbesíthető” (`docs/uzemeltetes.md`).
- **Műveletek** a 02 · 2.5 szabályaival:
  - szerkesztés: egy ügyfél nevét bármelyik admin, az e-mail címet csak szuperadmin írhatja át, ahogy az adminoknál (01 · 1.5). Admin-fiók nevét itt is csak az írhatja át, aki a fiókot szerkesztheti: szuperadminét csak szuperadmin. E-mail-módosításkor a felhasználó a régi és az új címére is értesítést kap, és a „nem kézbesíthető” jelzés törlődik;
  - meghívó küldése vagy újraküldése;
  - inaktiválás vagy aktiválás: a fiók szintjén, bármelyik admin. Az inaktív fiók nem tud belépni, a munkamenetei azonnal véget érnek, a tagságai nem változnak. A naplóba is bekerül;
  - archiválás: csak szuperadmin. Ez a fióktörlés (31.7), csak a szuperadmin indítja: a fiók azonnal inaktív, a személyes adatok egy órán belül törlődnek, és ez nem vonható vissza. A felhasználó e-mailt kap, amely szerint az adminisztrátor törölte a fiókját. Archivált fiókot nem lehet aktiválni;
  - belépés a nevében (14 · 14.6).
- Az inaktiválás, az aktiválás és az archiválás a fejléc *Műveletek* menüjében van, ugyanúgy, mint a listán (33.8).
- Admin fióknál (admin, aki egy szervezetnek is tagja) ezek a műveletek nem érhetők el: az *Admin felhasználók* között kezelhető (01).

### 33.3 Szervezetek és diagnózisok

- **Szervezetenként:** címkék, csapatok (csapatvezető-jelöléssel), pozíció, állapot, link a szervezetre.
- **Diagnózisok:**
  - a fő diagnózisok, amelyekben vezető vagy társ: szerep, szakasz (17.5), link;
  - alattuk a felhasználó részdiagnózisai (17.4): Értéktükör, Megélés.

### 33.4 Értéktükör és érdeklődés

- A felhasználó Értéktükrei dátummal, linkkel a diagnózisra (06).
- Az ET5-ön jelölt kíváncsiság-kérdések, és hogy rákattintott-e az „Időpontot foglalok” gombra, mikor: „időpontot foglalna · 2026.10.20. 14:05” (06, 22). Az ET5-ön most nincs foglalás: [NM-22](../backlog_nem_MVP/NM-22-idopontfoglalas.md).

### 33.5 Elfogadások és hozzájárulások

- **Jogi dokumentumok** (14 · 14.5): dokumentum, verzió, mikor fogadta el.
- **Hozzájárulások** (25.2, 31.6), diagnózisonként:
  - melyik szöveg;
  - mikor adta meg;
  - mikor vonta vissza, ha visszavonta.
- Csak olvasható. Az admin hozzájárulást nem adhat és nem vonhat vissza a felhasználó helyett (14 · 14.6).

### 33.6 Napló

Időrendben, a legfrissebb elöl, 50 soronként lapozva, csak olvasható. Minden sorban: mi történt, mikor, ki tette, és ha szervezethez tartozik, melyikben.

- a „nevében” munkamenetek: ki indította, mikor kezdődött és ért véget, és mit módosított (14 · 14.6).
  - A módosítás a lap nevével áll, mellette zárójelben a művelet technikai neve: „Diagnózis · saját értékek (save)”.
  - A befejezés oka is látszik: Kilépés, az admin kijelentkezett, lejárt az admin munkamenete, vagy félbehagyva.
  - Ha az admin Kilépés nélkül megy el, a munkamenet 6 óra után (az admin tétlenségi ideje, 01) magától lezárul, „félbehagyva” okkal. A vége az utolsó kérés ideje (`close:impersonations`, `cron.md`). Utána a böngészője sem dolgozhat tovább a felhasználó nevében.
- felvétel törlése a felvétel napjával (31.5), hozzájárulás megadása és visszavonása a fajtájával és a diagnózissal (31.6), e-mail cím módosítása (31), admin naptár-betöltése a forrással (09);
- fióktörlés (31.7); ha szuperadmin archiválta, ő áll a „Fióktörlés kérve” sorban;
- a fiók inaktiválása és aktiválása az adminnal, aki tette (33.2).

A napló üzleti adat, nem log: a technikai logok 365 napos szabálya (NFR) nem vonatkozik rá. Addig marad meg, amíg a szervezet, amelyben történt: a szervezet törlésével a sorai is törlődnek. A szervezethez nem köthető sorok (e-mail-csere, „nevében” kezdés és befejezés, fióktörlés) a felhasználóhoz tartoznak (33.7).

### 33.7 Törölt felhasználó

- A törlés után (31.7) az adatlap csak a naplót mutatja, név és e-mail nélkül: „Törölt felhasználó · {belső azonosító} · {dátum}”.
- A napló sorai megmaradnak, mert vita esetén tudni kell, mi történt és mikor (ahogy a jogi dokumentumok verzióinál, 05). A személyes adat (név, e-mail, a diagnózis és a szervezet neve) kikerül belőlük, a felhasználót csak a belső azonosító jelöli.
- A magánszemély szervezete a fiókkal együtt törlődik (31). Ilyenkor a napló sem marad meg: akinek a törlés után nem marad szervezete, annak a naplója is törlődik.
- A szervezetekben és a diagnózisokban a helyén „Törölt felhasználó · #{belső azonosító}” áll (31.7).

### 33.8 Ügyfelek lista

- Bal oldali menüpont: *Felhasználók* a *Szervezetek* csoportban, az NFR listaszabályai szerint.
- Oszlopok: név, e-mail, szervezet(ek), állapot, utolsó belépés. Törölt felhasználónál az e-mail helyén „–” áll.
- Kereső: név és e-mail; a teljes névre is, bármilyen sorrendben („Kovács Anna”, „Anna Kovács”). Szűrők: szervezet, állapot, „csak Értéktükör” (van megerősített Értéktükre, de nincs fő diagnózisa).
- Állapot: Aktív, Meghívás alatt, Inaktív, Archivált. Archivált az, akinek a fióktörlése elindult: akár ő kérte (31.7), akár szuperadmin archiválta. Az archivált felhasználó a listán marad, a törlés után „Törölt felhasználó”-ként (33.7).
- A sor az adatlapra nyílik. Új felhasználót innen nem lehet felvenni, az a szervezeten át megy (02).
- Sorműveletek: belépés a nevében, inaktiválás vagy aktiválás, archiválás (33.2).
  - Mindhárom menüpont mindig látszik. Ami az adott adminnak vagy felhasználónál nem érhető el, az szürke, és ráállva megmutatja az okát. Ilyen ok: csak szuperadmin archiválhat; admin fiók; archivált; inaktív; még nem regisztrált.
  - A szerver is ellenőrzi a jogosultságot.

## Nincs benne

- Globális audit-napló az adminban (01).
- A felhasználó válaszainak (megélés, saját értékek) kiírása a lapon: a részdiagnózisok linkjein érhetők el (17.4). Így az adatlap áttekintő marad, a tartalom pedig ott látszik, ahol a diagnózis összefüggésében érthető.
