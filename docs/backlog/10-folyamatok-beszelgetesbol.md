# 10 · Folyamatok – detektálás beszélgetésből

## Cél

Ha a felhasználó nem köti össze a naptárát, vagy az összekötés nem sikerül, a Coach négy kérdésére válaszol (V6·c). Ugyanarra a detektált folyamatok listára jut, mint a naptárral (V6·d, 11). Ez az út lassabb, de nem másodrangú.

**Belépők:**

- a V6 „Beszélgetésből” kártyája;
- a V6·b „Inkább beszélgetünk” gombja;
- a sikertelen naptár-összekötés (09).

## Alfeladatok

### 10.1 A négy kérdés

A kérdések fix sorrendben, egy lapon jelennek meg („B · 2 / 4”, 6–8 perc). A szöveget a mégis adja, és nyelvi fájlban tároljuk.

1. Mi az a heti vagy kétheti alkalom, ami ritkán marad el? (a rutin)
2. Hol dőlnek el nálatok a döntések? (a valódi rutin, nem a szervezeti ábra szerinti)
3. Mi az, ami mindig újrakezdődik – ugyanaz a kör, más emberekkel? (alfolyamat, ügyfélfolyamat)
4. Melyik alkalom után érzed azt, hogy ez így nem jó? (a töréspont jelöltje)

- **Válasz:** beírva, vagy elmondva a 06-os felvevővel. Az átirat a mezőbe kerül, és szerkeszthető.
- Egy kérdés kihagyható, de a továbblépéshez legalább egy válasz kell.
- Követő kérdés nincs.

### 10.2 Élő kivonat

- Minden válasz után egy AI-hívás kivonatolja a folyamatokat.
- Az eredmény az „Ami továbbmegy” blokkban látszik. A fejléc számol, például: „2 folyamat · 1 tisztázandó”.
- Kétféle chip van:
  - **Folyamat** (teli chip): a név, és ha elhangzott, a ritmus és a létszám (pl. „keddenként, négyen”).
  - **Tisztázandó** (szaggatott chip): amit a válaszból mintának hallunk, nem alkalomnak (pl. „döntés négyszemközt?”).
- A chip ×-szel kivehető.
- A 4. kérdés válaszában megnevezett folyamat **töréspont-jelölt** jelzést kap. Ezt csak az admin látja (11).
- A modell- és a promptverziót mentjük (NFR 11). A hangfelvétel tárolása a 06 · 6.6 szerint működik.

### 10.3 Tovább a listára

- A 4. kérdés után a „Válasz és tovább” gomb a V6·d-re visz (11).
- A folyamat-chipekből kártya lesz, „beszélgetésből” forrással. A tisztázandókból a „Bizonytalan” csoportba kerülő kártya lesz.
- Ha a ritmus és a létszám nem hangzott el, üresen marad. Az adatlapon (V6·e) pótolható.
- A válaszok mentődnek, a beszélgetés később folytatható.
- Az „Inkább a naptárral” gomb a V6·b-re visz (09).

### 10.4 Ha később a naptár is jön

- A vezető később is összekötheti a naptárát (09).
- Ilyenkor az AI a naptár mintáit név és ritmus alapján a beszélgetésből jött folyamatokhoz illeszti.
- Az egymáshoz illő párokat a V6·d egy kártyán mutatja, „összevonás?” javaslattal. A vezető dönt.

## Nincs benne

- Valódi chat követő kérdésekkel.
- A Coach általános felülete (M5): [NM-18](../backlog_nem_MVP/NM-18-coach.md).
