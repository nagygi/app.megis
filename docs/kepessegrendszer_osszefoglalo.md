# Képességrendszer · összefoglaló a kapott anyagokból

2026.10.13. · Forrás: `docs/04 Capabilities` · Állapot: Átírási lista v14 (10.13.), tárak v0.4 a 0. hullám betöltése után

A mégis képességrendszeréről szól (jóváhagyó: Oszi). Arról, hogy milyen képességek kellenek, hogyan ismerjük fel és mérjük őket valós anyagban, és hogyan épül a tudásbázis. Az összefoglaló a fejlesztésnek készült. A backlogra vonatkozó következmények a 17. pontban vannak.

## 1. Röviden

- **Mi ez.** A képességrendszer a mégis közös tudás-, bizonyíték- és referenciarétege. Maga nem diagnosztizál és nem fejleszt. Ebből dolgozik a P2P diagnózis, az Értékmérés, a Bridge to Purpose, a P2P Operating System és a coach.
- **75 képesség.** A kiindulás a 72-es lista (RC-01–RC-72). Ezt három valós esetben (DXC, Kheiron, SZV) kipróbálták: „részben” működött. Szótárnak jó, teljes készletnek nem. Ezért jött 3 új tétel (ÚJ-01–03), összesen 75, 12 munkacsoportban. A csoportokat nem pontozzák.
- **Nyomokon áll a mérés.** A nyom szó szerinti idézet, forrással. Bizonyíték nélkül nincs állítás és nincs pont. Az első diagnózis állapotot és konfidenciát mond, érettséget (6 lépcső) csak a második diagnózistól.
- **Új előfeltétel-réteg.** E1–E9: döntési jog, gazda, védett idő stb. A próbán mindhárom esetben ezek döntöttek a kimenetről, nem a képességek.
- **A mester egy Google Sheet.** A „tárak v0.4” normalizált táblákból áll (Képesség, Minta, Bizonyíték, Kapcsolat, Találat, Esettár, Szabálykönyv…), és a megrendelő megadta hozzá az adatbázis-megfelelést. A prototípus JSON-ja ebből készül (F44: `kepessegtar-75.json`).
- **Hol tart (10.13.).** 12 csoportból 6 összefésülve és betöltve, a 0. hullám (3 képesség) is. 1 csoport jóváhagyásra vár, 5 van hátra, utána jön a Tesztpad és az átadás. A 75 képességből 5 „használt”, 35 „jelölt”, 35 „csak irodalom”.
- **A fejlesztésnek.** A 07-es jegy (Képességtár) a régi 72-es JSON-ra és az Értéktár mezőire épül, a megrendelő modellje ennél jóval gazdagabb. Néhány MVP-s jegyünk (0 = „nincs nyoma”, 3 fős küszöb) ütközik a megrendelő 09.26-i döntéseivel (17. pont).

## 2. Mi van a mappában

| Anyag | Mire való | Megjegyzés |
|---|---|---|
| Áttekintő v0.3 (09.26.) | Egy oldal külsősöknek | A négy szint és a v0.4 még nincs benne |
| **Részletes leírás v0.3.3** (10.05.) | A specifikáció: fogalmak, mezők, szabályok | A fejlesztéshez ez a fő forrás. Csak Változásnaplóval változik |
| Építési terv v0.3.3 (10.06., frissítve 10.11-ig) | Végrehajtás: 59 lépés, minden prompt, betöltési menet, Tesztpad | Hetente változik |
| **Átírási lista v14** (10.13.) | Döntésnapló, és az ebből következő átírások más dokumentumokban | A legfrissebb állapot innen olvasható |
| Tiltólista (10.05.) | Források, amelyekből nem idézünk | Egy tétel: egy DXC-interjú, és a rá épülő szintézisek |
| Tárak v0.4 (.xlsx) | A mester-Sheet letöltése | Mellette egy mentés (F23 előtt) és a lezárt v0.3.2 |
| A 72-es lista próbája (09.26.) | A Tesztpad 0. futása | Innen jött az előfeltétel-réteg és az ÚJ-01–03 |
| Capability Tükör v0.1 (09.26.) | Side project: ingyenes belépő nyilvános anyagból | 13. pont |
| Perplexity-promptok (09.27.) | A 12 csoport külső kutatásának promptjai | |
| `Eszközök/` | `tarak_v04.py` (build, load, rebuild, protect), `check_v04.py` | Python 3 + openpyxl |
| `Bemenetek/` | Esetek idézetgyűjtései, Perplexity-kutatások, 3 cikk | 14. pont |
| `Összefésülés · 0–2. hullám/` | Csoportonként kísérőlista és munkafüzet | 11. pont |
| `Tesztpad/` | DXC-próbák (10.10., 10.11.) | 12. pont |
| `99 ARCHIVE/` | Régi verziók, korai angol anyagok | 15. pont |

## 3. Mi a képességrendszer

**Képesség:** „Valami, amit a szervezet mostantól ismételhetően tud, és eddig nem tudott.” Nem csak készség, hanem az, hogy felismerik, mi számít, felelősen döntenek, együtt cselekednek, tanulnak, és nyomás alatt visszatalálnak.

**Két arca van:**
- folyamatoldal: szabályok, döntési utak, eszközök;
- emberoldal: tudás, gyakorlat, magabiztosság, együttműködés.

„Ha csak a folyamat van meg, papír lesz belőle. Ha csak az emberek tudják, egy távozással elveszik.”

**Négy döntés egy képességről:** létrehozzuk / megerősítjük / átadjuk (megvan, de nem ott, ahol kell) / leépítjük (árt, le kell tudni állítani).

**Nem képesség:**
- készség: egy ember tudása, a képesség emberoldalának eleme;
- előfeltétel (6.7);
- érték: az Értékmérés tárgya;
- eredmény vagy mutató;
- projekt.

**Kik használják:**

| Termék | Mit olvas, mit ad |
|---|---|
| P2P Teljes diagnózis | 3 fejlesztendő + 3 erősség, állapottal és konfidenciával, érettség nélkül. Bridge-indíthatóság blokk: ki viszi, van-e ideje, kinél a döntési jog |
| P2P Snapshot Belső | Egy ember, egy folyamat, önkép. Mutat állapotot, jellemzően alacsony konfidenciával („Kevés még az adat”) |
| P2P Snapshot Külső | 1 csatorna, 3–5 megjelenés. Csak „kívülről látható” minták, alacsony konfidencia |
| Második diagnózis | Terv és tény az előző 90 napról, és itt már érettség is |
| Értékmérés | A képesség a dashboardon, a riportban és a folyamat-kártyán (hordozza-e a rutin). Alkalmanként új nyomokat ír vissza |
| Bridge to Purpose | Képesség kiválasztása, Charta, nyomáspróba, záró mérés (8. pont) |
| P2P OS | A beépült képességek fenntartása. Ciklus-állapot: fut / indítható / a mégisnél / beépült / leállt |
| Coach | Bizonyítékkal válaszol („Miért lett ez így?”) |
| Capability Tükör | Tervezett, nyilvános anyagból. Csak jelet mond, állapotot nem |

**Hol tárolódik:**
- Az ügyféladat (nyom, mérőpont, állapot, találat) a termékben van, az ügyfél adatkezelése alatt („Elemzők vagyunk, nem rögzítők”).
- A képességrendszerbe csak anonimizált, szerep szerint hivatkozott példa kerül.

## 4. Fogalmak

