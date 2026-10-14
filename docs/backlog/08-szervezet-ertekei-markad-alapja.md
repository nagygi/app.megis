# 08 · A márkád alapja – a szervezet értékei

## Cél

A szervezet vezetője megadja a szervezet értékeit (V3) és a márkád alapjának négy elemét (V4), majd egy lapon értékeli, hogyan éli meg őket (V5). A szervezet értékei a diagnózis mércéje: a riport minden pontja ehhez mér (R2·b).

**Ki használhatja**

- A kliens oldalon a **szervezet vezetője** címkéjű felhasználó (02).
- Az adminban az admin, a szervezet *Értékek* fülén (8.5).
- A többi felhasználó csak olvashatja.

**Hol jelenik meg**

- A diagnózis kitöltésének lépései között (V1 szerint a 2–4. lépés). Ha a diagnózis vezetője nem „szervezet vezetője”, a 2. és a 3. lépést csak olvassa (17, 23).
- Később bármikor a *Tárak → Értéktár* (M2, 30) alól, „Szerkesztem” gombbal.

## Alfeladatok

### 8.1 A szervezet értékei (V3)

- **Darabszám:** legalább 3, legfeljebb 5 érték. A „Tovább” gomb 3 érték alatt szürke, 5 után az „+ Új érték” gomb szürke.
- **Egy érték egy kártya.** Rajta: név, „Mit jelent nálatok?”, kiemelve egy példa. A kártya lenyitva szerkeszthető, a mezők ugyanazok, mint a V2·c-n:
  - Mit jelent?
  - Mit nem jelent?
  - 1 · Mikor nagyon nem teljesül? (kötelező)
  - 6 · Mikor teljesül nagyon? (kötelező)
  - Példa
- **Hozzáadás** két úton:
  - **Az Értéktárból választom:** az Értéktár (03) aktív értékei közül. A mezők az érték aktuális verziójából előre kitöltve érkeznek, és a szervezet saját szavaira átírhatók. Az Értéktár értéke ettől nem változik: az átírás a szervezet saját definíciója lesz.
  - **Új érték:** név és a fenti mezők.
    - Az új érték az Értéktárba is bekerül, **„Szervezet által felvett”** címkével (03 · 3.7).
    - Más szervezet vagy felhasználó csak akkor választhatja, ha az admin átnézte.
- **Bevitel:** minden szöveges mező beírható, vagy a 06-os felvevővel elmondható. Az átirat bekerül a mezőbe, és szerkeszthető.
- A × kiveszi az értéket a listából.
- **Ha egy érték a vezető saját értékei között is szerepel,** a kártyán „a tiéd is” jelölés látszik. Ehhez a saját értékek kellenek (V2, 24).
- **Magánszemély típusú szervezetnél** (02) a szervezet értékei a saját értékek. A V3 a V2-ből töltődik, amint a V2 elkészül.
- Mező elhagyásakor a felület automatikusan ment, külön mentés gomb nincs.

### 8.2 A márkád alapja (V4)

Négy doboz, ugyanazzal a mintával: jel és cím, alatta szürkén a kérdés, alatta a válasz.

| Elem | Kérdés | Válasz |
|---|---|---|
| Értékek (gyökér) | Mik a szervezet értékei? | Automatikusan a V3 értékei, felsorolva |
| Purpose (törzs) | Miért csináljátok? | Szöveg |
| Értékajánlat (korona) | Mit kap tőletek, aki veletek dolgozik? | Szöveg |
| Impact (árnyék) | Mi marad utánatok? | Szöveg |

- Üres mezőben szürke, dőlt példaszöveg jelenik meg, mellette mikrofon (06-os felvevő). A válasz beírható vagy elmondható, legfeljebb 500 karakter.
- Mező elhagyásakor a felület ment. A kész doboz bal alsó sarkában zöld pipa és „kész”, jobb felső sarkában „Szerkesztem”.
- Mind a négy kötelező. A „Tovább” addig szürke, a fejléc számol: „3 / 4 kész”.
- A V4·b lap (AI-mondatjavaslat) nincs az MVP-ben: [NM-08](../backlog_nem_MVP/NM-08-ai-javaslatok-ertekeknel.md). A vezető válasza maga az elem mondata.

### 8.3 Nézzük egyben – megélés (V5)

- **Egy lapon:**
  - a szervezet értékei, a saját mondatukkal;
  - a márkád alapjának négy eleme;
  - soronként egy csúszka: 1–6 vagy „nem tudom”.
- **Minden sorra válasz kell,** a fejléc számol: „9 / 9 megválaszolva”.
- **Beküldéskor felhasználói diagnózis jön létre** (04):
  - Diagnózis típusa: **Megélés**.
  - Készítő: Automatikus.
  - Státusz: Jóváhagyásra vár.
  - A sorok az értékek és a fa négy eleme. Csak a „saját megélés” van kitöltve, mérőszám nincs.
- **A V5 a gyűrűbe nem számít.** A riport rését adja: „5-nek látjátok, 2-t mutattok”.
- **Ha fut fő diagnózis,** a megélés annak részdiagnózisa lesz (17 · 17.4).

### 8.4 Módosítás és verziók

- A szervezet értékei és a márkád alapja együtt egy **értékszett**. Minden változást tartalmazó mentés új verziót hoz létre.
- Ahol használják (diagnózis, AI elemzés), ott rögzítjük, melyik verziót. A futó diagnózist az új verzió nem változtatja meg, ahogy az Értéktárban is (03 · 3.3).
- Módosításkor ez a figyelmeztetés jelenik meg: „A korábbi értékek nem törlődnek. Az új diagnózisok az új értékek szerint futnak.”

### 8.5 Admin: a szervezet *Értékek* füle (02)

- A szervezet oldalán negyedik fül: *Adatok · Csapatok · Felhasználók · Értékek · Folyamatok* (a *Folyamatok* fül: 11).
- Ugyanazok a mezők és szabályok, mint a kliens oldalon: 3–5 érték, a márkád alapja, verziók.
- **Verziók:** listában látszik, ki és mikor mentette, a régi verziók csak olvasható nézetben nyithatók meg.

## Nincs benne

- AI-javaslatok (V2·c mezőjavaslat, V4·b mondatjavaslat): [NM-08](../backlog_nem_MVP/NM-08-ai-javaslatok-ertekeknel.md).
- A saját értékek (V2): 24.
- A társ megélése (T4): 25, ugyanezzel a diagnózis-mechanizmussal.
- A csapat saját értékei.
- A márkád alapja további öt eleme (talaj, micélium, évgyűrűk, a kör, illat): [NM-14](../backlog_nem_MVP/NM-14-fa-tovabbi-ot-eleme.md).
- Az értékmérés motorja (06) illesztése a szervezet értékeire: a folyamatoknál a 13-as, a diagnózis többi anyagánál a 18-as jegyben.
