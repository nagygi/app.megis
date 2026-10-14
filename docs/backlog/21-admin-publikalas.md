# 21 · Admin felület – publikálás

## Cél

A 3. kapu (A4). Az admin előnézetben látja, amit a vezető és a társak kapnak. Egy ellenőrzőlistával meggyőződik róla, hogy minden kész, és egy gombbal publikál. A vezető e-mailt kap, a társak az Útravalójukat.

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 21.1 Előnézet

A fő diagnózis *Publikálás* füle (19.1).

- **Balra a vezető nézete**, telefon-szélességben, kattintható: a kezdőlap (V12, snapshotnál V12·b) és a riport.
- **Jobbra a társak Útravalója** (T6), egy választóval társanként.
- Az előnézet ugyanaz a nézet, ami publikáláskor kimegy, nem másolat.

### 21.2 Ellenőrzőlista

Négy sor. Amíg nincs meg mind, a „Publikálom” gomb szürke, és a hiányzó sor a helyére visz:

- minden mérőpont jóváhagyva (20);
- minden állítás és szöveg jóváhagyva (20);
- nincs besorolatlan anyag a diagnózisban (22);
- a küszöb rendben van, vagy tudatosan felül van írva (19.4).

### 21.3 Publikálás

- „Publikálom” gomb, megerősítéssel.
- A fő diagnózis és minden részdiagnózisa „Publikált” lesz, a szakasz „Publikálva” (17.5). Rögzül, ki és mikor publikálta.
- A riport a publikálás napjára fagy („diagnózis-nézet · 04.18.”).
- E-mail megy a vezetőnek, és a társaknak az Útravalóval.
- Ami ettől kezdve csak olvasható:
  - a saját értékek és a megélés (V2, V5, T3, T4);
  - a helyzetek és a feltöltések (12 · 12.5);
  - a diagnózishoz rögzített folyamatlista (11 · 11.5) és a Folyamat-diagnózisok (13 · 13.4);
  - a hozzájárulás erre a diagnózisra már nem vonható vissza (T1, M6).
- A tárak (Értéktár 08, Folyamatok 11.5) tovább szerkeszthetők, de a publikált diagnózist nem változtatják meg (08 · 8.4).

### 21.4 Javítás publikálás után

- Az állítások, az olvasat szövegei és a nyomok kiválasztása publikálás után is javíthatók.
- Javítás után a fejezet fejlécén ez látszik: „frissítve · {dátum}” (R2·c).
- A PHI is átírható, kötelező indoklással (20.2). Az indoklás a Mellékletbe kerül (28.2), a gyűrű-% újraszámolódik, és a fejezeten „frissítve” jelzés áll.
- Erre épül a visszajelzések beépítése (22).

### 21.5 Visszavonás

- A publikált fő diagnózis visszavonható (04), megerősítéssel és kötelező indoklással. Rögzül, ki, mikor és miért vonta vissza.
- A fő diagnózis és a részdiagnózisai Draftba kerülnek, a szakasz „Olvasat” lesz (17.5). A jóváhagyások megmaradnak, a sorok újranyithatók (20).
- A vezető és a társak elől eltűnik a riport és az Útravaló, a kezdőlap az olvasat állapotát mutatja (26).
- A 21.3 zárolásai érvényben maradnak: a vezető és a társak nem kezdenek újra kitölteni.
- Újrapublikáláskor nem megy ki újra e-mail.

## E-mailek

- **Vezető:** „Elkészült a {Csapat} diagnózisa. [Megnézem] Ha átbeszélnéd velünk: [Időpontot foglalok]”
- **Társ:** „Elkészült a {Csapat} diagnózisa, és vele az Útravalód: a te értékeid, ahogy a helyzeteidben látszanak. [Megnézem]”

## Nincs benne

- Ütemezett publikálás (lásd 3. kérdés).
- A kezdőlap, a riport, az Útravaló és a PDF: 26–29.

## Nyitott kérdések

1. **Értesüljön-e a vezető a visszavonásról?**

   **Javaslat:** e-mail ne menjen. A kezdőlapon (26) egy sor jelenik meg: „A mégis javítja a diagnózisotokat. Hamarosan újra elérhető.” Ha a javítás érdemi, a kapcsolattartó személyesen szól.

2. **Ki kap Útravalót?** A prototípus szerint az Útravaló a publikálással megy ki. Kap-e Útravalót az a társ, aki nem töltötte ki a megélését, vagy visszavonta a hozzájárulását?

   **Javaslat:** csak az kap, aki kitöltötte a T4-et és nem vonta vissza a hozzájárulását. A többiek nem kapnak e-mailt.

3. **Ütemezett publikálás.** A publikálás napja az A1-ben meg van adva.

   **Javaslat:** nincs automatikus publikálás, a dátum csak tájékoztató. Ha a nap elmúlt, és a diagnózis még nincs publikálva, a Beérkezőben (22) sor jelenik meg.

4. **Asztali előnézet.** A 15 asztali nézetet is kér.

   **Javaslat:** az előnézetben legyen egy telefon · asztali váltó.

5. **Belső snapshot és magánszemély.** A snapshot riportja egy lap (S1), és társ nélkül nincs Útravaló.

   **Javaslat:** ugyanez a fül, a jobb oldali Útravaló-rész nélkül. Az ellenőrzőlista küszöb-sora magánszemélynél és társ nélküli terméknél nem jelenik meg.