| Fogalom | Jelentés |
|---|---|
| Alkalom | Egy folyamat egy előfordulása. Ez az atom |
| Nyom | Idézhető részlet egy alkalmon belül (mondat, döntés, jegyzet), forrással és dátummal |
| Mérőpont | Egy érték egy alkalmon, 1–6. **A 0 nem szám:** ahol nincs nyom, gondolatjel áll |
| Megjelenés | A kifelé irányuló oldal atomja (poszt, videó, interjú) |
| Bizonyíték | Gyűjtőszó (nyom és mérőpont). Szövegben használják, számként soha |
| Minta | Felismerési eszköz. Három típus: képesség-minta, előfeltétel-minta, detektor-hibaminta (6.6) |
| Találat | Egy minta egy ügyfélnél (alany, időszak), bizonyítékkal és konfidenciával |
| Állapot | Megvan-e most: jelen lehet / hiányzik / nincs nyoma / nincs adat |
| Konfidencia | Mennyire biztos az állapot: alacsony / közepes / magas |
| Érettség | Mennyire megbízhatóan működik, 6 lépcsőn. A második diagnózistól van |
| Lépcső | Egy diagnózis-válasz értéke egy mérőponton (1–6). Nem a képesség érettsége |
| Előfeltétel | Szerkezeti feltétel (E1–E9). Nélküle a képesség akkor sem működik, ha tudják |
| Csábító tétel | Szinte bármire ráhúzható képesség. Szigorúbb küszöb vonatkozik rá |
| Határfelület | Jelölés: két egység között (például menedzsment és csapat). Nem külön szint |
| Mikro szervezet | Jelölés: 1–3 fő, ahol a csapat- és a szervezeti szint összeesik |
| Nyers / feldolgozott | Amit valaki mondott, tett, pontozott / amit ebből valaki gondolt (összefoglaló, AI-kimenet, a mégis értelmezése) |
| mégis-eset | A mégis érintett (facilitátor, beszállító, szabálygazda). Mintát alapozhat, a „használt” státuszhoz nem számít |

**Szintek:** egyén, csapat, szervezet, ökoszisztéma. Az ökoszisztéma a szervezet kapcsolatai ügyfelekkel, partnerekkel, beszállítókkal, a közösséggel és a természettel. Minden képességnek van egy elsődleges szintje, de a megjelenést mind a négy szinten, mindkét oldalon kitöltik, ahol van rá anyag.

## 5. A 75 képesség

- **Azonosító:** RC-xx a 72-es listából, ÚJ-xx az új tételeknél. Az azonosító állandó, a név változhat.
- **Három új tétel** (a 72-es próbából):
  - ÚJ-01 Döntési jog és mandátum tisztázása;
  - ÚJ-02 Közös folyamat-nyelv;
  - ÚJ-03 Értékmegragadás és árazási modell.

**Státusz a tárban:**
- **használt:** legalább két független ügyféleset, mindegyikben saját, nyers jelenlét- vagy hiány-jel, és legalább egy anti-minta. Nem számít esetnek a határeset, az előfeltétel-jel, a régi lezárt esemény, a mégis-eset és a tiltólistás forrás.
- **jelölt:** egy esetben látták, vagy csak kutatás támasztja alá.
- **csak irodalom:** valós példa még nincs.
- **félretett:** összevonták vagy elvetették, indokkal.

**Jelleg:** képesség / készség-jellegű (később egy képesség emberoldalába kerülhet) / kérdéses.

**A 12 munkacsoport és az állapot (10.13.):**

| Csoport | Tételek | Használt | Jelölt | Csak irodalom | Összefésülés |
|---|---|---|---|---|---|
| Inner Grounding & Wellbeing | RC-01–06 | 1 | 5 | 0 | F21 kész |
| Ethical Agency & Power | RC-07–12 | 0 | 1 | 5 | F35 vár (RC-10 a 0. hullámban) |
| Systems & Complexity | RC-13–18 | 0 | 0 | 6 | F37 vár |
| Futures & Adaptability | RC-19–24, RC-37 | 0 | 2 | 5 | F39 vár (RC-24 a 0. hullámban) |
| Relational Intelligence | RC-25–30 | 0 | 0 | 6 | F41 vár |
| Teaming & Participation | RC-31–36 | 0 | 6 | 0 | F15 kész |
| Purpose & Strategy | RC-38–42, ÚJ-03 | 0 | 6 | 0 | F25 kész |
| Operating System & Execution | RC-43–48, ÚJ-02 | 2 | 5 | 0 | F17 kész |
| Learning & Innovation | RC-49–54 | 1 | 4 | 1 | F23 kész |
| AI, Data & Technology | RC-55–60 | 0 | 0 | 6 | F43 vár |
| Regenerative Value & Ecology | RC-61–66 | 0 | 0 | 6 | F33 jóváhagyásra vár |
| Governance, Risk & Accountability | RC-67–72, ÚJ-01 | 1 | 6 | 0 | F19 kész (ÚJ-01 a 0. hullámban) |

- **„Használt” (5):** RC-06, RC-45, RC-48, RC-52, RC-72. Az RC-52 és az RC-72 fenntartással.
- **Csábító (13):** RC-02, RC-05, RC-06, RC-24, RC-33, RC-39, RC-43, RC-46, RC-51, RC-52, RC-53, RC-70, RC-71. A 72-es próbában ezen felül az RC-13 és az RC-15 bizonyult csábítónak.
- **Áthelyezés:** az RC-37 a Purpose & Strategy csoportból a Futures & Adaptability csoportba került (10.08.).
- A teljes lista az A mellékletben van.

**Átnevezések:**
- RC-34: „Feszültség és érdek közvetlen tisztázása”;
- RC-70: „Nyomáspróba és szándékos kihívás”.

Az azonosító és az angol név maradt.

**Határ-szabályok** (a „mi nem ez” mezőkhöz és a detektorhoz), például:
- RC-43 vs RC-24: ciklusba áthelyezésnél RC-24;
- RC-24 vs RC-06: változás-teher vagy normál tempó;
- RC-47 vs RC-48: távozás vagy ideiglenes kiesés;
- ÚJ-03 vs E6: ha nem a helyi vezetés dönt az árról, az előfeltétel-jel.

**Megfigyelt hiányok** (még nem tételek): elismerés és ünneplés, belső változáskommunikáció, alapítói átadás, hatókör- és határkezelés, kapacitás- és függőség-láthatóság, összeférhetetlenség-kezelés, kísérletezésből tanulás stb.

## 6. Mérés: mikor mondunk ki valamit

### 6.1 Nyom és bizonyíték

**Honnan jöhet nyom:**
- beszélgetés (interjú, átirat, társak);
- Értéktükör;
- feltöltött anyag;
- naptár: cím, idő, gyakoriság, résztvevők, csatolmány soha;
- kifelé irányuló csatornák.

**Bizonyíték típusa:** megfigyelt viselkedés, munka-artefaktum, eredmény-mutató, érintett visszajelzése.

**Nyers vs feldolgozott:**
- A feldolgozott forrás csak megerősít, önállóan nem alapoz.
- Szintézisbe ágyazott idézet nyersnek számít, ha az eredeti visszakereshető.

**Forrásszabályok:**
- Az idézet a gondolat végéig tart, és az eredetivel egyeztetik.
- A gépi leirat beszélő-címkéjét tartalom alapján ellenőrzik.
- Az automatikus levél és az AI-val javított leirat feldolgozottnak számít.

**Különleges nyomok** (jelölik őket, önállóan nem alapoznak):
- vezetett válasz;
- harmadik személyről szóló állítás;
- régi, azóta javított esemény;
- önprezentáció;
- hiány-nyom: csak akkor nyom, ha a forrás a vizsgált időszakra teljes;
- pozitívumot kérő vagy marketing-célú kérdésre adott válasz;
- visszaadott vélemény („több embernél az visszajött”);
- audit vagy ESG-jelentés: az RC-67, RC-69–72 képességhez önmagában nem jelenlét-jel.

