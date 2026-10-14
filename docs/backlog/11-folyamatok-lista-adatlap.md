# 11 · Folyamatok – lista, adatlap, összegzés

## Cél

A vezető jóváhagyja a detektált folyamatokat, kézzel kiegészíti őket, és megadja a felelőst, a résztvevőket és a saját mérőszámokat (V6–V6·f). A „mérjük” állapotú folyamatok kerülnek a diagnózisba, ezekből mér a 13. A lista később a *Tárak → Folyamatok* (M4) alól is elérhető.

- A folyamat a **szervezeté** (02), és opcionálisan csapat(ok)hoz köthető. A diagnózis rögzíti, mely folyamatokat méri.
- A mért folyamatok száma a Teljesben nincs korlátozva, a Belső snapshotban legfeljebb 2 (18 · 18.1).
- A lépés a Teljes és a Snapshot · Belső termékben fut, a Snapshot · Külsőben kimarad.
- A diagnózis beállítása (A1, a termék szerinti lépések, az időablak): 17.

**Ki használja**

- A szervezet vezetője.
- Az admin a szervezet *Folyamatok* fülén (11.6).
- A társ a folyamatokat csak a feltöltésnél látja, és csak azokat, amelyekben résztvevő (12).

## Alfeladatok

### 11.1 Belépő (V6)

- **Két kártya:**
  - **Naptárból** (ajánlott, 2 perc) → 09;
  - **Beszélgetésből** (6–8 perc) → 10.
- **„Amit utána mérünk”:** értékazonosság folyamatonként (1–6) és saját mérőszámok (opcionális).
  - A képességek sora nem jelenik meg: [NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md).
- **„Így néz ki egy kész rutin”:** minta-adatlap, csak olvasható.
- **„Kihagyom”:** a lépés kihagyott állapotba kerül, később pótolható. Folyamat nélkül nem készül Folyamat szintű diagnózis (13).

### 11.2 Detektált folyamatok (V6·d)

A lista gépi javaslat, jóváhagyásra. Négy csoportja van: Erős minta · Bizonytalan · Kézzel hozzáadva · Egyéb.

**Kártya**

- Név, mellette „átnevezés”. Az átnevezés nyoma megmarad: a régi név, ki és mikor nevezte át.
- Ritmus · létszám · előfordulás.
- Konfidencia: magas / közepes / alacsony / kézzel / beszélgetésből.
- Típusváltó:
  - **rutin:** mérjük, a diagnózisba kerül;
  - **egyszeri:** az *Egyéb* közé kerül, nyomként számít, de saját pontot nem kap;
  - **nem mérjük:** kizárva, a „Kimaradt” sorban látszik. Feltöltésnél nem választható (12).
- Gombok: „+ alfolyamat”, „Elfogadom” / „Visszavonom”.

**Alfolyamat**

- Legfeljebb két szint: folyamat → alfolyamat.
- Lehet detektált (sárga vonallal jelölve) vagy kézzel felvett.
- Az alfolyamat alkalmai a szülő folyamat diagnózisába számítanak, saját diagnózisa nincs.

**Kézi hozzáadás** („Folyamat vagy alfolyamat hozzáadása”)

- Mezők:
  - név (kötelező);
  - ritmus: heti · kétheti · havi · negyedéves · alkalmi;
  - szülő folyamat (opcionális).
- Jelölése: „nincs a naptárban · 0 alkalom”.
- Ugyanúgy mérhető, mint a többi. Alkalmat a feltöltéskor kap (12).

**A lap alja**

- „Ami továbbmegy”: ugyanaz a lista, mint a V6·f-en és az M4-ben.
- „N folyamat elfogadása”: az összes még el nem fogadott rutint elfogadja.
- „Később folytatom”: az állapot mentődik.

### 11.3 Adatlap (V6·e)

