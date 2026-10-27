# 15 · Ügyféloldal – az app kerete, menü, szervezetválasztó

## Cél

A keret, amelyben minden ügyféloldali lap fut: domain és URL-ek, fejléc, menü, szervezetválasztó, reszponzív elrendezés és PWA. A menüpontok mögötti lapokat a saját jegyeik adják. Ez a jegy a helyüket és a szabályaikat adja meg.

## Alfeladatok

### 15.1 Domain és URL-ek

Az app a **app.megis.hu** aldomainen fut. A megis.hu weboldal külön marad, és onnan linkelünk ide.

| Útvonal | Mi van ott |
|---|---|
| `/` és `/ertektukor/…` | A nyilvános Értéktükör (06). |
| `/belepes`, `/meghivo/…` | Belépés és regisztráció (14). |
| `/aszf`, `/adatkezelesi-tajekoztato`, `/cookie-tajekoztato` | A jogi dokumentumok (05). |
| `/admin/…` | Az admin felület (01). |
| `/{slug}/…` | Egy szervezet ügyféloldali lapjai (02). |

- Az alap URL konfigurálható.
- A foglalt útvonalak nem lehetnek szervezet-slugok (02).
- A sitemapbe csak a nyilvános oldalak kerülnek.

### 15.2 Fejléc

A prototípus fejléce minden lapon ugyanaz:

- vissza nyíl;
- a lap neve;
- lépésszámláló vagy kísérősor (pl. „3 / 8”, „9 / 9 megválaszolva”);
- avatár (monogram), amely a menüt nyitja.

Belépés előtt (ET, B1) nincs avatár.

A fejléc színét a lap típusa adja:

| Szín | Lapok |
|---|---|
| Charcoal | kezdőlap |
| Zöld | Belső működés |
| Világossárga | a válasz, összefoglaló, a márkád alapja, képesség, Bridge |
| Cream | bevitel és tárak |
| Coral | Coach |
| Fehér | Profil |

A szín a fejléc-komponens paramétere, a lapok jegyei állítják be.

A 14.6 szerinti „nevében dolgozol” sáv a fejléc fölött áll, minden lapon.

### 15.3 Menü (M1)

Egy szint, lenyitható csoportokkal.

| Kinek | Menüpontok |
|---|---|
| Vezető | Riportok · Fejezetek · Tárak (Értéktár, Folyamatok) · Bölcsességtár |
| Társ | Saját nézet · Értéktár |

- A menü alján: az ügyfél-sor (15.4), a Profil és a Kijelentkezés.
- Nem menüpont: Ma, Mérés, Idővonal, Heti összefoglaló, Felvétel.
- Egy menüpont csak akkor látszik, ha:
  - a funkciója elkészült;
  - a felhasználónak van joga hozzá (címkék, 02);
  - van mögötte tartalom. A Riportok például csak publikálás után jelenik meg.
- A menü tartalma ezért konfigurációból áll össze, és a lapok jegyei bővítik.

### 15.4 Szervezetválasztó

- Az ügyfél-sor a menü alján: a szervezet neve, alatta az aktuális diagnózis:
  - amíg fut (a meghívástól az olvasatig), a termék és az időablak: „Teljes · 03.02. – 04.18.”;
  - utána a legutóbb publikált riport: „Teljes · 04.18.”. A társnál ez a sor üres, mert a riportot nem látja.
- Ha a felhasználónak több aktív szervezete van, a sor lenyílik, és a listából vált. Váltás után a választott szervezetnél a 14.4 szerinti lapra érkezik.
- Az inaktív szervezet nem jelenik meg a listában.
- A rendszer felhasználónként megjegyzi a legutóbb használt szervezetet. Belépéskor ez nyílik meg.
- Ha a felhasználó egy másik szervezetének linkjét nyitja meg, a rendszer automatikusan oda vált. Ha nem tagja a szervezetnek, „Nincs jogosultságod” oldalt kap.
- Ahol egy funkció az „aktív szervezetre” hivatkozik (pl. 06 · 6.2), ezt a választást érti alatta.

### 15.5 A coral plusz

- A felvétel gombja: coral harmad-kör a lap alján, megnyitva ×-re vált.
- Csak futó diagnózisnál jelenik meg, és csak a kezdőlapokon (V10, T5).
- A tartalmát a 12-es jegy adja (V11).

### 15.6 Reszponzív elrendezés

- Mobil-első, a prototípus telefonos lapjai szerint.
- Teljes reszponzív asztali nézet is kell:
  - a menü oldalt állandóan látszik;
  - a tartalom szélesebb;
  - ahol értelme van, két hasábra bomlik (pl. riport-fejezet és mélyítés egymás mellett).
- Asztali terv nincs. A fejlesztő javasol:
  - töréspontokat (javaslat: mobil 768 px alatt, tablet 768 és 1199 px között, asztali 1200 px-től);
  - négy kulcslap asztali változatát: kezdőlap, egy kitöltési lépés, egy riport-fejezet, Profil.
  - A mégis jóváhagyja ezeket, és a többi lap ezek mintájára készül.

### 15.7 PWA

- Manifest „mégis” névvel, a design system ikonjával és színeivel. Teljes képernyős indítás, kezdőképernyőre tehető.
- Offline működés nincs. Kapcsolat nélkül egy egyszerű lap jelenik meg: „Most nincs internet. Ha visszajött, folytathatod.”
- Push értesítés nincs.

### 15.8 Közös szabályok

- A design system tokenjei és komponensei (`_ds`), Geologica betű, informális „te” megszólítás.
- Minden szöveg nyelvi fájlban van, így később lefordítható (NFR).
- Budapesti idő.
- Automatikus mentésnél rövid „Mentve” visszajelzés jelenik meg. Ha a mentés nem sikerül, ez: „Nem sikerült menteni. Próbáld újra.”
- Hibalapok a brand szerint: 404, „Nincs jogosultságod” (403), „Lejárt a munkamenet” (419), 500.
- A nyilvános oldalakon egyedi title, meta description, canonical és Open Graph tag van (NFR).
- Akadálymentesség az NFR szerint: minden lapon WCAG 2.2 A, a kritikus lapokon (belépés, kitöltés, riport) AA.

## Nincs benne

- A menüpontok mögötti lapok: a saját jegyeik.
- Képességtár, képesség-fejezet, Bridge to Purpose: [NM-15](../backlog_nem_MVP/NM-15-kepesseg-es-bridge.md).
- Coach: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- Offline működés, push értesítés, natív app.
- Több diagnózis közötti választás egy szervezeten belül: 27 · 27.12.