**A nyom rekordja** (javaslat):
- azonosító, alkalom, felvétel típusa;
- forrásfájl és hely (időbélyeg, oldal, sor), dátum;
- ki mondta: szerep, név csak mégis-nézetben;
- alany (személy / csapat / szervezet / ökoszisztéma / folyamat / esemény), szó szerinti részlet, kontextus;
- bizonyítéktípus;
- közvetlenség: közvetlen megfigyelés / önbevallás / más véleménye / szakértői értelmezés;
- jelleg és irány (+ / − / vegyes);
- nyers vagy feldolgozott;
- lehetséges képesség és minta, alternatív olvasat;
- hozzájárulás a névvel való megjelenéshez;
- ki kódolta (ember vagy AI, prompt-verzióval), felülvizsgálat, státusz.

**Külső forrás szintje:**
- A: szabvány vagy akadémiai publikáció;
- B: szisztematikus áttekintés;
- C: intézményi keretrendszer;
- D: tanácsadói anyag vagy esettanulmány;
- E: mégis pilot.

Mellette erő: erős / közepes / gyenge. Minden külső hivatkozást megnyitnak és ellenőriznek: igazolt / részben / nem támasztja alá / nem elérhető.

### 6.2 Állapot

| Állapot | Mikor |
|---|---|
| Jelen lehet | Legalább egy jelenlét-jel nyom, és ugyanarra az időszakra nincs erősebb hiány-jel vagy anti-minta |
| Hiányzik | Hiány-jel vagy anti-minta nyom, jelenlét-jel nélkül. Anti-mintánál a képesség csak látszik |
| Nincs nyoma | Volt forrás, amelyben előjöhetett volna, de nem jött elő („nálatok ez most nem kérdés”) |
| Nincs adat | Nem volt ilyen forrás. Gondolatjel, és egy sor arról, mi hiányzik |

**Ha jelenlét- és hiány-jel is van:**
- Előbb megnézik, nem két alany keveredik-e. Ha igen, az egyenetlenség, például a core team-nél jelen van, a menedzsmentnél hiányzik.
- Ha nem, az erősebb nyomok döntenek, a konfidencia legfeljebb közepes.

**Ami nem állapot:**
- a „Kevés még az adat” (az alacsony konfidencia felirata);
- a „beépült” (az érettség);
- a „blokkolt” (akadály mező);
- a „vitatott” (konfidencia).

**Nem kompenzál (AL-08):** magas RC-01, RC-03 vagy RC-04 nem ellensúlyozza az RC-05 vagy az RC-06 hiányát.

Az állapot mindig alanyhoz és időszakhoz kötött: képesség · alany · időszak · állapot · konfidencia · nyomok · akadály.

### 6.3 Konfidencia

| Szint | Feltétel |
|---|---|
| Alacsony („Kevés még az adat”) | Egy nyom, vagy csak feldolgozott forrás, csak önkép vagy csak önprezentáció |
| Közepes | Legalább két független nyom azonos irányban, ellentmondás nélkül |
| Magas | Legalább három nyom, legalább két módszerből és két alkalomból, köztük legalább egy nem önbevallás, hat hónapnál frissebb. Magasat csak ember ad |
| Vitatott | Ellentmondó nyomok, legfeljebb közepes |

- **Független nyom:** más ember, más módszer vagy más alkalom. Ugyanaz az ember ugyanazon az alkalmon egy forrásnak számít.
- **Csábító tétel:** a közepeshez legalább két nyers nyom kell az adott szinten.
- **Méret és szint szerinti kiigazítás:**
  - egyénről csak saját nyomból legfeljebb alacsony;
  - mikro szervezetnél a függetlenséget a módszer és az alkalom adja, és a kimenet jelzi: „egy ember nézőpontja”;
  - kis csapatnál (4–9 fő) legalább két tag nyoma kell;
  - szervezeti állítás nem lehet magasabb, mint a lefedettség (egy egység vagy két fő: legfeljebb alacsony);
  - ökoszisztéma-szinten csak belső nyomból legfeljebb alacsony, a közepeshez külső hang kell.
- **Anonimitási küszöb:** összesített számot és bontást csak legalább 4 fő válaszából mutatnak. Munkaváltozat, Oszi még felülvizsgálja.

### 6.4 Érettség (6 lépcső)

| # | Név | Leírás | Lépés ide |
|---|---|---|---|
| 1 | Csak szavak | Kimondják értéknek, nyoma nincs | – |
| 2 | Látjuk a hiányt | Felismerték a hiányt, tett még nincs | A hiányt kimondják, és elvárják a változást |
| 3 | Ha szólnak, megy | Megtörténik, de kérni vagy emlékeztetni kell | Az első alkalom, amikor tényleg megtörtént |
| 4 | Magától megy | Nem kell kérni | Kérés és emlékeztetés nélkül is megtörténik |
| 5 | Nyomás alatt is megy | Határidő, konfliktus, távollét alatt sem esik ki | Nyomás alatt és a gazda távollétében is ismétlődik |
| 6 | Továbbadjuk | Új ember is így csinálja, és el tudják mondani, miért | Új belépő is így tanulja meg |

- **Korall vonal** a 4. és az 5. lépcső között: addig egy emberen múlik, onnantól a működésen.
- **Plafonok:**
  - csak magabiztosság: legfeljebb 2;
  - dokumentáció ismételt gyakorlat nélkül: legfeljebb 3;
  - normál körülmények között működik: legfeljebb 4;
  - az 5-höz a gazda távollétének próbája kell;
  - a 6-hoz igazolt üzleti és ökoszisztéma-haszon.
- **Lépés:** csak nyomokkal lehet lépni, a lépés dátummal megmarad, és visszalépés is lehetséges. A coach javasol, a mégis jóváhagy.
- **Mikortól van:** az első diagnózisban nincs érettség, a másodiktól van. A „nincs adat” állapot, nem szint.
- **Skálanevek:** a lépcsőnevek a prototípus J5.E leírásából jönnek. A J9 szavai (nincs és mégis van, csak szavak, ad-hoc, rendszeres néha, többnyire ott van, beépült) zárójeles referenciák.

### 6.5 Állapot, érettség, lépcső

- **Állapot:** durva és olcsó, egy időpontra szól.
- **Érettség:** finom és drága, ismétlést, nyomást, a gazda távollétét kell hozzá látni.
- **Összefüggés:** a „hiányzik” nagyjából az 1–2. lépcső, a „jelen lehet” a 3. lépcsőtől felfelé.
- **Lépcső:** egy diagnózis-válasz értéke egy mérőponton, nem a képesség érettsége.

### 6.6 Minta és találat (döntés 10.05.)

**A minta három típusa:**
- **Képesség-minta**, négy altípussal:
  - jelenlét-jel;
  - hiány-jel;
  - anti-minta: látszólag észszerű gyakorlat, amely rontja a képességet, elrejti a problémát vagy máshová tolja a költséget;
  - határeset: összetéveszthető vele, de mást jelent.
- **Előfeltétel-minta:** szerkezeti helyzet, amely eldönti, indulhat-e fejlesztés.
- **Detektor-hibaminta:** amibe maga a felismerés szokott belecsúszni, például feldolgozott következtetés átvétele, csonkolt idézet, szándék mint jelenlét. Ügyfél soha nem látja, a detektorban tiltó szabályként fut.

**Két külön érték:**
- **Minta-érettség (a tárban, számított):** hipotézis (irodalom) / hipotézis (saját megfigyelés) / egy esetben láttuk / használt (legalább két ügyfélnél jóváhagyott találat) / elvetett, elavult (ez kézzel állítható). Csak a megfogalmazást módosítja, például „a szakirodalom szerint gyakori; nálatok ezt láttuk”.
- **Találat-konfidencia (az ügyfélnél):** ez adja a kimondás erejét. A gép legfeljebb közepeset adhat.

