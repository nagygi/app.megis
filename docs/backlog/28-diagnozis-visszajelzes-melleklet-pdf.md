# 28 · Diagnózis – visszajelzés, Melléklet, PDF

## Cél

Három dolog, ami a riportot (27) körülveszi:

- a vezető fejezetenként jelezheti, ráismer-e magukra (R2·c);
- a Mellékletben látja, honnan jön minden szám (RM);
- a riport, a snapshot és az Útravaló PDF-ben letölthető.

## Alfeladatok

### 28.1 Magatokra ismertek? (R2·c)

- **Hol:** minden fejezet alján (értékek, folyamatok, területek, a márkád alapja), a snapshotnál egyszer, a lap végén.
- **Gombok:** Igen · Részben · Nem így látjuk.
- **„Részben” és „Nem így látjuk”** egy sort nyit: „Miben nem?”. Ide szöveg írható, vagy hang mondható a 06-os felvevővel, átirattal. Gomb: „Küldés”.
- **Elküldés után:** „Elküldve a mégisnek · {dátum} · „Részben” · a mégis beépíti az olvasatba.”
- A visszajelzés a Beérkezőbe kerül (22).
- **Beépítés után** a fejezet fejlécén ez látszik: „frissítve · {dátum} · a ti visszajelzésetek alapján” (21.4).
- A visszajelzés pontot nem ír át.

### 28.2 Melléklet (RM)

- **Hol érhető el:** a menüből („Honnan tudjuk?”) és minden fejezet aljáról.
- **Fejléc-blokkok:**
  - **Kire:** „{Vezető} és a {Csapat} · 1 vezető · {n} társ, {k} kitöltötte”.
  - **Miből:** „{n} beszélgetés · {m} felvétel · {k} dokumentum · {j} Értéktükör · naptár, 90 nap”.
  - **Mikor:** az időablak.
  - **Ki olvasta:** „mégis · publikálva {dátum}”.
- **Három lenyitható lista**, az első nyitva:
  - **Idézetek:** szereppel és dátummal, név nélkül, dátum szerint.
  - **Állítások bizonyítékkal:** típusonkénti darabszám (erősség, repedés, sejtés); soronként az állítás, a dimenzió és a nyomok száma.
  - **Mérőpontok:** mind, PHI-vel.
- **Amit a mégis átírt:** soronként a mérőpont, a régi és az új pont, az indoklás és a dátum (20.2). Név helyett „mégis” áll. Ez a bizalom lapja: ha a mégis felülírta a gépi számot, annak is látszania kell.

### 28.3 PDF

- **Mi tölthető le:**
  - a Teljes riport;
  - a Belső snapshot;
  - az Útravaló (29);
  - a Melléklet.
- A PDF a publikált, fagyasztott adatokból készül, szerveroldalon. Interaktív elem nincs benne.
- **Fejléc és lábléc:** mégis logó, a diagnózis neve, a dátum, és „Bizalmas · {Csapat}”.
- **Fájlnév:** `megis-diagnozis-{csapat}-{datum}.pdf`, illetve `megis-utravalo-{datum}.pdf`.
- Az tölthet le, aki a lapot látja (27.1, 29).
- A riport a felületen ugyanúgy néz ki, mint előtte; a PDF egy külön, nyomtatásra szánt nézet.

## Nincs benne

- A „Kérdezem a Coachot” út a visszajelzésnél: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- Megosztás linkkel (27 · 6. kérdés).
- A visszajelzések összesítése diagnózisokon át (22).

## Nyitott kérdések

1. **Hol lehet visszajelezni?** A prototípus fejezetenként kér visszajelzést, a mélyítő lapokon nem.

   **Javaslat:** csak fejezetenként (R2, R3, R4, R5), a snapshotnál egyszer. A „Miben nem?” szövegben úgyis megnevezhető a tétel.

2. **Módosítható-e a visszajelzés?**

   **Javaslat:** igen, mindig a legutolsó számít. Ha új szöveg is jön, a Beérkezőben új sor nyílik. Az „Igen” nem kerül a Beérkezőbe, csak a diagnózis oldalán látszik összesítve („4 fejezetből 3 Igen”).

3. **Meddig lehet visszajelezni?**

   **Javaslat:** a publikálástól a diagnózis lezárásáig (17.5). Lezárás után a gombok eltűnnek, az elküldött visszajelzések állapota olvasható marad.

4. **Mi kerüljön a riport PDF-be?**

   **Javaslat:**
   - Teljesnél: R0, R1, R1·b, a négy fejezet mélyítő lapokkal, a zárólap és a Melléklet. A nézet a Csapat, más nézet nem.
   - Snapshotnál: S1, S1·b és a Melléklet.

5. **A Melléklet „Miből” sora.** A prototípusban „4 csatorna, 6 hét” is szerepel, ami a Brand-oldalhoz tartozik.

   **Javaslat:** csak a belső források: beszélgetés, felvétel, dokumentum, Értéktükör, naptár. Csatorna nem.
