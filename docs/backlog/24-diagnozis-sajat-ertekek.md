# 24 · Diagnózis – saját értékek

## Cél

A vezető és a társ megadja a saját értékeit (V2–V2·c, a társnál T3–T3·c). A saját értékekből jön:

- az Egyén nézet (18.5);
- az Útravaló „A te értéked” sora (29);
- a „a tiéd is” jelölés a szervezet értékeinél (08 · 8.1);
- magánszemélynél a szervezet értékei is (08).

Kapcsolódik: 03, 06, 08, 17.

## Alfeladatok

### 24.1 Honnan (V2)

- Kérdés: „Miben nem alkuszol, akkor sem, ha kényelmetlen?”
- Három egyenrangú út, mindegyik ugyanabba a listába fut (24.2):
  - **„Az Értéktükörből hozom · {dátum}”:** csak akkor jelenik meg, ha volt tükre (06). Alatta: „{n} érték, idézetekkel · te döntöd el, mi marad.”
  - **„Elmondom”:** egy helyzet a hétről, kb. 2 perc, a 06-os felvevővel. Ez egy Értéktükör (06 · 6.2), a diagnózis részdiagnózisa lesz (17.4). A végén a detektált értékekkel tér vissza ide.
  - **„Hozzáadom vagy kiválasztom”:** a 24.3.

### 24.2 A lista (V2·b)

- Fejléc: „Ha továbblépsz, ezek lesznek a saját értékeid.” Alatta: „{n} érték · ami nem a tiéd, vedd ki.”
- **Egy érték egy kártya:**
  - név és állapot (24.4);
  - „Mit jelent neked”;
  - „Például”: egy idézet vagy példa;
  - forrás-sor, pl. „az Értéktükörből · 3 helyzetedből 1-ben”, vagy „hozzáadva · még nincs róla helyzet”.
- A × kiveszi az értéket a listából.
- „+ Új érték” a lista alján.

### 24.3 Érték hozzáadása (V2·c)

- **Felül két út:** „Elmondom” (a 06-os felvevő) és „Az Értéktárból választom” (03, aktív és átnézett értékek). Mindkettő kitöltve érkezik ide. Az alap út a beírás.
- **Mezők:**
  - Érték (név);
  - Mit jelent számodra?;
  - Mit nem jelent?;
  - 1 · Mikor érzed, hogy nagyon nem teljesül?;
  - 6 · Mikor érzed, hogy nagyon teljesül?
- **Értéktárból választva** a mezők az érték aktuális verziójából előre kitöltve érkeznek, és átírhatók. Az Értéktár értéke ettől nem változik.
- **Gombok:** „Hozzáadom” és „Mégse”.
- AI-javaslat a mezőkben nincs: [NM-08](../backlog_nem_MVP/NM-08-ai-javaslatok-ertekeknel.md).

### 24.4 Állapot

- Három állapot: **erősen jelen**, **részben**, **még nincs beépítve**.
- Az állapotot a felhasználó saját anyagai (Értéktükrök, elmondott helyzetek) adják, nem ő pontozza:
  - **erősen jelen:** az anyagai legalább kétharmadában megjelenik az érték;
  - **részben:** legalább egyben megjelenik;
  - **még nincs beépítve:** egyikben sem.
- Az illesztés a 06-os motor B lépcsője szerint megy, az Értéktár értékére. Ha az értéket nem az Értéktárból választotta, névegyezésre nem illesztünk (06 · 6.4); ilyenkor a kártyán „még nincs róla helyzet” áll.

### 24.5 Átvétel az Értéktükörből

- A fiókhoz kötött Értéktükör (06) értékei és idézetei átjönnek.
- Ami az ET3-on „nem az enyém” jelölést kapott, nem jön át.
- Az Értéktárra illesztett érték az Értéktár értékeként jön. A nem illeszkedő érték „AI által detektált” értékként jön (03 · 3.7), és a felhasználó választhatja, mert nála keletkezett.

### 24.6 A társnál (T3–T3·c)

- Ugyanaz a komponens, a társ avatárjával és „1 / 2” lépésszámlálóval.
- A végén a megélés jön (T4, 25).

## Nincs benne

- AI-javaslatok a mezőkben és az „Így nézne ki”, „Egy első lépés” szövegek: [NM-08](../backlog_nem_MVP/NM-08-ai-javaslatok-ertekeknel.md).
- Az Értéktükör elmélyülése (ET4): [NM-06](../backlog_nem_MVP/NM-06-ertektukor-elmelyules.md).
- A saját értékek nézete a Tárak alatt (M2): 30.

## Nyitott kérdések

1. **Hány saját érték lehet?** A prototípus hármat mutat.

   **Javaslat:** legalább 1, legfeljebb 5, a felület hármat ajánl („Három érték elég”). Magánszemélynél 3–5, mert itt a saját értékek a szervezet értékei is (08).

2. **Kötelező mezők.**

   **Javaslat:** a név és a „Mit jelent számodra?” kötelező. Az 1 és a 6 opcionális, mert a diagnózis mércéje a szervezet értékei, nem a saját értékek. Kivétel a magánszemély: ott a saját értékek a szervezet értékei is, ezért ott az 1 és a 6 is kötelező, ahogy a 08-ban.

3. **Új érték az Értéktárba?** A szervezet új értéke „Szervezet által felvett” címkével az Értéktárba kerül (03 · 3.7).

   **Javaslat:** a felhasználó új értéke is kerüljön be, új eredet-címkével: „Felhasználó által felvett”. A 3.7 szabályai szerint, átnézésig csak ő választhatja. Így látszik, mi hiányzik az Értéktárból.

4. **Melyik Értéktükör jön át?**

   **Javaslat:** a legutóbbi, a dátumával. Ha a diagnózis alatt újat csinál, a lista magától nem változik. A V2·b tetején egy sor jelenik meg: „Új tükröd van ({dátum}) · Átnézem”, ami az új értékeket felkínálja.

5. **Szerkeszthetők-e a saját értékek a diagnózison kívül?**

   **Javaslat:** igen, a Tárak → Értéktár (M2, 30) alól bármikor. A diagnózisban a publikáláskor rögzül, mi volt a lista (21.3). Ezért a saját értékekhez is kell verzió, ahogy a szervezet értékszettjéhez (08 · 8.4).