**Szabályok:**
- **NY-26:** hipotézis érettségű minta első találatát ember hagyja jóvá. Detektor-javasolta új minta csak emberi felvétellel kerül a tárba.
- **NY-27 (javaslat):** az elvetett javaslat téves riasztás. Ha egy mintánál legalább négy javaslatból legalább a felét elvetik, a mintát felülvizsgálják.

**Mintatár-mezők:**
- azonosítás: típus, kapcsolt képesség (pontos / részleges), szint, ökoszisztéma-irány (befelé / kifelé), kívülről látható, termékek;
- helyzet, megfigyelhető viselkedés;
- mechanizmus: „Ha [helyzet], akkor [viselkedés], ezért [hatás], feltéve, hogy [feltétel]”;
- látszat, következmény, mi nem ez, alternatív magyarázat, ellenpróba;
- minimális nyom;
- hogyan mondjuk ki, következő kérdés, ellenszer;
- eredet, külső forrás, ICP-átvihetőség, hivatkozás-ellenőrzés.

Altípusonként külön kötelezőségi tábla adja meg, mely mező kötelező.

### 6.7 Előfeltételek (E1–E9)

| Kód | Előfeltétel |
|---|---|
| E1 | A fájdalom és a döntési jog egy helyen van: aki érzi a problémát, dönthet-e a megoldásról |
| E2 | Gazda: név szerinti, írásban rögzített, a 90. nap után is viszi |
| E3 | Védett idő a naptárban (a Charta felső határa heti 4–6 óra) |
| E4 | Kör, nem névsor: a vivő kör működő csapat-e |
| E5 | Vevő és résztvevő ugyanaz-e |
| E6 | Mozgástér: mi van a helyi jogkörön kívül (anyacég, bér) |
| E7 | Szponzor: meddig ér a keze |
| E8 | Belépő mérete: kicsi, árazott belépő |
| E9 | Függetlenség: érdekelt-e a mérést végző fél |

- **Érték:** megvan / részben / nincs / nem tudjuk, nyommal. Nem pontozzák.
- **Blokkolt képesség:** nem külön állapot, hanem akadály mező a képesség–alany párnál. A kimenetben a képesség mellett jelenik meg, hogy látsszon: nem a csapaton múlik.
- **Döntési jog hiánya:**
  - ha a ciklusról, a programról vagy a büdzséről szól: előfeltétel (E1, E6, E7), és a megoldás mandátumépítés;
  - ha visszatérő működési döntésekről: ÚJ-01, Bridge-ciklussal.

### 6.8 Kimenet és prioritás

- **Állítás típusa:**
  - erősség;
  - repedés: csak ott, ahol a rés nagy;
  - sejtés: kimondják, de nem építenek rá.
  - Egyenetlenség: erősség az egyik alanynál, repedés a másiknál, egy állításban.
- **Az érték öt helyen keletkezik:** a kimondatlan kimondása, az ok átnevezése, hol van már meg a képesség, a mondás és a tett különbsége, mit érdemes megvenni. Amit mindenki tud, azt egy mondatban mondják ki.
- **Értékteszt** minden diagnózis után: „Mi volt ebben új?” és „Mit döntöttetek másképp miatta?”
- **Prioritás:**
  - öt szempont (rés, relevancia, bizonyíték, kockázat, függés), súlyozás nincs: az ember dönt, szempontonként egymondatos indoklással;
  - egy ciklus, egy képesség;
  - ha E1–E3 bármelyike „nincs”, a javaslat az előfeltétel rendezése, nem Bridge.

## 7. A képesség rekordja (Képességtár)

Egy rekordot nem töltenek ki egyszerre. Üres cella nem marad, háromféle üres érték van: „nincs még”, „nem értelmezhető”, „nincs elég külső bizonyíték”.

| Blokk | Mezők |
|---|---|
| A. Azonosítás | Azonosító, név (hu, en), elsődleges szint, csoport, mégis-terület (a Charta 5 területe), Purpose Tree fa-elem, jelleg, státusz, verzió, felülvizsgálat, jóváhagyó |
| B. Definíció és határ | Rövid definíció, kimondható forma („Mostantól tudunk X-et, Y nélkül”), mi nem ez (hamis barát is), átfedés, összetevők (2–5) |
| C. Megjelenés | 4 szint × folyamat- és emberoldal |
| D. Felismerés | Mintatár-hivatkozások, jellemző források, alternatív magyarázat, szükséges előfeltételek (és képesség-függőség, például ÚJ-01), falszifikálás, csábító tétel |
| E. Érettség | A 6 lépcső képesség-specifikusan, lépés-feltételek |
| F. Fejlesztés | Jellemző döntés, mit kell építeni a két oldalon, eredménytermékek, elfogadási feltételek, viselkedéses mutatók, nyomáspróba (helyzet → elvárt működés → bukási jel → helyreállás), szerkezeti akadályok, öt próba kockázata, átadás jelei |
| G. Források | Eset- és külső források, mérőeszköz (validált kérdőív, ha van), esetek, ellenpéldák, nyitott kérdések |
| H. Kereskedelem | Ügyfélfájdalom az ügyfél szavaival, hogyan hangzik ajánlatban |

## 8. Fejlesztés: kapcsolat a Bridge-dzsel

- **Mikor kész egy képesség.** Mind az öt feltétel kell:
  1. kimondható forma;
  2. megnevezett gazda;
  3. ritmus a naptárban;
  4. előre rögzített elfogadási feltétel;
  5. az öt próba.
- **Az öt próba:** önazonos, felelős, közösségi, regeneratív, élő.
  - Mind az ötöt megválaszolják. Az elfogadás feltétele csak az Élő próba.
  - A többinél nyitó és záró értéket adnak (1–6), és a fejlődést mérik.
- **A 90 nap kapui:**
  1. Charta, nyitó mérés (1–10. nap);
  2. élő esetek átnézése (11–30. nap);
  3. nyomáspróba (félidő);
  4. átadás (90. nap);
  5. regeneráció-ellenőrzés (90–180. nap).
- **Hét kizáró feltétel** a „sikeres átadásra”: nincs gazda, nem használják élesben, nincs záró mérés stb.
- **Mit ad vissza a Bridge a tárba:**
  - nyomáspróba-bejegyzések, nyomként;
  - teljesült elfogadás, jelenlét-jelként;
  - „megmaradt akadályok”, előfeltétel-minta-jelöltként;
  - érettségi lépés javaslata.

## 9. Személyvédelem

- **Tiltások:** egyéni adat nem kerül teljesítményértékelésbe, nem használják büntetésre vagy rejtett megfigyelésre. „Ha egy ügyfél ezek bármelyikét kéri, az nem tárgyalási pont.”
- **Idézetek:** név nélkül jelennek meg, és szerep nélkül is, ha a szerep azonosít. Névvel csak kifejezett, visszavonható hozzájárulással.
- **Az állítás alanya** alapértelmezésben a folyamat, a csapat vagy a szervezet. Személyről szóló állítást csak az érintett és a mégis lát.
- **Negatív állítás személyről:** legalább két független forrás kell, köztük legalább egy közvetlen. Viselkedésként fogalmazzák meg egy helyzetben, nem tulajdonságként.
- **Soha:**
  - pszichológiai, egészségi vagy személyiség-címke, IQ;
  - személy és képességkód összerendelése megosztott anyagban;
  - állítás távollévő harmadik személyről.