- **Fejléc:** ritmus · hossz · alkalmak száma.
- **Felelős** (opcionális): a szervezet egy felhasználója.
  - Az emlékeztető az [NM-10](../backlog_nem_MVP/NM-10-emlekezteto-folyamat-felelosenek.md) jegyben jön, addig a felület nem ígéri.
- **Résztvevők:** a naptárból jönnek, e-mail alapján a szervezet felhasználóihoz párosítva.
  - Aki nem felhasználó, a nevével „külsős” jelölést kap. Nem számít bele a létszám limitbe, és nem kap meghívót.
  - Szerkeszthető: felhasználó hozzáadható, bárki eltávolítható.
- **Csapat(ok)** (opcionális).
- **Saját mérőszám** (opcionális, több is lehet):
  - Mezők:
    - név (kötelező);
    - mértékegység (pl. %, db, nap);
    - ritmus: heti · havi · negyedéves;
    - célérték és leírás (opcionális).
  - Értéket a felelős, a vezető vagy az admin rögzít, dátummal. A folyamat lapján az utolsó érték és a korábbiak listája látszik.
  - A PHI-be nem számít.
- **Gombok:** „Kész” · „Vissza a listához”. Az adatlap bármikor visszanyitható az M4-ből.
- „A folyamat lapja” link csak publikálás után jelenik meg (R3·b).

### 11.4 Összegzés (V6·f)

- **Fejléc:** „N folyamat kerül a diagnózisba.”
- **Bekerül · mérjük:** soronként ritmus · létszám · felelős · alfolyamatok száma.
- **Megvan, de nincs a naptárban** (coral): a kézzel felvett folyamatok, ugyanolyan súllyal.
- **Kimaradt:** a „nem mérjük” folyamatok, és alattuk: „N egyéb esemény · nyomként beszámít”.
- **A mégis javaslata:** az admin írja (11.6). Ha üres, nem jelenik meg.
- Minden sor az adatlapra nyílik.
- A „Tovább” a termék szerinti következő lépésre visz.

### 11.5 Tárak → Folyamatok (M4)

- **Fejléc:** „naptár összekötve · 03.02. · 7 rutin · 5 mérve”.
- **Soronként:** név, ritmus, felelős, létszám, naptárban van-e, mérjük-e. Pont csak publikálás után látszik.
- „+ Rutin”: kézi hozzáadás. A sor az adatlapra nyílik.
- **Szerkesztés:**
  - a diagnózis futása alatt szerkeszthető;
  - publikálás után a diagnózishoz rögzített lista nem változik, a tár viszont tovább szerkeszthető.

### 11.6 Admin: a szervezet *Folyamatok* füle

- A szervezet oldalának ötödik füle: *Adatok · Csapatok · Felhasználók · Értékek · Folyamatok*.
- **Lista** az NFR listaszabályai szerint:
  - Oszlopok: név, szülő, forrás (naptár / beszélgetés / kézi), konfidencia, állapot, felelős, alkalmak száma, töréspont-jelölt (10).
  - A *Bizonytalan* és az *Egyéb* sorok külön blokkban látszanak, átnézésre.
- **Naptár sor:** forrás, mióta van összekötve, eseményszám, „a metaadat {dátum}-ig él”, hibaállapot (09).
- **Szerkesztés:** az admin ugyanazokkal a szabályokkal szerkeszthet, vehet fel és zárhat ki folyamatot, mint a vezető. Az átnevezés nyoma itt is megmarad.
- **A mégis javaslata:** opcionális szöveg, legfeljebb 255 karakter. A V6·f-en jelenik meg.

## Nincs benne

- Emlékeztető a felelősnek: [NM-10](../backlog_nem_MVP/NM-10-emlekezteto-folyamat-felelosenek.md).
- Képességek mérése: [NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md).
- Szervezeti idő, hőtérkép, értékkép: [NM-13](../backlog_nem_MVP/NM-13-folyamatok-ertekmeresben.md).
- Csatornák és assetek (V7): egyeztetés alatt.
