# 28 · Diagnózis – visszajelzés, Melléklet, PDF

## Cél

Három dolog, ami a riportot (27) körülveszi:

- a vezető fejezetenként jelezheti, ráismer-e magukra (R2·c);
- a Mellékletben látja, honnan jön minden szám (RM);
- a riport, a snapshot és az Útravaló PDF-ben letölthető.

## Alfeladatok

### 28.1 Magatokra ismertek? (R2·c)

- **Hol:** minden fejezet alján (értékek, folyamatok, területek, a márkád alapja), a snapshotnál egyszer, a lap végén. A mélyítő lapokon nincs: a tétel a „Miben nem?” szövegben nevezhető meg.
- **Gombok:** Igen · Részben · Nem így látjuk.
- **„Részben” és „Nem így látjuk”** egy sort nyit: „Miben nem?”. Ide szöveg írható, vagy hang mondható a 06-os felvevővel, átirattal. Gomb: „Küldés”.
- **Elküldés után:** „Elküldve a mégisnek · {dátum} · „Részben” · a mégis beépíti az olvasatba.”
- A visszajelzés a Beérkezőbe kerül (22). Minden „Részben” és „Nem így látjuk” új sort nyit, az „Igen” nem kerül a Beérkezőbe.
- **Módosítható:** a vezető újra válaszolhat, mindig a legutolsó számít.
- **Meddig:** amíg a diagnózis publikált (17.5).
- **Beépítés után** a fejezet fejlécén ez látszik: „frissítve · {dátum} · a ti visszajelzésetek alapján” (21.4).
- A visszajelzés pontot nem ír át.

### 28.2 Melléklet (RM)

- **Hol érhető el:** a menüből („Honnan tudjuk?”) és minden fejezet aljáról.
- **Fejléc-blokkok:**
  - **Kire:** „{Vezető} és a {Csapat} · 1 vezető · {n} társ, {k} kitöltötte”.
  - **Miből:** „{n} beszélgetés · {m} felvétel · {k} dokumentum · {j} Értéktükör · naptár, 90 nap”. Csak belső forrás, csatorna nem. A naptár csak akkor, ha volt; a PDF-ben nem szerepel.
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
- **A riport PDF-je:** az R0 a fejezetek táblájával; Teljesnél az R1 pontjai (a legnagyobb rés, egy erősség, egy repedés); a mégis javaslata; fejezetenként egy lap minden sorral, a definícióval, az állításokkal és legfeljebb 3 idézettel; a zárólap és egy rövid Melléklet (Ki olvasta, Miből, amit a mégis átírt, a mérőpontok). Csak a Csapat nézet, más nézet nem.
- **Fejléc és lábléc:** mégis logó, a diagnózis neve, a dátum, és „Bizalmas · {Csapat}”.
- **Fájlnév:** `megis-diagnozis-{csapat}-{datum}.pdf`, illetve `megis-utravalo-{datum}.pdf`.
- Az tölthet le, aki a lapot látja (27.1, 29).
- A riport a felületen ugyanúgy néz ki, mint előtte; a PDF egy külön, nyomtatásra szánt nézet.
- **Kikapcsolható (2026-10-27):** `MEGIS_PDF_DOWNLOADS=false` elrejti a felhasználói oldal „Letöltöm · PDF” gombjait (zárólap, Melléklet, Útravaló, a riportlista „PDF” gombja). Alapból be van kapcsolva. A letöltési címek kikapcsolva is működnek.

## Nincs benne

- A „Kérdezem a Coachot” út a visszajelzésnél: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- Megosztás linkkel: a zárólapon „hamarosan” (27.11).
- A visszajelzések összesítése diagnózisokon át (22).

## Hátravan

- **„Igen”-összesítés:** a diagnózis oldalán nincs összesítés („4 fejezetből 3 Igen”). Most semmi nem gyűjti össze a válaszokat.
- **Visszajelzés lezárás után:** lezáráskor a gomboknak el kell tűnniük, az elküldött visszajelzés állapotának olvashatónak kell maradnia. Most lezárás után az egész riport 403-at ad.
- **A riport PDF-je:** nincs külön R1·b lap. A Mellékletből hiányzik a Kire és a Mikor sor, az idézetek és az állítások. A snapshot PDF-je az általános elrendezést használja, nem az S1-et és az S1·b-t.