- **Kis létszám:** gyűjtés előtt megmondják, hogy az idézetek visszaazonosíthatók lehetnek.
- **Megőrzés:** 3 év, hacsak másképp nem állapodnak meg. A szerződésbe vagy a Chartába kell írni.
- **Nyitott:** a Diagnózis és az Értékmérés alkalmazáshoz nincs adatkezelési tájékoztató, ezt jogásszal kell rendezni.
- **Tiltólista:** forrás, amelyből nem idéznek és nem gyűjtenek. Minden gyűjtés és futás előtt ellenőrzik.

## 10. Tárak v0.4 (adatmodell)

Egy Google Sheet, ez a mester. A JSON-fájlok ebből készülnek, nem fordítva.

| Fül | Kérdés | Sorok (10.13.) |
|---|---|---|
| Képesség-lap | Nézet: egy képesség mindene, képletekkel | – |
| Képesség | Mi ez a képesség? (törzsmezők + számított darabszámok) | 75 |
| Képesség-részletek | Ritkább mezők, hosszú formában (képesség · mező · érték) | kb. 1550 |
| Minta | Hogyan néz ki? Számított minta-érettség | 198 |
| Találat | Hol láttuk? (ügyfél × minta) | 184 (164 megerősített, 20 javasolt) |
| Bizonyíték | Mit hallottunk ténylegesen? Egyszer tárolva | 327 |
| Kapcsolat | Melyik bizonyíték melyik mintát támasztja alá (valós példa / ellenpélda / határeset) | 392 |
| Esettár | Honnan van? (forrásonként) | 64 (DXC 47, SZV 11, Kheiron 4, módszertan 2) |
| Szabálykönyv | Szabályok azonosítóval és státusszal | 75 |
| Tesztpad | Futások, feltételek, eredmények | 1 (a 72-es lista próbája) |
| Változásnapló | Mi változott, miért, ki javasolta, ki hagyta jóvá | 82 |

**A lánc:** Képesség ← Minta ← Kapcsolat → Bizonyíték → Esettár, valamint Minta ← Találat (ügyfelenként).

**A minták:**
- típus szerint: 172 képesség-minta (62 jelenlét, 45 hiány, 35 határeset, 30 anti-minta), 18 detektor-hibaminta, 8 előfeltétel-minta;
- érettség szerint: 142 „egy esetben láttuk”, 26 hipotézis (saját), 24 hipotézis (irodalom), 6 használt;
- csak 3 minta „kívülről látható”.

**Azonosítók:**
- Az azonosítót az eszköz adja (M-xxx, B-xxxx, K-xxxx, T-xxxx), kézzel nem írnak és nem használnak újra.
- Törlés helyett „elavult” vagy „elvetett” jelölés.
- Párhuzamos munkánál ideiglenes azonosító készül (például F33-M01), a végleges számot a betöltő adja.
- A szabályok előtagja: NY (nyom), AL (állapot), KO (konfidencia), ER (érettség), PR (prioritás), VE (védelem), EF (előfeltétel), FE (fejlesztés).

**Adatbázis-megfelelés** (a megrendelő javaslata):
- táblák: `capability`, `capability_detail`, `pattern`, `finding`, `evidence`, `evidence_pattern`, `case_source`, `rule`, `test_run`, `change_log`;
- további táblák: `precondition`, `capability_subject_blocker`, `capability_component`, `measurement_instrument`, `case_context`, `source_blocklist`;
- a szint felsorolt típus (4 érték) + két jelölés;
- minden mezőnek állapota is van: kitöltve / nincs még / nem értelmezhető / nincs elég külső bizonyíték;
- a nyom és a találat ügyféladat; a képesség- és mintatudás megmarad, ha az ügyfél adatait törölni kell.

**Betöltési menet:**
1. Sheet letöltése.
2. Mentés.
3. `tarak_v04.py load`.
4. `check_v04.py` („HIBA NINCS” nélkül nincs továbblépés).
5. Import „Táblázat cseréje” móddal.
6. Védelem újra (`protect`).
7. Visszaellenőrzés.

## 11. Hogyan épül, és hol tart

**Folyamat:**
1. **Gyűjtés:** Claude közvetlenül a Drive-ból és a Wispr Flow-ból dolgozik, szó szerinti idézetekkel, név nélkül. A Gemini-kigyűjtést elvetették, mert hibás volt.
2. **Kutatás:** Perplexity, csoportonként, szigorú forrásszabályokkal.
3. **Összefésülés:** Claude, csoportonként, Oszi jóváhagyásával. Képességenként legfeljebb 8 új minta.
4. **Betöltés:** eszközzel.
5. **Tesztpad:** a rendszer stressztesztje.
6. **Átadás:** a prototípus JSON-ja és a detektor skill.

| Lépés | Mi | Állapot (10.13.) |
|---|---|---|
| F00 | A Sheet váza | Kész (09.26.) |
| F01–F03 | Forrásgyűjtés: DXC (50 forrás, 573 idézet), SZV (96 idézet), Kheiron | Kész (09.27.) |
| F04–F05 | Csoportosítás: a 12 csoport marad munkacsoportosításként | Kész |
| F06–F13 | 0. hullám (RC-24, RC-10, ÚJ-01) és kalibráció | Kész, a tárakba 10.13-án került (E-0). Az F12 (ÚJ-01) munkafüzete elveszett, 18 sora hiányzik |
| F14–F25 | 1. hullám: 6 csoport (kutatás + összefésülés) | Kész, jóváhagyva, a tárakban |
| – | Átállás a v0.4-re | Kész (10.06.) |
| F32–F42 (páros) | 2. hullám kutatás, 6 csoport | Kész (10.05.) |
| F33 | Regenerative Value & Ecology összefésülése | Elkészült (10.08.), jóváhagyásra vár |
| F35–F43 (páratlan) | A többi 5 csoport összefésülése és betöltése | Vár, szigorúan egymás után |
| T-0 | A Tesztpad-találatok betöltője (új eszköz-mód) | Vár |
| F26–F31 | Tesztpad: DXC, Kheiron, SZV vak teszt, kevés adat, személyvédelem, reprodukálhatóság | Vár (előfutások 10.10–11.) |
| F44 | Átadás: `kepessegtar-75.json` a prototípusnak, a detektor skill frissítése | Vár |

**Visszatérő problémák és a rájuk hozott szabályok:**
- csonkolt idézet és rossz beszélő-címke: egyeztetés az eredetivel, tartalmi ellenőrzés;
- gyenge Perplexity-hivatkozások: mindhárom 0. hullámos kör adataiból 62 ellenőrzött hivatkozásból 8 nem támasztotta alá az állítást, ezért „HIVATKOZÁS NEM TÁMASZTJA ALÁ” jelölés került be;
- túl sok sor: sorkorlát;
- azonosító-ütközés: ideiglenes kódok;
- túl könnyű „használt” státusz: szigorú olvasat.

## 12. Tesztpad: amit az első próbák mutattak

**A 72-es lista próbája (09.26., 0. futás).** Három eset, esetenként egy AI-olvasó. Az ítélet: részben.
- Szótárként jó.
- Az előfeltételekre vak, pedig mindhárom esetben azok döntöttek.
- A tételek között nem dönt: a DXC-megállapítások fele kétértelmű volt, mert a sablonmezők minden sorban azonosak.
- A szintek félrevisznek (nincs határfelület, a mikro szervezetnél összeesnek).
- Az emberoldali beavatkozások felé terel, és ez a mégis visszatérő hibája.
- Ebből lett az előfeltétel-réteg, a „mi nem ez” és az „átfedés” mező, a csábító tétel, a négy szint és az ÚJ-01–03.

