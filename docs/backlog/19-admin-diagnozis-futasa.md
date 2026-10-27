# 19 · Admin felület – a diagnózis futása

## Cél

Amíg a diagnózis fut, az admin egy lapon látja, hol tart (A2): a vezető, a társak, a folyamatok, a beérkezett anyagok és a mégis beszélgetései. Itt dönt a küszöbről, innen küld emlékeztetőt, és innen indítja az olvasatot. A *Diagnózisok* lista adja az áttekintőt (A0).

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 19.1 A fő diagnózis oldala

- A fő diagnózis (17) oldala fülekből áll: *Beállítás* (17) · *Futás* · *Olvasat* (20) · *Publikálás* (21) · *Részdiagnózisok* (17.4).
- Mindig az aktuális szakasz füle nyílik meg (17.5).
- A *Futás* fület csak a szervezet kapcsolattartója (02) és a szuperadmin látja. Helyettesítéskor a szervezetnél át kell írni a kapcsolattartót.
- **Fejléc:** szervezet · csapat · termék · szakasz · „23. nap / 47” · kapcsolattartó. A fejléc szervezet-színű (02).

### 19.2 Áttekintő: a kert (A0)

- Bal oldali menüpont: *Diagnózisok*, a kert-nézettel nyílik. Fent egy mondat, linkkel a Beérkezőre: „Ma {n} dolog vár rád.”
- **Oszlopok szakaszonként** (17.5), a prototípus jeleivel: talaj (Meghívva) · gyökér (Kitöltés) · törzs (Fut) · évgyűrű (Olvasat) · gyümölcs (Publikálva). A Vázlat és a Lezárva szűrővel látszik.
- **Kártya:** a diagnózis neve, az alany jele (csapat: micélium; egy ember vagy magánszemély: kör), a termék, és egy szám, ami a szakaszhoz illik: lépés („5 / 6”), társ („2 / 6 társ”), jóváhagyás („14 / 19 jóváhagyva”) vagy visszajelzés („2 visszajelzés”).
- **Coral keret,** ha a diagnózisnak nyitott sora van a Beérkezőben (22), pl. küszöb, besorolás, lejárt meghívó.
- **Akciók:** a kártya a diagnózis szakaszához illő fülre visz; „+ Új diagnózis” az A1-re (17). Szűrő: csapat · egy ember · magánszemély, és kapcsolattartó.
- Amikor egy kártya a gyümölcs oszlopba lép, a jele egyszer felvillan. Más játékos elem nincs.
- A *Diagnózisok* lista (04 · 4.2) egy váltóval továbbra is elérhető, szakasz-oszloppal.

### 19.3 Futás lap

Számok és állapotok, válaszok nélkül, ahogy a vezető is látja. A válaszok névvel a részdiagnózisok oldalán érhetők el (17.4).

- **Időablak:** sáv a kezdéstől a publikálásig, a mai nap jelölve.
- **A vezető:** a lépései (17.3) állapottal (kész · most · hátra), utolsó belépés, a beküldött helyzetek száma.
- **Társak** (csak Teljes, csak csapat):
  - meghívva · belépett · kitöltötte (T4) · visszavonta;
  - lenyitva név szerint;
  - „A küszöbhöz még {n} kell.” (18.6).
- **Folyamatok:** mért · bizonytalan · kizárt, link a szervezet *Folyamatok* fülére (11.6).
- **Anyagok:** feltöltések és helyzetek száma; feldolgozás alatt, sikertelen és besorolatlan anyagok, link a Beérkezőre.
- **Naptár:** állapot, „a metaadat {dátum}-ig él” (09).
- **Beszélgetések a mégissel:** 19.5.
- Magánszemélynél a társak sora nem jelenik meg.

### 19.4 Döntések és műveletek

- **Küszöb.** Ha a publikálásig 7 napnál kevesebb van, és a vezetőn kívül 3-nál kevesebb társ töltötte ki a megélést, egy sáv jelenik meg: „A küszöb veszélyben. Mit teszel?” Három gomb:
  - „Szólok a vezetőnek”: e-mail sablonból, küldés előtt szerkeszthető.
  - „Várok 5 napot”: a jelzés 5 napra elhallgat, utána újra megjelenik. A publikálás napja nem változik. Ha közben meglesz a 3 kitöltő, a jelzés magától eltűnik.
  - „Csapat-szám nélkül megy”: rögzül, ki és mikor döntött; a riport a 18.6 szerint jelenik meg.
  - A döntés a publikálásig visszavonható.
- **Emlékeztető:** egy gombbal a vezetőnek (ha nem fejezte be a kitöltést), vagy egyszerre minden társnak, aki még nem töltötte ki. Egy címzett legfeljebb 48 óránként kaphat.
- **Időablak módosítása:** a publikálás napja átírható; a vezető e-mailt kap.
- **Olvasat indítása:** a szakasz „Olvasat” lesz (17.5). Ha van még feldolgozás alatti anyag, megerősítést kér.

### 19.5 Beszélgetések a mégissel

- **Az admin rögzíti a beszélgetéseket:** időpont, kivel (vezető, társ vagy külsős név), ki vezeti (admin), megjegyzés.
- **Előre megadott szám nincs:** az admin annyi beszélgetést vesz fel, amennyi lesz.
- **Felvétel vagy leirat** a fő diagnózishoz kötve tölthető fel (17.4, 11. kérdés), „beszélgetés a mégissel” forrással.
- A vezető a V10-en ezt látja: „Beszélgetések a mégissel · 2 megtartva · következő: 03.24. 10:00 · Luca”.

## E-mailek

- **Emlékeztető a vezetőnek:** „Még {n} lépés van hátra a {Csapat} diagnózisából, kb. {perc} perc. [Folytatom]”
- **Emlékeztető a társnak:** „{Vezető} kíváncsi, hogyan látod te. Kb. 5 perc. [Kezdem]”
- **Küszöb, a vezetőnek:** „A rés csak akkor látszik, ha legalább 3 társ válaszol. Most {k} válaszolt, még {n} kell. Szólnál nekik? [Társak]”
- **Időablak módosult:** „A {Csapat} diagnózisa {dátum}-ra készül el.”

## Nincs benne

- Automatikus emlékeztetők: [NM-20](../backlog_nem_MVP/NM-20-automatikus-emlekeztetok.md).
- Időpontfoglalás külső naptárból.
