# 12 · Folyamat alkalmai – feltöltés, átirat, megélés

## Cél

Amíg a diagnózis fut, a vezető, a társ és az admin anyagot ad egy folyamat egy alkalmához (V11–V11·c). Ez lehet hangfájl, leirat vagy dokumentum, vagy egy rövid beszámoló arról, hogyan ment az alkalom. A hangból beszélő-felismeréssel átirat készül. Az anyagokból mér a 13.

**Alkalom:** egy folyamat egy lezajlott eseménye. A naptárból jön (09), vagy a feltöltéskor jön létre a megadott dátummal.

**Ki tölthet fel**

- A vezető bármelyik mért folyamathoz.
- A társ csak azokhoz, amelyekben résztvevő (T5).
- Az admin bármelyikhez (12.6).
- A feltöltő a saját feltöltéseit látja, másét nem.
- A feldolgozás eredményét az ügyfél nem látja, csak az admin (V11·b).

## Alfeladatok

### 12.1 Feltöltés (V11)

A coral pluszból nyílik. Egy lap minden bejövő útnak.

- **Mérés indítása:**
  - Értéktükör (06 · 6.2);
  - **Egy helyzet:** egy alkalom egy rutinból, elmondva vagy leírva (12.3);
  - a „Megjelenés kifelé” sor nem jelenik meg (V7, egyeztetés alatt).
- **Feltöltés:** egy doboz, típus nélkül.
  - Hang: mp3, m4a, wav vagy webm, legfeljebb 90 perc és 300 MB.
  - Szöveg: txt, md, doc, docx vagy pdf, legfeljebb 10 MB.
  - A szabályok ki vannak írva (NFR).
- Feltöltési kvóta nincs. A költség szervezetenként a Rendszer → AI lapon látszik (16).
- **Integráció:** a naptár állapota (09), link a Profilra.
- **Feldolgozás alatt:** soronként fájl · dátum · állapot.
  - Állapotok: feldolgozás alatt · a megélés nyitva („Befejezem” gomb) · kész.
  - Sikertelen feltöltésnél a sorban coral szöveg áll: „Nem tudtuk beolvasni.” Mellette: Újra · Kiveszem.

### 12.2 Hova tartozik (V11·b)

- Kérdés: „Melyik rutinhoz kötődik?”
- **A lista:**
  - A feltöltő számára elérhető, mért folyamatok.
  - A naptárból felismert legutóbbi alkalom előre ki van jelölve, dátummal („Retró a naptárból · 03.14.”).
  - A „nem mérjük” folyamatok szaggatottan látszanak, és nem választhatók.
- **Egyikhez sem:** az admin sorolja be (12.6).
- **Mikor volt?** Dátum, előre kitöltve. Ha a folyamatnak aznap van naptári alkalma, ahhoz kötjük. Ha nincs, új alkalom jön létre.
- **Hozzájárulás** (hangnál és leiratnál kötelező): jelölőnégyzet, ezzel a szöveggel: „A résztvevők tudnak a felvételről, és hozzájárultak.”
  - Rögzítjük, ki és mikor jelölte be.
  - Az adatkezelési tájékoztató (05) kitér rá.
- Egy alkalomhoz több anyag is tartozhat, például ha a vezető és egy társ is feltölti.

### 12.3 Egy helyzet, elmondva vagy leírva

- Rövid beszámoló egy alkalomról, a 06-os felvevővel vagy szövegben. A lista sorában például: „elmondva · 0:42”.
- Utána ugyanaz a „Hova tartozik” lap következik.
- Hozzájárulási nyilatkozat nem kell, mert ez a saját beszámoló, nem a megbeszélés felvétele.
- A maximális hossz ugyanaz, mint az Értéktükörnél (06).

### 12.4 A megélésed (V11·c)

- A feltöltés után, az elemzés előtt jelenik meg: „Hogyan élted meg ezt a helyzetet?”
- **Fejléc:** folyamat · dátum · az anyag típusa.
- **Megjegyzés** (opcionális), elmondva vagy leírva.
- **Soronként 1–6 vagy „nem tudom”:** a szervezet értékei és a márkád alapjának négy eleme.
- **Eredményes volt?** Igen · Nem · Részben · Nem tudom.
- **Gombok:**
  - „Beküldöm”: minden sorra kell válasz, a fejléc számol („9 / 9 megválaszolva”);
  - „Kihagyom”: egyenrangú út. Alkalmanként nem kötelező, és később pótolható.
- A válasz az alkalomhoz és a felhasználóhoz tartozik. A felhasználását a 13-as jegy írja le.

### 12.5 A helyzeteid (T5·b)

- A vezetőnél és a társnál ugyanaz a lap. A beküldött anyagok időrendben látszanak: rutin, dátum, elmondva vagy feltöltve, és van-e hozzá megélés.
- Ahol a megélés hiányzik, coral szöveg áll: „megélés hiányzik”. Erre koppintva a V11·c nyílik.
- A publikálásig javítható, utána csak olvasható.
- A saját hangfelvétel a 06 · 6.6 szerint törölhető: csak a hang törlődik, az átirat megmarad.

### 12.6 Átirat és admin

- **Átirat:**
  - A hangból beszélő-felismeréssel készül átirat (Beszélő 1, 2, …). A beszélők résztvevőkhöz rendelése: [NM-11](../backlog_nem_MVP/NM-11-beszelok-hozzarendelese.md).
  - A leirat meglévő beszélő-címkéi megmaradnak.
  - A dokumentum szövegként, beszélő nélkül kerül be.
- **Tárolás:** a 06 · 6.6 szerint.
- **Admin, a szervezet *Folyamatok* fülén (11.6):**
  - **„Besorolásra vár” blokk:** az „Egyikhez sem” anyagok. Az admin folyamathoz és dátumhoz köti őket, vagy *Egyéb*-nek jelöli. Ugyanezek a sorok a Beérkezőben (22) is megjelennek.
  - **Az alkalmak listája** folyamatonként: dátum, anyagok, feltöltő, állapot, a megélések száma. Az átirat megnyitható, a hang lejátszható.
  - **„+ Anyag”:** az admin is feltölthet, ugyanazokkal a szabályokkal.

### 12.7 Átíró szolgáltató (PoC)

- 2–3 szolgáltatót vetünk össze ugyanazon a magyar meetingfelvételen (90 perc, 4–6 beszélő).
- Szempontok: pontosság, beszélő-felismerés, EU-s adatkezelés, ár óránként, fájlméret-korlát.
- Az eredmény a 06-ra is érvényes (a 06-os jegy 1. nyitott kérdése).

## Nincs benne

- Felvétel az alkalom alatt (böngészős mikrofon, meeting-bot): [NM-09](../backlog_nem_MVP/NM-09-felvetel-az-alkalom-alatt.md).
- Emlékeztető a felelősnek: [NM-10](../backlog_nem_MVP/NM-10-emlekezteto-folyamat-felelosenek.md).
- A beszélők hozzárendelése a résztvevőkhöz: [NM-11](../backlog_nem_MVP/NM-11-beszelok-hozzarendelese.md).
- Megjelenés kifelé (V7): egyeztetés alatt.