**F26-előfutás (10.10., DXC).**
- Az első éles próba a 75 tétellel, csak nyers forrásból. Nem vak futás.
- Kimenet: E1–E9, P2P 3+3 (fejlesztendő: ÚJ-01, ÚJ-03, RC-53; erősség: RC-46, RC-67, RC-43), Értékmérés-mutatók, Bridge-blokk.
- Tanulság: a „csak irodalom” tételeknél a felismerés értelmezés, nem mintára illesztés. Az RC-15 és az RC-39 túl általános.

**TP-DXC-P2P (10.11.): egyéni és core team diagnózis vakon, öt alany interjúiból.**
- **Két forrásréteg:**
  - 1. réteg: az első interjú, önbevallás;
  - 2. réteg: a visszatükrözés.
- **A 2. réteg szétválogatása:**
  - M: a mégis beszél, soha nem nyom;
  - T: egyetértés, nem bizonyít;
  - V: viselkedés a tükörrel szemben;
  - S: spontán új eset.
- **Ha a két réteg egybevág,** az két módszer és két alkalom.
- **Core team küszöbök:**
  - közepes: legalább 2 tag nyers nyoma;
  - magas: legalább 3 tag, köztük megfigyelt viselkedés;
  - ha több alany ugyanarra a felolvasott szövegre ugyanúgy reagál, az „mégis-hatás”, nem független nyom.
- **Eredmény:**
  - mind a 10 átmeneti feltétel teljesült;
  - mind a 928 idézet szó szerint megvan;
  - magas konfidencia sehol;
  - E1 és E3 „nincs”, ezért a javaslat mandátumépítés, nem Bridge.
- **Javítandó:**
  - a promptok ellentmondanak a magas konfidenciáról;
  - a V/S határ bizonytalan;
  - a tükör-helyzet torzít (az RC-10 és az RC-49 mindenkinél „jelen lehet”);
  - csapatszintű megfigyeléshez közös alkalom leirata kell;
  - a pontozási skála szintjét előre rögzíteni kell.

A Tesztpad-futások eredménye mindig „javasolt” találat, a tárakba még semmi nem került belőlük.

## 13. Capability Tükör (side project, v0.1)

**Mi ez:** egyoldalas olvasat egy cég nyilvános megjelenéseiből (videó, podcast, poszt, weboldal). Ingyenes sales-belépő, ügyfél-beavatkozás nélkül.
- Legfeljebb 3 jel (erősség / repedés / sejtés), időbélyeges idézettel és alternatív magyarázattal.
- Egy mondás-tett kérdés, „amit kívülről nem látunk”, és 3 kérdés az első beszélgetéshez.
- Pontszám, állapot és név nincs benne, a konfidencia alapból alacsony.

**Út:** Tükör → beszélgetés → Snapshot → Teljes → Bridge.

**Pilot:** 4 hét, 5 cég. Továbblépés, ha:
- 5 olvasatból legalább 3-nál van új jel;
- legalább 2 beszélgetés-foglalás;
- olvasatonként 90 percnél kevesebb munka;
- nulla hibás idézet.

**Beépítés:** egy „képesség” mód az Értéktükör Külső tükör motorjában. A fejlesztési időt a fejlesztő becsüli. Nyitott: név (Képességtükör?), ár, jogalap.

## 14. Esetek és bemenetek

| Eset | Mi ez | Adat |
|---|---|---|
| DXC | Nagyvállalati IT-szolgáltató magyar delivery csapata (kb. 47 fő). P2P diagnózis, értéktérkép-workshopok, 90 napos ciklusok, CoE-k | 50 forrás, 573 idézet (Claude-gyűjtés). Az Esettár nagy része innen |
| Kheiron | Pszichológiai, terápiás közösség, pro bono keretben | Egyetlen nyers interjú. A többi feldolgozott anyag |
| SZV (Szuperszónikus) | Kétalapítós kis cég (admin- és pénzügy-automatizálás), a mégis beszállítója is | 96 idézet (memók, ügyfélinterjúk). A diagnózis-ülések leirata hiányzik |

**Egyéb bemenetek:**
- Perplexity-kutatás mind a 12 csoportra, külön mélyfúrás az RC-10, RC-24 és ÚJ-01 képességre. Az AI, Data & Technology fájl csonka: az RC-58–60 hiányzik belőle.
- 3 cikk az elégségességről és a regeneratív üzleti modellekről: Nikou és mtsai 2025; Gossen és mtsai 2026; Kangas 2025, mesterszakdolgozat.

## 15. Előzmények: honnan jött, mit vetettek el

- **Korai angol anyagok** (09.22–26.): négy párhuzamos készlet. A 48-as, a négyrétegű D-72, az RC-72 „v2” (a mostani alap) és egy 23-as szintézis. Mindegyiknek más skálája volt (0–5, 1–5, Foundation/Practitioner/Steward).
- **A mai rendszer elvetette:**
  - a 48 + 24-es szerkezetet és a CAP-azonosítókat;
  - a 0–5 skálát;
  - a súlyozott prioritás-képletet;
  - a „72 mint teljes készlet” szemléletet;
  - a domain-pontozást;
  - a külön készség-réteget;
  - a Gemini mint forrásgyűjtő szerepét;
  - a nagyvállalati bizonyíték-feltételeket kötelezőként. Helyettük ICP-átvihetőség mező került be.
- **Megmaradt:**
  - az ember- és a folyamatoldal;
  - az öt próba;
  - a 90 napos ciklus;
  - egy ciklus, egy képesség;
  - a gazda-távolléti próba;
  - az RC-azonosítók és a 12 csoport;
  - az ökoszisztéma-szint.
- **Fordulópont:** a 72-es próba (09.26.), Oszi döntései aznap, majd a v0.4 (10.05–06.). Utóbbiban a minta és a találat szétvált.

## 16. Nyitott kérdések a megrendelőnél

- A „csak irodalom” plafon (legfeljebb közepes) maradjon-e a minta-érettség bevezetése után. Javaslat: helyette az első találat emberi jóváhagyása.
- A téves riasztás küszöbe (NY-27): a Tesztpadon kalibrálják.
- Az ellentétes viselkedés 1 („nincs”) legyen-e, vagy gondolatjel.
- Az anonimitási küszöb (4 fő) végleges-e.
- Megőrzés: 3 év, álnevesítéssel. Maradjon-e a naptár-metaadat 90 napja és az Értéktükör 30 napja.
- A 11 másodlagos csoport-jelölés, és a fa-elem lista forrása.
- A Perplexity-hivatkozások mindegyikét ellenőrizzék-e, vagy csak a tartóoszlopokat.
- Az M-008 minta pótlása vagy elavulttá tétele.
- Az ÚJ-01 hiányzó F12-sorai.
- Romlás az öt próbán (javaslat).
- A Tesztpadhoz második emberi értékelő kell.

## 17. Mit jelent ez a fejlesztésnek

Javaslatok és egyeztetendő pontok, nem döntések. 2026.10.13-án ezek alapján frissült a 07, a 18, az NM-12 és az NM-15, és új jegy lett az NM-19 (Mintatár). Az alábbi pontok a frissítés előtti állapotot írják le.

**1. A 07-es jegy (Képességtár) modellje elmarad a megrendelőétől.**
- A jegy az Értéktár mezőit másolja: Mit jelent / Mit nem jelent / 6 · Mikor teljesül nagyon / 1 · Mikor nagyon nem teljesül / Példák / Szinonimák.
- A megrendelő rekordja más:
  - állandó azonosító, magyar és angol név, elsődleges szint, csoport, státusz, jelleg;
  - rövid definíció, kimondható forma, mi nem ez, átfedés;
  - megjelenés négy szinten;
  - előfeltételek;
  - a felismerés külön entitásban (Minta) van, nem a képesség soraiban (7. pont).
- **Javaslat:** MVP-ben legalább a Képesség-fül törzsmezőit vegyük át. A Minta, a Bizonyíték és a Találat az NM-12-höz tartozik.

