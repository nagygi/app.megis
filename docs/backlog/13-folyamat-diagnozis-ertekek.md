# 13 · Folyamat-diagnózis – értékek az alkalmakból

## Cél

Az alkalmak anyagaiból (12) a 06-os motor kiolvassa, mennyire hordozza a folyamat a szervezet értékeit. Folyamatonként egy **Folyamat szintű diagnózis** áll össze (04), ez adja a riport folyamat-mérőpontjait (R3, R3·b). Ez a jegy köti be a motor illesztését a szervezet értékszettjére (08) is.

## Alfeladatok

### 13.1 Folyamat szint a Diagnózisokban (04)

- **Új szint:** Szervezet / Csapat / Felhasználói / **Folyamat**. Folyamat szintnél a szervezet egy folyamatát kell választani (11).
- **Új diagnózis típus** a kezdő adatok között: **Folyamat**.
- **Sorok:** a szervezet értékei, a diagnózisban rögzített verzióval (08 · 8.4). *A márkád alapja* blokk ennél a szintnél nincs.
- **A folyamat pontja** (PHI 1–6, vagy „–”, ha nincs nyoma) a diagnózis fejlécében áll. Ez a mérőpont.
- **A diagnózis adatai:**
  - Készítő: Automatikus.
  - Státusz: Jóváhagyásra vár.
  - Név: „{Folyamat} · {időszak}”.
  - Időszak: az első és az utolsó feldolgozott alkalom dátuma.
- Folyamatonként egy Folyamat szintű diagnózis készül, a fő diagnózis részdiagnózisaként (17 · 17.4).
- Folyamat szintű diagnózis kézzel is felvehető (pl. egy workshop alapján), a 04 szabályai szerint.

### 13.2 Alkalmanként: a motor (06 · 6.4)

- **Bemenet:**
  - az alkalom összes anyaga együtt: átirat, leirat, dokumentum, elmondott helyzet;
  - a folyamat neve és ritmusa;
  - a szervezet értékei: definíció, az 1 és a 6, példák.
- **A · szabad detektálás:** változatlan.
- **B · illesztés:** a szervezet értékeire is illeszt, mellettük az Értéktárra. Az illesztés itt is csak definíció és példák alapján történik, névegyezés alapján soha.
- **C · pontozás:** a szervezet minden értékére 1–6 vagy „nincs adat”, idézet-hivatkozással.
- Egy alkalom értékenként egy pontot ad, akárhány anyag tartozik hozzá.
- A nem illeszkedő értékek és a nyers detektálás a 06 szerint kezelendők, a folyamatra és az alkalomra hivatkozva.
- **Besorolatlan és *Egyéb* anyagok:** a besorolatlan („Egyikhez sem”) és az *Egyéb* anyagokat is feldolgozzuk, de Folyamat szintű diagnózisba nem kerülnek.
  - A nyers detektálásuk megmarad.
  - Az olvasatban (20) nyomként használhatók.

### 13.3 Összesítés

- **Értéksor mérőszáma:** az alkalmak pontjainak számtani átlaga, egy tizedesre kerekítve.
  - A „nincs adat” alkalmak kimaradnak.
  - Ha egyik alkalomban sem volt adat, a sor „–” („nincs nyoma”), és nem számít bele a folyamat pontjába (18 · 18.4).
  - Soronként látszik az n (hány alkalomból számoltunk) és a szórás, ahogy a 04 · 4.5-ben.
- **A folyamat pontja:** az értéksorok átlaga, egy tizedesre kerekítve.
- **Minimum:** a Teljesben legalább 2 feldolgozott alkalom kell, a Belső snapshotban 1 is elég, „Egy alkalomból” jelzéssel. Ennél kevesebbnél a diagnózis létrejön, de a pont üres („–”), és ez látszik: „Kevés alkalom (1)”.
- **Alfolyamat:** az alkalmai a szülő folyamatba számítanak. A sornál látszik, melyik alfolyamatból jött.
- **Saját megélés:** értékenként a V11·c válaszok átlaga (12.4), a „nem tudom” válaszok nélkül.
  - A sor részleteiben külön látszik a vezető és a társak átlaga, kitöltőszámmal.
  - A PHI-be nem számít, a rést adja (R3·b: „4-nek látjátok, 2-t mutattok”).
- **Eredményesség:** a diagnózis oldalán összesítve, pont nélkül: „6 alkalom · eredményes 3 · részben 2 · nem 1” (R3).
- A márkád alapja négy elemének alkalmankénti megélése az alkalomnál marad, diagnózisba nem kerül.

### 13.4 Frissítés

- Amíg a diagnózis nincs publikálva, minden feldolgozott alkalom után újraszámol.
- A kézzel átírt sort és pontot nem írja felül. Ha az automatikus érték eltér, mellette jelzés látszik.
- Publikálás után az új alkalmak nem módosítják a diagnózist, de látszik rajta: „2 új alkalom a publikálás óta”.
- Ha a diagnózist visszavonják Draftba (04), újra frissül.

### 13.5 Admin: a diagnózis oldala

- Az értéksorok és a folyamat pontja átírható, kötelező indoklással. Rögzítjük, ki és mikor írta át, és mi volt az automatikus érték.
- **Az alkalmak listája:** dátum, anyagok, alkalmankénti pontok, eredményesség, link az átiratra. Soronként az idézetek is látszanak.
- **Az összesítés leírása**, ahogy a 04 · 4.5-ben, a diagnózissal együtt mentve:
  - forrás: a folyamat, az időszak és a felhasznált alkalmak;
  - a kimaradt alkalmak és az okuk (feldolgozási hiba, nincs anyag);
  - a számítás: számtani átlag, egyenlő súly, legalább 2 alkalom;
  - ki és mikor írt át kézzel.

## Nincs benne

- Képességmérés: [NM-12](../backlog_nem_MVP/NM-12-kepessegmeres.md).
- Beszélőnkénti felhasználói diagnózis a leiratból: [NM-11](../backlog_nem_MVP/NM-11-beszelok-hozzarendelese.md).
- Az olvasat (A3) és a publikálás: 20, 21.
- A riport folyamat-fejezete (R3, R3·b): 27.
- Szervezeti idő, értékkép: [NM-13](../backlog_nem_MVP/NM-13-folyamatok-ertekmeresben.md).
