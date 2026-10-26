# 26 · Diagnózis – a vezető kezdőlapja

## Cél

A vezető kezdőlapja a futás alatt és után. Ugyanaz a váz három állapotban:

- nem indult: V1 (23);
- fut: V10;
- kész: V12.

Belső snapshotnál napos sávval (V10·b, V12·b). A belépés ide irányít (14 · 14.4). A társ kezdőlapja: 25.

## Alfeladatok

### 26.1 Fut (V10, V10·b)

- **Fejléc** (charcoal): „A ti diagnózisotok”, kísérősor: „{Termék} · fut · {kezdés} → {publikálás}”.
- **A gyűrű helyén** a publikálás dátuma és „Belső működés · {n} mérőpont”.
- **Hol tartunk:** sáv a hetekkel (snapshotnál napokkal), alatta a lépések. Állapotuk: kész, most vagy később.
  - **Onboarding:** „értékek · folyamatok · a márkád alapja · {dátum}”. Koppintásra a lépéslista nyílik (23 · 3. kérdés).
  - **Társak** (csak Teljes, csak csapat): „{k} / {n} kitöltötte” és „Emlékeztető” gomb (lásd 2. kérdés). 3 kitöltő alatt: „Még {n} társ hangja kell, hogy lássátok a rést.”
  - **Beszélgetések a mégissel:** „{k} megtartva · következő: {dátum} {idő} · {admin}” (19.5).
  - **Amit hozzátesztek:** „{n} felvétel · {k} dokumentum · {j} elmondott helyzet”. Koppintásra a saját helyzetei nyílnak meg (12 · 12.5).
  - **A mégis olvasata:** „{olvasat kezdete} → {publikálás előtti nap}” (17.2).
  - **A riport:** „{publikálás}”.
- **Mélyítés** (szürke, nem kattintható): „Belső működés · értékek · folyamatok · területek · a márkád alapja · {publikálás}”.
- **A coral plusz** (15 · 15.5) a feltöltésre visz (12).
- A vezető számokat lát, nem válaszokat.

### 26.2 Olvasat alatt

- Ugyanaz, mint a 26.1, a „A mégis olvasata” lépés „most” állapotban.
- A coral plusz eltűnik, a lépéslista csak olvasható (17.5).
- Visszavonás után (21.5) is ez látszik, egy sorral: „A mégis javítja a diagnózisotokat. Hamarosan újra elérhető.”

### 26.3 Kész (V12, V12·b)

- **Fejléc:** „A ti diagnózisotok”, kísérősor: „{Termék} · publikálva {dátum}”.
- **A gyűrű:** egy, Belső működés (18.4).
  - Benne a %, alatta „{pont} / {max} pont”.
  - Coral vonás 80%-nál.
  - Alatta „Mit jelent a % és a pontok?”, a Bölcsességtárba visz (BT4, 32).
- **A legnagyobb rés**, charcoal kártyán, egyetlen coral elemmel: „A legnagyobb rés: {érték}. {m}-nek látjátok, {p}-t mutattok.” Az érték mélyítő lapjára visz (27).
  - Snapshotnál a töréspont mondata áll itt, ha nincs rés („A rossz hír megvárja, amíg biztos.”).
- **Mélyítés** (élő):
  - „Belső működés · értékek · folyamatok · területek”, a 27 · R1·b lapra visz;
  - „A márkád alapja · gyökér · törzs · korona · árnyék”, az R5-re, snapshotnál az S1·b-re visz.
- **Riportok:** a vezető publikált fő diagnózisai, soronként: „{Termék} · {dátum} · Megnyitom · PDF” (28).
- **Értékmérés-teaser:** „Értékmérés · a diagnózis után kapcsolható be. Az értékalapú működés nyomás alatt és hosszabb távon jobban tart.” Gomb: „Beszéljünk róla”, a konfigurált naptárlinkre, új ablakban (06).

### 26.4 Több diagnózis, több szerep

Egy ember lehet az egyik diagnózisban vezető, egy másikban társ (25.7), és lehetnek régebbi diagnózisai is.

- A kezdőlap az aktív szervezet (15 · 15.4) legfrissebb olyan fő diagnózisát mutatja, amelyben vezető.
- Ha csak társ, a társ kezdőlapja (T5) nyílik (25).
- Ha mindkettő, a vezetői kezdőlap nyílik, és az alján egy sor visz a társ-nézetre: „A {Csapat} diagnózisában társ vagy”. A T5-ön ugyanígy egy sor visz vissza.
- Ha egy e-mailből jön (pl. társ-meghívó), az a lap nyílik, ahová a link mutat (14 · 14.4).
- A régebbi riportok a „Riportok” listában és a menüben (15 · 15.3) maradnak.

## Nincs benne

- A Brand-gyűrű és a „Brand · kívülről” sor: egyeztetés alatt.
- A „Képesség és 90 nap” sor: [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- A Coach belépője: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- A riport lapjai: 27.

## Nyitott kérdések

1. **Hány kitöltőt lát a vezető?** A prototípus „Társak 4 / 6 kitöltötte” sort mutat.

   **Javaslat:** a számot mindig látja, neveket nem (V9: „névvel csak ők és a mégis”). Ez akkor is igaz, ha 3 alatt van, mert a szám nem fedi fel, ki mit válaszolt.

2. **Vezetői emlékeztető.**

   **Javaslat:** egy gomb, ami minden olyan társnak e-mailt küld, aki még nem töltötte ki a T4-et. Ugyanaz a szöveg és ugyanaz a 48 órás korlát, mint az admin emlékeztetőjénél (19.4). A vezető nem látja, kinek ment ki.

3. **„Amit hozzátesztek” számai.** A prototípus egy összesített számot mutat („7 felvétel · 5 dokumentum · 1 átirat”).

   **Javaslat:** a vezető az egész csapat számát látja név nélkül. A koppintás csak a saját helyzeteit nyitja meg (12 · 12.5: a feltöltő csak a sajátjait látja).

4. **Kell-e az Értékmérés-teaser az MVP-ben?** Ez nem funkció, hanem ajánlat.

   **Javaslat:** igen, statikus szövegként és naptárlinkkel. Olcsó, és ez az egyetlen hely, ahol a diagnózis után továbbvezetünk.