**2. Kezdő adat.**
- A 07 a prototípus `kepessegtar-72.json`-jára épít. Ez egy 2026.09.22-i munkaváltozat, angol leírásokkal és minden sorban azonos sablonmezőkkel, és a megrendelő épp ezt találta gyengének.
- A mester ma a Sheet (75 tétel), és a megrendelő F44 lépése gyártja a `kepessegtar-75.json`-t.
- **Kérdés a megrendelőnek:** mikor lesz F44-export, vagy töltsünk-e közvetlenül a Sheetből.

**3. A 07-es jegy nyitott kérdésére (kód és szint) az anyag válaszol:** igen.
- Az RC- és ÚJ-azonosító állandó, a név változhat.
- A szint négyértékű (egyén / csapat / szervezet / ökoszisztéma) + két jelölés (határfelület, mikro szervezet). A képességnek elsődleges szintje van.

**4. Kategóriák (07 · 7.4).**
- A 12 csoport a megrendelőnél munkacsoportosítás, „csak bemutatásra, nem pontozzuk”.
- Az RC-37 átkerült a Futures & Adaptability csoportba, az ÚJ-01–03 is csoportban van.
- A megrendelő angol csoportneveket használ. A jegy magyar nevei a régi JSON `domain_hu` mezőjéből jönnek: egyeztetni.

**5. Verziózás és státusz.**
- A 07 verziózása és archiválása összefér a megrendelő gyakorlatával: Változásnapló indokkal, törlés helyett elavult vagy elvetett.
- A státusz (használt / jelölt / csak irodalom / félretett) viszont hiányzik a jegyből.

**6. NM-12 (képességmérés) eltérései.**
- **Lépcső:** az NM-12 már az első javaslatban lépcsőt ad. A megrendelő szerint az első diagnózisban csak állapot és konfidencia van, érettség csak a második diagnózistól.
- **Állapot:** az NM-12-ben 2 érték van (jelen lehet / hiányzik), a megrendelőnél 4 (+ nincs nyoma, nincs adat).
- **Nyomkeresés:** az NM-12 a 72-es mátrix folyamat- és emberoldali (sablon) szövegét használná. A megrendelőnél a Mintatár alapján megy (jelenlét-jel, hiány-jel, anti-minta, határeset), a detektor-hibaminták tiltó szabályként futnak.
- **Konfidencia és előfeltételek:** konfidencia a KO-01–08 szabályok szerint, az előfeltételek (E1–E9) külön, pontozás nélkül.

**7. NM-15 (képesség a riportban, Bridge).**
- A megrendelőnél a Teljes riport 3 fejlesztendő + 3 erősség, érettség nélkül. Az NM-15-ben „6 jelölt, ebből 3 javaslat”: egyeztetni.
- Ha E1–E3 bármelyike „nincs”, a javaslat mandátumépítés, nem Bridge. Ez az „Indítható?” logikája.

**8. Ütközés már MVP-s jegyekkel.** A megrendelő 09.26-i döntései, amelyek az Átírási lista szerint a prototípusba és az Értékmérés-specbe még nem kerültek át.
- **0 helyett gondolatjel:**
  - a döntés: „A 0 nem szám: ahol nincs nyom, gondolatjel áll”, a mérőponton is;
  - a 04 (PHI 0–6, „0 nincs nyoma”), a 13, a 18 és a 27-es jegy 0-t használ;
  - kérdés: az értékek mérésére már MVP0-ban is érvényes-e.
- **Anonimitási küszöb:**
  - a 18, a 29 és a 31-es jegy 3 kitöltővel számol (a prototípus szerint), a megrendelő munkaváltozata 4 fő;
  - kapcsolódik a 18-as jegy 4. és a 29-es jegy 1. nyitott kérdéséhez.
- **Megőrzés:** 3 év, a prototípus J1 lapja szerint „csomag + 12 hónap”. A 31-es (adatkezelés) jegyhez és a jogi dokumentumokhoz (05) fontos.

**9. Személyvédelem a termékben.** A 9. pont szabályai a riportot, a coachot és a jogosultságokat is érintik:
- idézet név és azonosító szerep nélkül;
- személy és képességkód nincs összerendelve megosztott nézetben;
- hozzájárulás-mező nyomonként.

**10. A kapott anyagok kezelése.**
- A mappában több fájl személyes adatot tartalmaz: a Gemini-kigyűjtésekben és néhány gyűjtésben maradt név, és egy DXC-forrásban egészségi és IQ-adat is van.
- Ezeket mégis-hozzáférésű anyagként kezeljük: ne kerüljenek repóba vagy további megosztásba.

## A melléklet: a 75 képesség (tárak v0.4, 10.13.)

Szint = elsődleges szint. Minták = a Minta fülön kapcsolt minták száma.

### Inner Grounding & Wellbeing

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-01 | Önismeret és mintázat-felismerés | Self-awareness and pattern recognition | egyén | jelölt | készség-jellegű | 2 |
| RC-02 | Integritás és belső iránytű | Integrity and inner compass | egyén | jelölt | csábító | 3 |
| RC-03 | Jelenlét és figyelem-gazdálkodás | Presence and attention stewardship | csapat | jelölt |  | 6 |
| RC-04 | Érzelmi ön- és együtt-szabályozás | Emotional regulation and co-regulation | csapat | jelölt | kérdéses | 5 |
| RC-05 | Pszichoszociális kockázat-megelőzés | Psychosocial risk prevention | szervezet | jelölt | csábító | 8 |
| RC-06 | Fenntartható tempó és regeneráció | Sustainable pace and recovery architecture | szervezet | használt | csábító | 14 |

### Ethical Agency & Power

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-07 | Etikus ítélet és erkölcsi bátorság | Ethical judgment and moral courage | egyén | csak irodalom |  | 0 |
| RC-08 | Felelősségvállalás és jóvátétel | Accountability and repair | egyén | csak irodalom |  | 1 |
| RC-09 | Hatalmi viszonyok láthatóvá tétele | Power literacy and privilege awareness | csapat | csak irodalom |  | 0 |
| RC-10 | Építő ellenvélemény és kiállás | Constructive dissent and speaking up | csapat | jelölt |  | 14 |
| RC-11 | Emberi jogok és igazságos átmenet | Human-rights and just-transition stewardship | szervezet | csak irodalom |  | 0 |
| RC-12 | Méltányosság, befogadás, hozzáférhetőség | Fairness, inclusion and accessibility by design | szervezet | csak irodalom |  | 1 |

### Systems & Complexity

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-13 | Rendszerszemlélet | Systems thinking | egyén | csak irodalom |  | 0 |
| RC-14 | Komplexitás és paradoxonok kezelése | Complexity and paradox navigation | egyén | csak irodalom |  | 0 |
| RC-15 | Közös értelmezés | Collective sensemaking | csapat | csak irodalom |  | 1 |
| RC-16 | Feszültségek és kompromisszumok feloldása | Trade-off and tension reconciliation | csapat | csak irodalom |  | 0 |
| RC-17 | Ökoszisztéma-térkép és határátlépés | Ecosystem mapping and boundary spanning | szervezet | csak irodalom |  | 0 |
| RC-18 | Beavatkozás a fordulópontokon | Leverage-point intervention design | szervezet | csak irodalom |  | 0 |

