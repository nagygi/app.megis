# 20 · Admin felület – olvasat

## Cél

A diagnózis munkájának zöme, Teljesnél 2–3 óra (A3). Az admin mérőpontonként átnézi a számot és a mögötte álló bizonyítékot. Jóváhagyja, vagy indoklással átírja. Jóváhagyja az állításokat és az olvasat szövegeit is.

Ez a 2. kapu: a riportba csak jóváhagyott szám és mondat kerül, és rögzül, ki és mikor hagyta jóvá („a mégis átnézte”).

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 20.1 A lap

A fő diagnózis *Olvasat* füle (19.1), három oszloppal, balról jobbra:

- **Mérőpontok:**
  - blokkonként, a riport sorrendjében: értékek, folyamatok, területek, a fa, a végén az olvasat szövegei (20.4);
  - soronként név, PHI és állapot (vár · most · jóváhagyva · átírva);
  - fent számláló: „14 / 19 jóváhagyva”.
- **Bizonyíték:**
  - a kiválasztott sor nyomai (18.2), forrással, szereppel, dátummal és névvel;
  - az anyag megnyitható: átirat, hang, dokumentum;
  - csapatnál külön: „vezető 5 · csapat 3 · helyzetek 2,4”;
  - folyamatnál az alkalmak listája (13 · 13.5).
- **Amit a vezető olvas:** a sor állításai és a rés mondata, úgy, ahogy a riportban megjelennek.

### 20.2 Műveletek soronként

- **Jóváhagyom:** a sor zöld lesz, a lap a következő sorra lép.
- **Blokk jóváhagyása:** megerősítés után a blokk összes, még nem átírt sorát egyszerre jóváhagyja. Soronként így is rögzül, ki és mikor hagyta jóvá.
- **Átírom:** a PHI (1–6 vagy „–”) és/vagy a mondat írható át, kötelező indoklással.
  - Rögzül, ki és mikor írta át, és mi volt az automatikus érték (mint a 13 · 13.5-ben).
  - Az indoklás a Mellékletbe kerül, a mégis nevével és dátummal, és az ügyfél is olvassa (28.2). A mező alatt ez áll: „Ezt a vezető is látja a Mellékletben.”
  - Folyamat sorának átírása a Folyamat-diagnózis pontját is módosítja (13).
- **Nyomok:**
  - egy nyom kivehető a riportból, a nyers detektálás ettől megmarad;
  - egy nyom kiemelhető, ez jelenik meg elsőként a fejezet-lapon;
  - új nyom kézzel is felvehető: szöveg, forrás-anyag választóból, dátum, szerep.
- **Nincs elég adat:** a sor PHI nélkül, ezzel a jelöléssel kerül a riportba.
- **Területek és a fa:** nincs automatikus érték (18.4). Az admin adja meg a PHI-t, és a diagnózis nyomai közül választja ki a bizonyítékot (blokkra, forrásra és szóra szűrhetően). Itt nem kell indoklás, mert nincs mit felülírni.
- A jóváhagyott sor a publikálásig újranyitható.

### 20.3 Állítások

- Fejezetenként lista: típus, sor, mondat, nyomok. AI-vázlatként érkeznek (18.3).
- Szerkeszthetők, törölhetők, új is felvehető. Mindegyik külön jóváhagyást kap.
- Csak jóváhagyott állítás kerül a riportba, „a diagnózis állítása · a mégis átnézte” jelöléssel.

### 20.4 Az olvasat szövegei

- **A legnagyobb rés** (18.3): elfogadható, vagy másik sor választható helyette.
- **Szövegek:** vezetői összefoglaló, a márkád alapjának olvasata, a mégis javaslata (18.3). AI-vázlatként érkeznek (16), az admin átírja.
- md szerkesztővel írhatók (NFR).
- A riportot telefonon olvassák, ezért az állítás és az olvasat szövegei legfeljebb 160, a márkád alapjának olvasata legfeljebb 600 karakter.

### 20.5 Jóváhagyás és napló

- Minden jóváhagyás, átírás és visszanyitás rögzül: ki, mikor, mit.
- Az olvasat alatt a fő diagnózis 04-es státusza „Jóváhagyásra vár” (17.5).
- Ha minden sor, állítás és szöveg jóvá van hagyva, a *Publikálás* fül (21) aktív lesz. Más játékos elem (telő évgyűrű, „felragyogó” fül) nincs.
- Több admin dolgozhat egyszerre ugyanazon az olvasaton, zárolás nélkül. Soronként látszik, ki hagyta jóvá. Ütközésnél az utolsó mentés nyer, a napló mindkét lépést mutatja.
- Ha egy társ visszavonja a hozzájárulását (T1, M6), az automatikus nyomai kikerülnek, és a sorok újraszámolódnak (18.6). A jóváhagyott sor, amelynek az automatikus értéke ettől változik, visszaesik „vár” állapotba, és a napló jelzi.
- A részdiagnózisok (17.4) innen megnyithatók, de nem kell őket külön jóváhagyni.

## Nincs benne

- A képesség-jelöltek és a lépcső jóváhagyása: [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- A visszajelzések beépítése: 22.
- A számítás és az AI-vázlat: 18.
- Nyom kijelölése az átiratból („Nyomként felveszem”).

## Hátravan

- **Visszavont hozzájárulás az olvasat alatt:** a napló sora általános okot ír („az automatikus érték változott”), nem a visszavonást. A társ kézzel felvett nyomai bent maradnak. Az érintett, jóváhagyott sor jóváhagyva marad, ha az automatikus értéke nem változik.
