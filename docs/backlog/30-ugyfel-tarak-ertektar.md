# 30 · Ügyféloldal – Tárak: Értéktár

## Cél

Az ügyfél Értéktára (M2) egy lapon mutatja:

- a szervezet értékeit a saját definíciójukkal, és ha van publikált diagnózis, a pontjukkal;
- a felhasználó saját értékeit (24);
- a mégis Értéktárát (03) böngészhetően.

Ez az ET5 ígérete is: „részletesen az Értéktárban, és még {n} érték böngészhetően”.

**Hol érhető el:**

- a menüben: *Tárak → Értéktár* a vezetőnek, *Értéktár* a társnak (15 · 15.3);
- a társ kezdőlapjáról (25.5);
- belépéskor annak, aki csak Értéktükröt csinált (14 · 14.4).

## Alfeladatok

### 30.1 A lap

- **Fejléc:** cream (tárak, 15 · 15.2). „Értéktár”, a kérdés: „Mit jelentenek nálatok az értékek?”
- **Három blokk, ebben a sorrendben:**
  - **A szervezet értékei** (30.2);
  - **A saját értékeid** (30.3);
  - **Az Értéktár** (30.4).
- Magánszemélynél az első két blokk egy: a saját értékek a szervezet értékei is (08).
- Akinek nincs szervezeti diagnózisa (csak Értéktükröt csinált), annál az első blokk nem jelenik meg.

### 30.2 A szervezet értékei

- **Fejléc-sor:** „{n} érték · {Név} definíciója · {dátum}”, az értékszett aktuális verziójából (08 · 8.4).
- **Soronként:**
  - a név és a „Mit jelent” mondat;
  - az 1 és a 6 egy-egy rövid mondatban („Az 1: a folyosón mondjuk. A 6: a körben.”);
  - a pont a legutóbbi publikált fő diagnózisból (lásd 1. kérdés), nyíllal az érték fejezet-lapjára (27 · R2·b).
- **Ki látja a pontot:** csak az, aki a riportot látja (27.1). A társ és a partner pont nélkül látja ugyanezt a listát.
- **Lenyitva:** Mit jelent · Mit nem jelent · 1 · 6 · Példa.
- **„Szerkesztem” gomb:** csak a „szervezet vezetője” címkéjű felhasználónak, a 08 szabályaival. Mindenki más csak olvassa.

### 30.3 A saját értékeid

- A 24 kártyái, állapottal (24.4).
- **„Szerkesztem”:** a 24 listája nyílik, „+ Új érték”-kel.
- A futó diagnózisban a változás a publikálásig számít, utána a diagnózis a rögzített listát mutatja (21.3).

### 30.4 Az Értéktár

- Az Értéktár (03) aktív és átnézett értékei, csak olvashatóan.
- Kereső (név, szinonimák, kulcsszavak) és kategória-szűrő (03 · 3.4).
- **Egy érték lapja:** Mit jelent · Mit nem jelent · 1 · 6 · Példák, az aktuális verzióból.
- **„Hozzáadom a sajátjaimhoz”:** a 24 · 24.3 űrlapja nyílik, előre kitöltve.

## Nincs benne

- A szervezet értékeinek szerkesztése: 08.
- A Képességtár (M3): [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- A Folyamatok tár (M4): 11 · 11.5.

## Nyitott kérdések

1. **Melyik diagnózis pontja látszik?** Egy szervezetnek több fő diagnózisa is lehet (17.6).

   **Javaslat:** a néző legutóbbi publikált fő diagnózisáé, Csapat nézetben, a dátummal („04.18-i diagnózis”). A szervezet vezetője, aki egyik diagnózisban sem vezető, a szervezet legutóbbi publikált fő diagnózisát látja.

2. **Mi van, ha a definíció a diagnózis óta változott?** A pont a diagnózisban rögzített definícióhoz tartozik (08 · 8.4), a lap viszont az aktuálisat mutatja.

   **Javaslat:** ha a verzió eltér, a pont mellett halk szöveg jelzi: „A {dátum}-i diagnózis a korábbi definícióval mért.” A nyíl a fejezet-lapra visz, ahol a rögzített definíció látszik.

3. **Látja-e a társ a szervezet értékeinek 1-es és 6-os mondatát?** A prototípus szerint „a társ Értéktára ugyanez, pont nélkül”.

   **Javaslat:** igen, mindent lát a pont kivételével. A T4 megéléshez is ez kell neki (25.4).

4. **Az Értéktár-blokk a szervezet értékeinek „+ Új érték” útjához.** A szervezet vezetője a 08 · 8.1-ben is az Értéktárból választ.

   **Javaslat:** a 30.4 böngészője és a 08 választója ugyanaz a komponens legyen, csak más gombbal: „Hozzáadom a sajátjaimhoz”, illetve „Hozzáadom a szervezet értékeihez”.