### Futures & Adaptability

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-19 | Jövő-írástudás | Futures literacy | egyén | csak irodalom |  | 0 |
| RC-20 | Látható készségek, önirányított fejlődés | Skills visibility and self-directed development | egyén | csak irodalom |  | 1 |
| RC-21 | Forgatókönyvek és visszatervezés | Scenario thinking and backcasting | csapat | csak irodalom |  | 0 |
| RC-22 | Adaptív tervezés és opciók | Adaptive planning and optionality | csapat | csak irodalom |  | 0 |
| RC-23 | Stratégiai előrelátás és jelzés-érzékelés | Strategic foresight and signal sensing | szervezet | csak irodalom |  | 0 |
| RC-24 | Változás tartós túlterhelés nélkül | Transition capacity without chronic overload | szervezet | jelölt | csábító | 20 |
| RC-37 | Szerep-rugalmasság és pálya-alkalmazkodás | Role fluidity and career adaptability | egyén | jelölt |  | 4 |

### Relational Intelligence

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-25 | Empátia és nézőpontváltás | Empathy and perspective taking | egyén | csak irodalom |  | 0 |
| RC-26 | Alázat, kíváncsiság és nem-tudás | Humility, curiosity and not-knowing | egyén | csak irodalom |  | 0 |
| RC-27 | Mély figyelem és generatív párbeszéd | Deep listening and generative dialogue | csapat | csak irodalom |  | 0 |
| RC-28 | Bizalomépítés és kapcsolati szerződés | Trust building and relational contracting | csapat | csak irodalom |  | 6 |
| RC-29 | Érintetti kapcsolatok gondozása | Stakeholder relationship stewardship | szervezet | csak irodalom |  | 2 |
| RC-30 | Generációk és kultúrák közti eligazodás | Intergenerational and intercultural fluency | szervezet | csak irodalom |  | 0 |

### Teaming & Participation

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-31 | Készség-alapú csapatösszeállítás | Skills-first team composition | csapat | jelölt |  | 11 |
| RC-32 | Facilitálás és részvétel | Facilitation and participation craft | egyén | jelölt | készség-jellegű | 7 |
| RC-33 | Pszichológiai biztonság és őszinteség | Psychological safety and candor | csapat | jelölt | csábító | 9 |
| RC-34 | Feszültség és érdek közvetlen tisztázása | Conflict transformation and negotiation | csapat | jelölt |  | 4 |
| RC-35 | Érdemi részvétel és munkavállalói hang | Meaningful participation and employee voice | szervezet | jelölt |  | 7 |
| RC-36 | Hálózatos, határokon átívelő együttműködés | Networked and cross-boundary collaboration | szervezet | jelölt |  | 13 |

### Purpose & Strategy

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-38 | Stratégiai és üzleti érzék | Strategic and commercial acumen | egyén | jelölt | kérdéses | 3 |
| RC-39 | Közös cél és stratégiai összhang | Shared purpose and strategic alignment | csapat | jelölt | csábító | 2 |
| RC-40 | Ügyfél- és kedvezményezett-ismeret | Customer and beneficiary insight | csapat | jelölt |  | 8 |
| RC-41 | Regeneratív stratégia és portfólió | Regenerative strategy and portfolio coherence | szervezet | jelölt |  | 5 |
| RC-42 | Elégségesség és nem-növekedési opciók | Sufficiency, enough and non-growth options | szervezet | jelölt |  | 5 |
| ÚJ-03 | Értékmegragadás és árazási modell | Value capture and pricing model design | szervezet | jelölt |  | 14 |

### Operating System & Execution

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-43 | Priorizálás és fegyelmezett fókusz | Prioritization and disciplined focus | csapat | jelölt | csábító | 10 |
| RC-44 | Tulajdonosi szemlélet és végigvitel | Ownership and dependable follow-through | csapat | jelölt |  | 10 |
| RC-45 | Dinamikus képesség-portfólió | Dynamic capability portfolio | szervezet | használt |  | 9 |
| RC-46 | Működési ritmus és reflexiós ciklus | Operating rhythm and reflective cadence | csapat | jelölt | csábító | 17 |
| RC-47 | Utódlás és intézményi emlékezet | Succession and institutional memory | szervezet | jelölt |  | 12 |
| RC-48 | Működési ellenálló-képesség | Operational resilience and continuity | szervezet | használt |  | 9 |
| ÚJ-02 | Közös folyamat-nyelv | Shared process language | szervezet | jelölt |  | 10 |

### Learning & Innovation

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-49 | Kritikus gondolkodás, bizonyíték-írástudás | Critical thinking and evidence literacy | egyén | jelölt | készség-jellegű | 4 |
| RC-50 | Kreativitás és problémakeretezés | Creativity and generative problem framing | egyén | jelölt | készség-jellegű | 3 |
| RC-51 | A vezető mint keretteremtő és coach | Manager as context builder and coach | csapat | jelölt | csábító | 9 |
| RC-52 | Visszajelzés, coaching, képesség-sokszorozás | Feedback, coaching and capability multiplication | csapat | használt | csábító | 13 |
| RC-53 | Szervezeti tanulás és tudásáramlás | Organizational learning and knowledge flow | szervezet | jelölt | csábító | 8 |
| RC-54 | Felelős innováció-irányítás | Responsible innovation governance | szervezet | csak irodalom |  | 2 |

### AI, Data & Technology

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-55 | MI- és adatírástudás | AI and data literacy | egyén | csak irodalom |  | 1 |
| RC-56 | Emberi ítélet automatizált munkában | Human judgment in automated work | egyén | csak irodalom |  | 0 |
| RC-57 | Ember–MI munkafolyamat-tervezés | Human-AI workflow design | csapat | csak irodalom |  | 0 |
| RC-58 | Adatvezérelt tanulás és mérés | Data-informed learning and measurement | csapat | csak irodalom |  | 2 |
| RC-59 | Felelős MI-irányítás | Responsible AI governance | szervezet | csak irodalom |  | 1 |
| RC-60 | Digitális ellenálló-képesség és autonómia | Digital resilience, security and autonomy | szervezet | csak irodalom |  | 0 |

### Regenerative Value & Ecology

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-61 | Ökológiai írástudás és kapcsolódás | Ecological literacy and connectedness | egyén | csak irodalom |  | 1 |
| RC-62 | Tudatos erőforrás-használat | Conscious resource use and personal sufficiency | egyén | csak irodalom |  | 0 |
| RC-63 | Körforgásos és regeneratív tervezés | Circular and regenerative design | csapat | csak irodalom |  | 0 |
| RC-64 | Regeneratív értéklánc-együttműködés | Regenerative value-chain collaboration | csapat | csak irodalom |  | 0 |
| RC-65 | Bolygóhatár- és hely-alapú stratégia | Planetary-boundary and place-based strategy | szervezet | csak irodalom |  | 0 |
| RC-66 | Nettó pozitív hatás és helyreállítás | Net-positive impact and restoration capability | szervezet | csak irodalom |  | 0 |

### Governance, Risk & Accountability

| Azonosító | Név | Angol név | Szint | Státusz | Megjegyzés | Minták |
|---|---|---|---|---|---|---|
| RC-67 | Kockázat-érzékelés és felelős eszkaláció | Risk sensing and responsible escalation | egyén | jelölt |  | 8 |
| RC-68 | Átláthatóság és bizonyíték-címkézés | Transparency and evidence labeling | egyén | jelölt |  | 8 |
| RC-69 | Integrált kockázat- és lehetőségértékelés | Integrated risk and opportunity assessment | csapat | jelölt |  | 4 |
| RC-70 | Nyomáspróba és szándékos kihívás | Pressure testing and red teaming | csapat | jelölt | csábító | 3 |
| RC-71 | Érintetti irányítás és elszámoltathatóság | Stakeholder governance and accountability | szervezet | jelölt | csábító | 9 |
| RC-72 | Hatásmérés és átlátható beszámolás | Impact measurement and transparent reporting | szervezet | használt |  | 10 |
| ÚJ-01 | Döntési jog és mandátum tisztázása | Decision rights and mandate clarity | szervezet | jelölt |  | 18 |
