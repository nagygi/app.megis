# 23 · Diagnózis – Mi vár rád, a kitöltés kerete, Ki lát mit

## Cél

A vezető útja az első belépéstől az „Indul a diagnózis” gombig: a lépéslista (V1), a lépések közötti keret, a folytatás (V1·b) és a zárólap (V9). A lépések tartalmát a saját jegyeik adják:

- saját értékek: 24;
- a szervezet értékei, a márkád alapja, a megélés: 08;
- képességek: [NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md) (1.32);
- folyamatok: 09–11.

**Ki használja:** a fő diagnózis vezetője (17). A társ útja: 25.

## Alfeladatok

### 23.1 Mi vár rád (V1)

- **Fejléc:** „Mi vár rád”, alatta: „{n} lépés, és indul a diagnózis.” (snapshotnál „indul a snapshot”). Kísérősor: „kb. {perc} perc · {kezdés}-ig”. Ha nincs kezdés napja: „egyeztetés alatt”.
- **Lépések:**
  - a 17.3 szerint, sorrendben, mindegyik mellett az idő (pl. „Saját értékek · 2 perc”);
  - a percek konfigurációból jönnek, a fejléc ezek összegét mutatja;
  - pöttyök: coral az aktuális, pipa a kész, szürke a hátralévő.
- **Gombok:** „Kezdjük” (elsődleges) és „Bővebben” (szöveges link a Bölcsességtárba, BT6, 32).
- **A vég képe:** alatta szürkén, nem kattinthatóan, „A végén · {publikálás}” felirattal (lásd 2. kérdés).
- **Belépéskor** (14 · 14.4) a kitöltés alatt mindig ez a lap nyílik.

### 23.2 Folytatás (V1·b)

- Kilépés után a V1 ezt mutatja: „Innen folytatod.” és „{k} / {n} kész · kb. {perc} perc van hátra”.
- Gomb: „Folytatom”, ez a coral lépésre visz.
- A kész lépések kattinthatók és javíthatók (lásd 3. kérdés).

### 23.3 A lépések kerete

- **Fejléc** (15 · 15.2): vissza nyíl, a lap neve, lépésszámláló („3 / 6”), avatár. Cream szín (bevitel).
- **Navigáció:** minden lépés a végén a következőre visz, a fejléc nyila az előzőre.
- **Mentés:** mező elhagyásakor automatikus, „Mentve” visszajelzéssel (15 · 15.8).
- **Mikor kész egy lépés:** amikor a saját jegye szerint teljes (pl. 08: 3–5 érték, mind a négy elem, minden megélés-sor). A Folyamatok (11 · 11.1) és a Képességek lépés „Kihagyom”-mal is kész.
- **Csak olvasható lépés** (17.3: a vezető nem „szervezet vezetője”):
  - a lap a szervezet meglévő értékeit, illetve a márkád alapját mutatja, „A szervezet értékeit {Név} adta meg” sorral;
  - egyetlen gombja: „Tovább”;
  - akkor kész, ha a vezető megnézte.

### 23.4 Ki lát mit (V9)

Egy lap, a diagnózis végéig érvényes. Szereplőnként két sor: mit lát, és mit nem.

- **Te:**
  - látod: a riportot, a gyűrűt, minden fejezetet, és a társak idézeteit szereppel és dátummal, név nélkül, ha az admin bekapcsolta (17.2);
  - nem látod: a társak nevét az idézetek mellett.
- **A társak:**
  - látják: a saját válaszaikat, a saját nézetüket és az Útravalót;
  - nem látják: a riportot.
- **A mégis:**
  - lát mindent, névvel;
  - a publikálásig olvas, utána csak a te kérésedre.
- Magánszemélynél és társ nélküli terméknél a társak sora nem jelenik meg.
- **Egy gomb:** „Indul a diagnózis”.
  - A szakasz „Fut” lesz (17.5), a kezdőlap a V10 (26).
  - A társak megkapják a meghívójukat (25).
  - Ha egy kötelező lépés még nincs kész, a gomb szürke, és a hiányzó lépésre visz.

## Nincs benne

- A társak lépése (V8): [NM-16](../backlog_nem_MVP/NM-16-tarsak-meghivasa-vezetotol.md).
- A csatornák lépése (V7): egyeztetés alatt.
- A Coach sora és kapcsolója a V9-en: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- A kezdőlap a futás alatt és után: 26.

## Nyitott kérdések

1. **Időbecslés.** A V7 és a V8 nélkül a Teljes csapat-diagnózis hat lépés, nagyjából 13 perc. A prototípus „kb. 17 perc”-et ír.

   **Javaslat:** a perceket lépésenként konfigurációban tartjuk (Saját értékek 2, A szervezet értékei 3, A márkád alapja 2, A megélésed 3, Folyamatok 2, Ki lát mit 1), a fejléc ezek összegét mutatja. Csak olvasható lépésnél 1 perc.

2. **A vég képe Brand és képesség nélkül.** A prototípus két gyűrűt, a „Nyolc fejezet” sort és a Bridge-et mutatja, ezek MVP-ben nincsenek.

   **Javaslat:**
   - Teljesnél: egy gyűrű kérdőjellel („Belső működés”), „A legnagyobb rés · amit látni véltek, és amit mutattok”, és „Négy fejezet · értékek · folyamatok · területek · a márkád alapja”.
   - Belső snapshotnál: a gyűrű, a legnagyobb rés és „Egy lap”.

3. **Meddig javíthatók a kész lépések?** A V1·b szerint a kész lépés javítható. A futás alatt viszont a kezdőlap már a V10.

   **Javaslat:** a kész lépések az olvasat indításáig javíthatók (17.5). A futás alatt a V10 „Onboarding” sorára koppintva a lépéslista nyílik meg. Az olvasattól kezdve csak olvashatók.

4. **A kimaradó lépések a snapshotnál.** A prototípus V1·c lapja áthúzva mutatja a kimaradt lépéseket („Csatornák · Külső”, „Társak · Teljes”), mint kedvcsinálót.

   **Javaslat:** ne jelenjenek meg. A csatornák egyeztetés alatt állnak, a társak pedig MVP-ben nem vezető-lépés, így félrevezető lenne.

5. **Mi kötelező az „Indul a diagnózis” előtt?**

   **Javaslat:** minden lépés kész, a Folyamatok „Kihagyom”-mal is. Ha a vezető a kezdés napja előtt végez, a diagnózis azonnal fut.

6. **Ki lát mit: módosíthat-e rajta a vezető?** A prototípus szerint a vezető a Coachot elrejtheti, a többi beállítás a setupé.

   **Javaslat:** MVP-ben a lap csak tájékoztat, kapcsoló nincs rajta. Az idézetek láthatóságát az admin állítja (17.2). A Profilból (31) is elérhető, csak olvashatóan.
