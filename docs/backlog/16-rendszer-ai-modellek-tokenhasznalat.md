# 16 · Rendszer – AI modellek, promptok, tokenhasználat

## Cél

Minden AI-hívás egy közös rétegen megy át. Ez a réteg:

- feladatonként a konfigurációból választ modellt;
- verziózott promptot használ;
- minden eredményhez rögzíti a modellt és a promptverziót;
- méri a tokeneket és a költséget (NFR 11).

A Rendszer menüben (01 · 1.4) a szuperadmin látja a konfigurációt és a használatot.

## Alfeladatok

### 16.1 Közös AI-réteg

- Az adatok az EU-n belül maradnak. Az AI-szolgáltatót is EU-s régióval vagy EU-s adatrezidenciával kötjük be.
- Három szolgáltatót támogat: Anthropic (Claude), OpenAI és Google (Gemini). A funkciók nem közvetlenül hívják a szolgáltatót, hanem egy **feladatot** a rétegen keresztül.
- **Feladatok az MVP-ben:**
  - Értéktükör: szabad detektálás, illesztés, pontozás (06 · 6.4);
  - a naptár-jelöltek elnevezése (09 · 9.3);
  - beszélgetés-kivonat (10 · 10.2);
  - a folyamat-alkalom elemzése (13 · 13.2);
  - átirat beszélő-felismeréssel (06, 12). Ennek a szolgáltatóját a 12.7 PoC választja ki;
  - a fő diagnózis értékeinek pontozása a diagnózis összes anyagából, az Értéktükröket is beleértve, az állítások és az olvasat szövegeinek vázlata (18 · 18.3–18.4, 20 · 20.4).
- Új feladatot a későbbi jegyek vesznek fel (pl. Coach, a területek és a fa pontozása: [NM-17](../backlog_nem_MVP/NM-17-ai-pontozas-teruletek-fa.md)).
- **Konfiguráció:** `config/ai.php` és `.env`. Feladatonként: szolgáltató, modell, maximális token, hőmérséklet, időkorlát. Az API-kulcsok csak `.env`-ben vannak.
  - Modellt cserélni csak konfigurációval lehet, ehhez deploy kell. Az adminban nincs modellválasztó.
- **Futás:**
  - Az AI-hívások queue jobként futnak, időkorláttal és újrapróbálással (3 próba, növekvő várakozással).
  - Ha végleg nem sikerül, a funkció a saját hibaállapotát mutatja (pl. 06: „Nem tudtuk feldolgozni.”).

### 16.2 Promptok

- A promptok a kódtárban vannak, feladatonként és verziónként külön fájlban (pl. `resources/prompts/ertektukor-detektalas/v3.md`).
- Új verzió mindig új fájl. A régi megmarad, így a régi eredmények promptja is visszakereshető.
- Az aktív verziót a konfiguráció adja meg.
- A promptot fejlesztő módosítja, kódreview után.

### 16.3 Az eredmény kötése (NFR 11)

- Minden AI-hívásról egy sor rögzül. A sor tartalma:
  - időpont, feladat, szolgáltató;
  - a modell pontos azonosítója, ahogy az API visszaadja;
  - a promptverzió;
  - állapot (sikeres vagy sikertelen) és időtartam.
- Minden AI-eredmény erre a sorra hivatkozik (06 nyers detektálás, 09, 10, 13). Így minden eredménynél látszik, melyik modell és melyik prompt készítette.
- A kérés és a válasz teljes szövegét a napló nem tárolja, mert személyes adat lehet benne. Az eredményt a funkció maga tárolja.

### 16.4 Token és költség

- Hívásonként rögzítjük:
  - a bemeneti és a kimeneti tokeneket (és a cache-tokeneket, ha vannak);
  - átírásnál a hang hosszát percben;
  - a szervezetet, ha van. A nyilvános Értéktükör külön sor: „Nyilvános Értéktükör”;
  - a költséget USD-ben.
- **Egységárak:** a konfigurációban, modellenként (bemenet és kimenet 1 millió tokenre, hang percenként).
- A költség a hívás pillanatában számolódik ki, és mentődik. Ha később változik az ár, a régi sorok nem változnak.

### 16.5 Rendszer → AI (admin, csak szuperadmin)

**AI használat**

- Időszakválasztó: ma · 7 nap · 30 nap · ez a hónap · egyedi.
- Fent az összesítés: hívások száma, token, költség (USD).
- Bontások táblázatban: modellenként, feladatonként, szervezetenként, napi bontásban.
- Hívások listája (az NFR listaszabályai szerint):
  - Oszlopok: időpont, feladat, modell, szervezet, token, költség, állapot, időtartam.
  - A hívás megnyitható: promptverzió, hibaüzenet, és melyik eredményhez tartozik.
- CSV export a szűrt listáról.

**AI konfiguráció**, csak olvasható táblázat. Soronként egy feladat:

- szolgáltató, modell, aktív promptverzió;
- egységár;
- a feladat utolsó sikeres hívásának időpontja.

**Promptok**, csak olvasható:

- feladatonként a verziók listája, az aktív megjelölve;
- a prompt szövege megnyitható.

## Nincs benne

- Modell- és promptcsere az adminból.
- Havi költségkeret és riasztás.
- Promptok A/B tesztje.
- A rendszer állapota (health): [docs/uzemeltetes.md](../docs/uzemeltetes.md).

## Nyitott kérdések

1. Melyik feladatra melyik modell induljon? Javaslat: a fejlesztő a 12.7 PoC-val együtt egy próbakészleten összeveti őket, és a mégis jóváhagyja.
