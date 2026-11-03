# Változásnapló

A legfrissebb verzió van felül. Formátum: `## {verzió} · {commit-hash} · {commit dátuma}`.

## 1.64 · – · 2026-11-03

Admin, szervezet *Folyamatok* füle: az *Új folyamat* saját oldalra került, morzsával.

- **Eddig (1.60):** az *Új folyamat* panelként nyílt a cím alatt. Alatta ott maradt a lista, a Naptár és a javaslat.
- **Most:**
  - **Saját oldal a fül alatt:** a morzsa „Folyamatok › Új folyamat”, a Folyamatok fül világít. Az oldalon csak az űrlap van:
    - név;
    - ritmus;
    - szülő folyamat;
    - leírás.
  - **Mentés után** vissza a listára, az üzenettel: „{név} felkerült a listára.” A *Mégse* is a listára visz.
  - **A két betöltő** (naptár, szöveg) továbbra is panelként nyílik a lista fölött.

## 1.63 · 74471fd · 2026-11-03

Admin, szervezet *Csapatok* füle: a csapat űrlapja saját oldalra került, és a tagok felvétele keresővel megy (02 · 2.4).

- **Eddig:**
  - Az *Új csapat* és a szerkesztés a lista fölött nyitotta az űrlapot, alatta a csapatlistával. Morzsa nem volt.
  - A felhasználók jelölőnégyzetes listában álltak, mellettük egy második jelölőnégyzet a csapatvezetőnek.
  - A súgó azt írta: „Új felhasználót a csapat sorából hívhatsz meg.”
- **Most:**
  - **Saját oldal:** `/szervezetek/{szervezet}/csapatok/uj` és `…/csapatok/{csapat}/szerkesztes`. A fejlécben a *Csapatok* fül világít. A cím fölött a morzsa: „Csapatok › Él csapat”, illetve „Csapatok › Új csapat”. A lista nem látszik.
  - **A csapat tagjai** keretes listában állnak: név, e-mail, az inaktívnál jelvény, a *csapat vezető* kapcsoló és egy × az eltávolításhoz.
  - **Felhasználó hozzáadása:**
    - név vagy e-mail alapján keres a szervezet nem inaktív felhasználói között, a találatok a mező alatt nyílnak;
    - kattintásra vagy Enterre veszi fel az első találatot; a ↓ és ↑ a találatok között lép, az Esc bezárja.
  - **Mentés és Mégse** után vissza a Csapatok listára. A csapat neve és a sor *Szerkesztés* menüpontja az új oldalra visz.
  - A „csapat sorából hívhatsz meg” mondat kikerült a súgóból.

Admin, szervezet: „A márkád alapja” saját fület kapott, közvetlenül az *Értékek* után (08 · 8.5).

- **Eddig:**
  - Az *Értékek* fülön az értékek alatt állt a négy doboz (gyökér · törzs · korona · árnyék), egy közös mentés gombbal.
  - Értéket csak akkor lehetett menteni, ha a márka három mezője is ki volt töltve.
- **Most:**
  - **A fülsor:** *Adatok · Felhasználók · Csapatok · Értékek · **A márkád alapja** · Folyamatok · Diagnózisok*.
  - **Fülenként ment:**
    - az *Értékek* fül csak az értékeket validálja és menti, így márka nélkül is menthető;
    - *A márkád alapja* fül a három mezőt (mind kötelező), az értékekhez nem nyúl.
  - **A gyökér** a mentett értékeket sorolja fel, alatta link visz az *Értékek* fülre.
  - **Verziók:**
    - a két fül ugyanazt az értékszettet menti, ezért mindkettőn ugyanaz a verziólista látszik;
    - egy régi verzió csak olvasható nézetben nyílik meg, és mindig az adott fül részét mutatja.

## 1.61 · 57dece0 · 2026-11-03

„Belépek a nevében” (14 · 14.6): a coral sávban a címkék és a csapatok pillékként jelennek meg, így a sáv kisebb.

- **Eddig (1.56):** „Címkék: szervezet vezetője” és „Csapatok: Él csapat” szöveggel. Egy keskenyebb ablakban a sáv két sorba tört.
- **Most:**
  - **Címkék:** kitöltött pillék, pl. *szervezet vezetője*.
  - **Csapatok:** körvonalas pillék, ábécérendben. Ha a felhasználó vezeti a csapatot, a pillben ott áll: *Él csapat · **vezető***.
  - A „Címke” és „Csapat” szót a képernyőolvasó felolvassa, egérrel tooltipként látszik.
  - A sáv alacsonyabb, a pillék kisebb betűsek, a „Címkék:”/„Csapatok:” felirat nem foglal helyet. Ha nem fér el, továbbra is több sorba törik.

## 1.60 · 2859bc9 · 2026-11-03

Admin, szervezet *Folyamatok* füle: alapból csak a folyamatlista látszik. A felvétel és a betöltés gombra nyílik.

- **Eddig:**
  - Minden egyszerre látszott, a lista fölött a teljes naptárbetöltő kártya:
    - a tag választó és a jelölőnégyzet;
    - a link és a fájl egymás mellett.
  - A lista alatt állt a „Folyamatok szövegből” kártya, az Új folyamat űrlap és a javaslat.
- **Most:**
  - **A fejlécben két gomb van:**
    - *Új folyamat*;
    - *Betöltés*, ez egy lenyíló menü: *Naptár betöltése* és *Betöltés szövegből*.
  - **A kiválasztott űrlap panelként nyílik a cím alatt.** Egyszerre egy látszik, a × bezárja. A beírt szöveg bezáráskor megmarad, egy diktált szöveg is.
  - **A naptár betöltése** három fület kapott:
    - *iCal link*;
    - *ICS fájl*;
    - *Google vagy Outlook fiók*, ez még inaktív, „hamarosan” jelöléssel.
    A tag választó és a „… kérte” jelölőnégyzet mindkét aktív fülre vonatkozik.
  - **Bezárul a panel:**
    - sikeres naptárbetöltés után;
    - a szövegből felvett folyamatok hozzáadása után.
  - **Nyitva marad az Új folyamat**, hogy többet is fel lehessen venni egymás után. Minden felvételről üzenet szól.
  - **Az összekötött naptárak sorai** a lista alá kerültek, a javaslat fölé. A megszakadt naptár így betöltés nélkül is látszik.

## 1.59 · 4a320c4 · 2026-11-03

Képességek lépés (NM-12): egy szabad szöveg a kártyák helyett; a képességeket ebből a Képességtár alapján olvassuk ki.

- **Eddig (1.32):**
  - A lépésen értékkártyák voltak, a Megélés alapján javasolva: Teljesben 3, Snapshotban és társnál 1. Minden kártya egy értékhez kért egy helyzetet.
  - Az utolsó kártyát nem lehetett törölni: kiürült listánál a javasolt kártyák újra létrejöttek.
- **Most:**
  - **Egy szöveg kitöltőnként:** leírva, elmondva vagy feltöltve, legfeljebb 10 000 karakter. A kérdés: „Mi történt nálatok mostanában? Mesélj el egy-két friss helyzetet…”, alatta témajavaslatok (döntés, rutin, vita, terhelés, ügyfél).
  - **Nem vezetjük a választ:** a lépés nem nevez meg képességet, és az eredményt sem mutatja vissza (Részletes leírás 8.1, 8.8). A mérés a háttérben fut a teljes Képességtár ellen; az admin a diagnózis Képességek fülén látja, a kitöltő a jóváhagyott eredményt publikálás után.
  - **Limit:** 10 000 karakterig a diktálás nyers átirat marad. A limit fölött az AI tömörít, és a tömörített szöveg már feldolgozott forrás.
  - **Tovább:** legalább 40 karakter kell, vagy „Kihagyom”. A megváltozott szöveget újraméri, a kiürítettet a nyomaival együtt visszavonja.
  - **V10:** „Képességek · 2 kitöltő mesélt · ebből 1 társ”.
  - **Kikerült:** az értékkártyák, a javaslatok, a „Másik értékről mesélnék” és a „Még egy helyzet”, így a törlési hiba is.
- **Adatbázis:**
  - A `capability_stories` táblán felhasználónként egy sor (egyedi index), az érték-oszlopok törlődtek.
  - A régi kártyák tesztadatok voltak: az anyagaikkal együtt törlődnek, és az érintett diagnózisok képesség-sorai újraszámolódnak.

## 1.58 · 6e2a49e · 2026-11-03

Admin: minden oldal kitölti a szélességet; nagyon nagy monitoron az oldal legfeljebb 1920 px széles.

- **Eddig (1.25 óta):**
  - Minden oldal maga döntötte el a szélességét:
    - a listák kitöltöttek;
    - az űrlapok 768 px-esek voltak, pl. a szervezet *Adatok* füle;
    - az adatlapok 1024 px-esek;
    - a fődiagnózis fülei 1280 px-esek. A fülsor viszont kitöltött, így a tartalom alatta félbemaradt.
  - Széles monitoron emiatt fülről fülre ugrott a szélesség.
  - Nagy monitoron a listák a képernyő széléig nyúltak.
- **Most:**
  - **Minden oldal kitölt:** a táblázatok, a kártyák és a fődiagnózis fülei egy vonalban állnak a fejléccel.
  - **Űrlapok:** a fehér kártya kitölt, a mezők megtartják a szélességüket: 768 px, az érték- és a képesség-űrlap szakaszaiban 1024 px. A gombok így a mezők mellett maradnak, pl. a Slug mellett a „Szerkesztem”, és a mentés.
  - **A hosszabb szövegek** (leírások, bevezetők) továbbra is 768 px-ig tartanak, hogy olvashatók maradjanak.
  - **Nagyon nagy monitoron** a menü és a tartalom együtt legfeljebb 1920 px széles, középen, két oldalt sötétebb szürke sávval. 1920 px-es monitorig ebből nem látszik semmi.
  - Az ügyfélfelület nem változott.

## 1.57 · e6064d0 · 2026-11-03

Admin felhasználólisták: színes állapotjelzés, az Inaktív kiemelve; a címkék chipként.

- **Eddig:**
  - A Felvéve, az Aktív és az Inaktív jelzés szinte egyformán szürke volt. Az Inaktív volt a leghalványabb.
  - A Címkék oszlopban vesszővel elválasztott sima szöveg állt, a csapatvezetés „· csapat vezető” szövegként.
- **Most:**
  - **Állapot:** minden állapotjelzés előtt színes pötty áll.
    - Aktív: zöld.
    - Felvéve, Meghívva és Meghívás alatt: sárga.
    - Meghívó lejárt és Zárolva: korall.
    - **Inaktív:** sötét jelzés. A szervezet Felhasználók listáján az inaktív felhasználó sora is szürkébb.
    - Ugyanígy néz ki a szervezet Felhasználók fülén, a Felhasználók listán, a felhasználó adatlapján és az Admin felhasználók listán.
  - **Címkék:** minden címke külön keretes jelölés. A „szervezet vezetője” sötétebb kerettel és félkövéren áll.
  - **Csapatvezető:** a „csapat vezető” is ilyen jelölés a csapat neve mellett. Így látszik a szervezet Felhasználók fülén és a felhasználó adatlapján.

## 1.56 · 708792b · 2026-11-03

„Belépek a nevében” (14 · 14.6): a coral sáv a felhasználó címkéit és csapatait is mutatja.

- **Eddig:** a sávban csak „{Név} nevében dolgozol · Kilépés” állt. Hogy milyen szerepben van ott a felhasználó, az adminból kellett kikeresni.
- **Most:**
  - **Címkék:** a név után „Címkék: szervezet vezetője, munkatárs”, a felhasználó címkéi az aktuális szervezetben.
  - **Csapatok:** „Csapatok: Sales, Value Core Team (csapat vezető)”, ábécérendben. Ahol a felhasználó csapatvezető, ott ez is látszik.
  - Mindkettő az aktuális szervezetre vonatkozik. Ha nincs címke vagy csapat, az adott rész kimarad.
  - Telefonon a sáv több sorba törik.

## 1.55 · 286b88d · 2026-11-03

Admin: „Szervezetek › szervezet” a fejlécben, a morzsa a fültől az aktuális oldalig; az A1 vezetői előnézete megszűnt.

- **Eddig (1.53):**
  - A zöld fejlécben csak a szervezet neve állt.
  - A cím fölötti morzsa a *Szervezetek › szervezet* szinttől indult, az aktuális oldal nélkül.
  - A Diagnózis indítása (A1) jobb oldalán ott volt a „Amit a vezető lát az első belépéskor” előnézet.
- **Most:**
  - **Fejléc:** *Szervezetek › szervezet*. A *Szervezetek* link a szervezetlistára visz, a szűrőkkel együtt, ha onnan jöttél.
  - **Morzsa a cím fölött:**
    - Szervezeten belül a fültől indul, és az aktuális oldallal zárul, pl. *Diagnózisok › Diagnózis indítása* vagy *Diagnózisok › fődiagnózis › Részdiagnózisok › részdiagnózis*.
    - Az utolsó elem nem link.
    - A szervezet saját fülein nincs morzsa, csak „Vissza”, ha máshonnan nyitottad meg.
    - Mobilon továbbra is egy lépés látszik felfelé.
  - **A1:** a vezetői előnézet kikerült. Hogy mit lát a vezető, a „Belépek a nevében” funkcióval nézhető meg. Az űrlap egy oszlopban áll.
  - **Meghívás oldal:** a lapcíme „Meghívás”, illetve „Felhasználó szerkesztése” lett, a szervezet neve helyett.

## 1.54 · added1e · 2026-11-03

Diagnózis kitöltése (V1·b, 23 · 23.3, 24 · 24.2): a Saját értékek lépés a mentett listától kész, nem kell hozzá a „Tovább”.

- **Eddig:**
  - A Saját értékek csak akkor kapott pipát, ha a lépés alján megnyomtad a „Tovább” gombot, mert az rögzítette a lista verzióját.
  - Egy érték felvétele vagy törlése csak a listát mentette. Ha ezután a lépéslistából mentél tovább, a lépés nyitva maradt.
  - A „Folytatom” ezért a Saját értékekre vitt vissza, akkor is, ha a többi lépést már kitöltötted.
- **Most:**
  - **Kész:** a lépés akkor kész, ha a mentett lista teljes: legalább 1 érték, névvel és jelentéssel; magánszemélynél 3–5 érték, mindkét véggel. Ha kiveszed az utolsó értéket, a lépés újra nyitott.
  - **„Folytatom”:** egy teljes lista után a következő hiányzó lépésre visz.
  - **„Tovább”:** továbbra is verziót rögzít a listáról.
  - **Magánszemély:** a saját értékei minden mentéskor a szervezet értékei is lesznek, így *A márkád alapja* a „Tovább” nélkül is a friss értékeket mutatja. A szervezet értékeinek verziója a „Tovább”-nál jön létre, mint eddig.

## 1.53 · 0264b63 · 2026-11-03

Admin: a szervezet fejléce a szervezet minden oldalán megmarad, a morzsa a fülsor alá, a cím fölé került.

- **Eddig (1.51):**
  - A szervezet neve és fülei csak a hat fülön látszottak.
  - Egy meghívásnál, egy folyamat alkalmainál, egy diagnózison vagy a Diagnózis indításán eltűntek, csak a morzsa maradt.
  - A morzsa a fejléc felső sorában állt.
- **Most:**
  - **Fejléc:** szervezeten belül minden oldalon zöld, a szervezet nevével és füleivel. Az a fül világít, amelyik alá az oldal tartozik:
    - meghívás, felhasználó szerkesztése: *Felhasználók*;
    - folyamat alkalmai, a szervezet besorolatlan anyaga: *Folyamatok*;
    - diagnózis, részdiagnózis, Diagnózis indítása: *Diagnózisok*.
  - **Morzsa:** a fülsor alatt, az oldal címe fölött áll, pl. *Szervezetek › szervezet › Diagnózisok*. A szervezet fülein is látszik: *Szervezetek › szervezet*. A „Vissza” és a szűrők megtartása nem változott.
  - **Fődiagnózis:** a neve, a szakasza, az adatai és a fülei a morzsa alatt, a tartalom elején állnak, aláhúzott fülsorral.
  - **Szervezeten kívül:** a sötét fejléc a menücsoport nevét mutatja (pl. *Értéktár*), a morzsa itt is a cím fölött áll.

## 1.52 · 6b86c2a · 2026-11-02

Diagnózis indítása (A1, 17 · 17.1–17.2): a szervezet kontextusában, a név felül és automatikusan.

- **Eddig:**
  - Az A1 szürke fejlécű, szervezet nélküli oldal volt, akkor is, ha a szervezet *Diagnózisok* füléről nyitottad meg.
  - A Név az űrlap alján, üresen állt („Ha üresen hagyod: …”), és csak mentéskor kapta meg az automatikus nevet.
- **Most:**
  - **Fejléc:** az A1 a szervezet zöld fejlécével és a *Szervezetek › szervezet › Diagnózisok* morzsával nyílik. Ha az oldalon másik szervezetet választasz, az A1 annak a kontextusában nyílik újra.
  - **Név:** a Név az űrlap első mezője. Kitölti magát a csapatból (új csapatnál a beírt névből), a termékből és a kezdés napjából, pl. „Él csapat · Teljes · 2026.11.09.”, és követi az űrlapot.
    - Átírhatod. Ha átírtad, már nem változik; az „Automatikus név” gombbal vagy a mező kiürítésével visszakapod az automatikusat.
    - Ha nem írtad át, mentéskor a ténylegesen mentett csapatból és dátumból épül.
  - **Mégse:** a szervezet *Diagnózisok* fülére visz, ha szervezettel nyitottad meg.

## 1.51 · c47706b · 2026-11-02

Admin: a fejléc mutatja, hol vagy. Kattintható morzsa, és a diagnózisnak egy fülsora van.

- **Eddig:**
  - A fejlécben a „Szervezetek / szervezet neve” sima szöveg volt.
  - Egy diagnózison belül két fülsor állt egymás alatt (a szervezeté és a diagnózisé), és a szervezet neve háromszor szerepelt.
  - A bal menüben a diagnózisoldalakon a „Diagnózisok listája” világított, a fejléc közben a szervezetet mutatta.
  - A cím fölötti „Vissza” oda vitt, ahonnan az oldalt megnyitottad, és nem látszott, mi van az oldal fölött.
- **Most:**
  - **Morzsa:** a fejléc felső sorában ott vannak az oldal fölötti oldalak, mindegyik link. Például:
    - diagnózisnál: *Szervezetek › szervezet › Diagnózisok*;
    - részdiagnózisnál: *… › Diagnózisok › fődiagnózis › Részdiagnózisok*;
    - egy érték oldalán: *Értékek*.
    A menü saját listáinak nincs morzsájuk.
  - **Vissza:** csak akkor van „‹ Vissza” a morzsa előtt, ha máshonnan nyitottad meg az oldalt (pl. a Diagnózisok listája kertjéből vagy az ügyfél-adatlapról). Ha a morzsa egyik oldaláról jöttél, a morzsa visz vissza, a lista szűrőivel és lapjával együtt.
  - **Telefonon** egy lépés látszik: a Vissza, vagy ha az nincs, a közvetlenül fölötte lévő oldal.
  - **A fődiagnózis fülei** a zöld fejléc folytatásában vannak: a név, a szakasz, a csapat, a termék, a napok, a kapcsolattartó, alatta a fülek. A szervezet fülei itt nem jelennek meg; a szervezet a morzsában van. A cím fölötti „Vissza” link és a sárga sáv megszűnt.
  - **A szervezet neve és fülei** csak a szervezet hat fülén állnak a fejlécben. A folyamat alkalmainál, a felhasználó meghívásánál és a diagnózisoknál a morzsa vezet vissza.
  - **A bal menü** a morzsát követi: a diagnózis oldalain a „Szervezetek” világít. A „Diagnózisok listája” a listán, az új diagnózis űrlapjain és a feltöltésen.
  - **Szürke fejléc:** a menüből elérhető oldalakon nem ismétli az oldal címét.
  - **Fülek:** egy gomb után (pl. Publikálom, jóváhagyás az Olvasatban) is a megnyitott fül marad kiemelve. Ez a diagnózisnál és az érték, a képesség és a minta füleinél is így van.
  - A levél-allowlist figyelmeztetése a fejléc fölé került.

## 1.50 · ae838d8 · 2026-11-02

„Belépek a nevében” a még nem regisztrált felhasználónál is (14 · 14.6): az admin előre látja, mit fog látni a felhasználó a regisztráció után.

- **Eddig:** a menüpont szürke volt („A felhasználó még nem regisztrált.”), ha a felhasználó Felvéve vagy Meghívva állapotú volt. Ez a szervezet *Felhasználók* fülén, az *Admin › Felhasználók* listán és az adatlapon is így volt.
- **Most:** ilyenkor is működik. Az ügyféloldal új lapon nyílik a coral sávval, ugyanott, ahová a felhasználó a regisztráció után érkezik. Ez a vezetett vagy társként kitöltendő diagnózis, vagy a szervezet kezdőlapja.
  - Azokat a szervezeteket látja, ahová felvették vagy meghívták. A regisztrált felhasználónál továbbra is csak az aktív tagság nyit szervezetet: náluk a Felvéve/Meghívva tagság sora szürke marad („…a meghívó elküldésével azonnal aktív lesz”).
  - Ugyanazok a szabályok, mint a regisztrált felhasználónál: a jelszó, a jogi dokumentumok, a hozzájárulások, a fióktörlés és a naptár összekötése tiltott, minden módosítás naplózva.
  - Az ÁSZF-elfogadás lapja nem állja útját: a felhasználó a regisztrációs űrlapon fogadja el.
  - A felhasználó állapota nem változik. A listán „Felvéve”/„Meghívva” marad, az utolsó belépés „még nem”, a tagsága nem aktiválódik, a meghívó linkje érvényes marad.
  - A nem regisztrált felhasználó maga továbbra sem tud belépni: az ügyféloldal csak az admin „nevében” munkájában nyílik meg neki.

## 1.49 · d69e635 · 2026-10-29

„Vissza” gomb az admin részletoldalain: oda visz vissza, ahonnan az oldalt megnyitottad.

- **Eddig:** a listából megnyitott oldalakról nem lehetett visszalépni. Ilyen volt például a fődiagnózis *Részdiagnózisok* füléről megnyitott részdiagnózis. Csak az értéktükörnek, a folyamat-alkalmaknak és a felhasználói nézetnek volt „Vissza” linkje, és mindhárom fix helyre mutatott.
- **Most:** minden admin részletoldal és űrlap kap a cím fölé egy „Vissza” linket. Ide tartoznak a diagnózisok és a fődiagnózis fülei, a szervezet fülei, az ügyfél-adatlap, az anyag, az érték, a képesség és a minta oldala, valamint az új/szerkesztő és import űrlapok.
  - A link oda visz, ahonnan az oldalt megnyitottad, a lista szűrőivel és lapjával együtt. Ha egy diagnózist az ügyfél-adatlapról nyitsz meg, oda visz vissza; ha a szűrt Diagnózisok listáról, a szűrt listára.
  - A fülek között váltva is ugyanoda mutat. Ha egy innen megnyitott oldalról jössz vissza, nem cserélődik le. Egy új elem mentése után az új oldal oda mutat, ahonnan az űrlapot megnyitottad.
  - Ha az oldalt kívülről nyitod meg (könyvjelző, begépelt cím, levélben kapott link), az oldal saját listájára visz. Részdiagnózisnál ez a fődiagnózis *Részdiagnózisok* füle, önálló diagnózisnál a szervezet *Diagnózisok* füle.
  - A menüből elérhető listák, a Beérkező, a Profil, a Beállítások és a Rendszer oldalai nem kapnak „Vissza” linket.
- A nem fődiagnózis *Képességmérés* lapján a külön „Vissza a diagnózishoz” gomb helyére a cím fölötti link került.

## 1.48 · 01e9840 · 2026-10-28

Vezetőcserénél a régi vezető a diagnózis társa lesz (17 · 17.2, 17.7).

- **Eddig:** a lecserélt vezetőnek csak a lezárt vezetősora maradt meg, társ-sora nem lett. Az admin *Beállítás* lapja bepipált társként mutatta, a kezdőlapja viszont ezt írta: „Jelenleg nincs olyan diagnózis, amelyben részt veszel.” A „Fut” indulásakor meghívót sem kapott volna.
- **Most:** ha a régi vezető még a csapat tagja, a csere után társ lesz. Az admin a pipával ugyanúgy kiveheti, mint bármelyik társat. A kitöltése megmarad, a saját értékei és a megélése a társ-lépéseinél is ott vannak. Ha a diagnózis már fut, azonnal megkapja a társ-meghívót.
- **Teendő a stagingen a deploy után:** a régebbi cseréket a javítás nem pótolja. Az „Él csapat · Teljes · 2026.11.02.” diagnózist (ALma Kft, a lecserélt vezető ng+alma16) egyszer el kell menteni a *Beállítás* lapon („Mentem”). Ez a deploy előtt is működik.

## 1.47 · d00b513 · 2026-10-28

Jegyek az oldalon: az `exot/onsite-comment` 1.0.0-ról 1.1.0-ra frissült (`composer.lock`; a `^1.0` feltétel maradt).

- **E-mail értesítés új jegyről:** a fejlesztő szerepkörű résztvevő a widget menüjében kapcsolhatja be magának („E-mail új jegyről”). Alapból ki van kapcsolva.
  - A levél tartalma: összefoglaló (típus, prioritás, bejelentő, oldal, leírás) és egy link, ami az oldalon megnyitja a jegyet. Kép nincs benne.
  - A saját jegyéről senki nem kap levelet.
  - CLI-ből: `php artisan onsite-comment:participants --notify-on=<e-mail>` és `--notify-off=<e-mail>`.
- **A levél a projekt mailerén megy ki** (`MAIL_*`), ezért a `MEGIS_MAIL_ALLOWLIST` rá is vonatkozik. Ha a stagingen a lista nem üres, a fejlesztő címe (vagy pl. az `@exot.hu` domain) legyen rajta, különben a levél elvész.
- **Queue:** a küldés job, a stagingen a redis worker viszi (ugyanaz, mint a Trello pusht). Helyben a queue a stagingdel közös adatbázisban van, ezért a helyi `.env`-ben `ONSITE_COMMENT_NOTIFICATIONS_QUEUE_CONNECTION=sync` kell, ugyanúgy, mint a Trellónál. A `.env.example` ezt mutatja.
- **Migráció:** a csomag saját SQLite-jában új oszlop jön (`osc_participants.notify_new_tickets`). A `php artisan migrate` után magától lefut; a `megis_dev`-hez nem nyúl.
- **Teendő a stagingen a deploy után:** `php artisan onsite-comment:doctor`. Az új „E-mail értesítés” sor mutatja, hányan kérik a levelet, melyik mailer viszi, és ki a feladó.
- Nincs új kötelező env-kulcs. Opcionális: `ONSITE_COMMENT_MAIL_MAILER`, `ONSITE_COMMENT_NOTIFICATIONS` (`false`: kikapcsolva), `ONSITE_COMMENT_NOTIFICATIONS_QUEUE_CONNECTION` és `_QUEUE`.
- **Rollback:** 1.46 (a csomag 1.0.0-ra áll vissza). Az új oszlop megmarad, az 1.0.0 nem olvassa.

## 1.46 · 383e6cc · 2026-10-28

Jegyek az oldalon: az `exot/onsite-comment` 0.9.0-ról 1.0.0-ra frissült. A `composer.json`-ban innentől `^1.0` áll, ez minden 1.x kiadást elfogad, így a csomag következő frissítéseinél nem kell átírni.

- **Képek a jegyekhez:**
  - Az új jegy formjában és a jegy panelen képet lehet feltölteni fájlválasztóval, behúzással vagy beillesztéssel (Ctrl+V). PNG vagy JPG, jegyenként legfeljebb 20, képenként 6 MB.
  - A böngésző feltöltés előtt legfeljebb 1920 px szélesre kicsinyíti a képet, és a fotók helyadatait is eltávolítja.
  - A saját képét mindenki törölheti, a fejlesztő bármelyiket.
  - A képek a Trello-kártyára és a Markdown exportba is bekerülnek.
- **Konzolhibák:** a jegyre a sikertelen kérések (`fetch`/XHR 4xx/5xx, hálózati hiba) és a be nem töltött fájlok is felkerülnek, időponttal, oldallal és ismétlésszámmal. Az új jegy formjában egy jelölővel kikapcsolható.
- **Migráció:** a csomag saját SQLite-jában új tábla jön (`osc_attachments`). A `php artisan migrate` után magától lefut; a `megis_dev`-hez nem nyúl.
- **Teendő a stagingen a deploy után:** `php artisan onsite-comment:doctor`. A „Képfeltöltés” sora jelzi, ha a PHP feltöltési korlátja kisebb 6 MB-nál. Az nginx `client_max_body_size` értéke legyen legalább `8m`; az alapértelmezett 1 MB-nál nagyobb képre 413-as hibát ad.
- Nincs új kötelező env-kulcs. Opcionális: `ONSITE_COMMENT_ATTACHMENT_MAX_KB`, `ONSITE_COMMENT_ATTACHMENT_MAX_COUNT`.
- **Rollback:** 1.45 (a csomag 0.9.0-ra áll vissza). Az új tábla és a feltöltött képek megmaradnak, a 0.9.0 nem mutatja őket.

## 1.45 · 09ebc87 · 2026-10-28

Jegyek az oldalon: az `exot/onsite-comment` 0.7.0-ról 0.9.0-ra frissült. A `composer.json`-ban `^0.9` áll, mert 0.x verziónál a `^0.7` a 0.8-at már nem engedi.

- **Új jegy űrlap:** a Linkek mező alól kikerült a „Pl. specifikáció, Figma-terv, másik oldal. Csak http(s) link.” súgósor. Mobilon a képernyőkép csak kis bélyegképként látszik, a jegyre teljes méretben kerül.
- **Trello:** minden kártyára rákerül az „OnsiteComment” címke, így a boardon szűrhető, mi jött a widgetből. A státuszlisták angol nevet kapnak: To Do, In Progress, Testing, Done, Closed.
- **Teendő a stagingen a deploy után** (ha a Trello be van állítva): `php artisan onsite-comment:trello:setup --board=<board link>`. Átnevezi a meglévő magyar listákat, és létrehozza a címkét. Amíg nem fut, a szinkron a régi nevekkel is működik.
- Nincs új migráció és új kötelező env-kulcs.
- **Rollback:** 1.44 (a csomag 0.7.0-ra áll vissza). Ha a setup már lefutott, a listák angolul maradnak; kézzel kell visszanevezni őket.

## 1.44 · 9a93b28 · 2026-10-27

Az `update.sh` nem akad el a szerveren kézzel telepített dev csomagokon (a DestinationX-es javítás átvétele).

- **Dev csomagok a vendor-ban:** egy kézi `composer install` vagy `composer update` `--no-dev` nélkül a dev csomagokat is telepíti. Ha utána a `composer.lock` nem változott, a skript csak `dump-autoload --no-dev`-et futtatott: ez a dev csomagokat kihagyja az osztálytérképből, a Laravel viszont betöltené a providerüket, így a deploy `Class "Laravel\Boost\BoostServiceProvider" not found` hibával állt meg, és az oldal karbantartó módban maradt. Most ilyenkor `composer install --no-dev` fut, amely eltávolítja őket.
- **Helyben módosult `composer.json` / `composer.lock`:** a skript a karbantartó mód előtt megáll, és kiírja, hogyan állítható vissza a gitben lévő változat.

## 1.43 · 098f142 · 2026-10-27

Jegyek az oldalon: a tesztelők és a megrendelő a stagingen az oldal egy elemét kijelölve rögzíthetnek hibát, módosítási kérést vagy kérdést (`exot/onsite-comment` 0.7, a DestinationX-ben már fut).

- **Csak local és staging:** productionben a csomag semmit nem regisztrál (route, middleware, migráció, ütemezés). A tesztekben sem kapcsol be.
- **Bekapcsolás:** az admin menü alján, a verziószám alatt „Jegyek bekapcsolása az oldalon” link, az adminoknak és a szuperadminoknak. Meghívó nélkül bekapcsolja a widgetet, és visszavisz ugyanarra az oldalra. A tesztelők és a megrendelő személyes linket kapnak (`php artisan onsite-comment:invite`).
- **Adatok:** külön SQLite fájlban (`storage/onsite-comment/`), a `megis_dev`-hez nem nyúl. A jegykulcs `MEGIS-12` formájú.
- **Telepítés:** a csomag privát GitHub-repóból jön, a szerveren egy csak olvasó deploy key kell hozzá (docs/deploy.md, Telepítés). Az env-kulcsok az `.env.example`-ben vannak; helyben: docs/install.md.
- **Verzió:** az admin menü alján és a `/health`-ben a verzió 1.27-en maradt; most újra a changeloggal egyezik.

## 1.42 · b1b7888 · 2026-10-27

A szervezet értékeinél az admin és az ügyfél felülete ugyanazokkal a szabályokkal működik (08 · 8.1, 8.5).

- **Admin › Szervezet › Értékek:** a márkád alapja három szöveges eleme (Purpose, Értékajánlat, Impact) kötelező, ahogy az ügyfél V4 lapján. Eddig az admin üresen is elmenthette őket.
- **5 érték után:** a hozzáadó doboz nem tűnik el, hanem szürke lesz (választó, mező, gombok), alatta: „Legfeljebb 5 érték lehet.” Így működik az ügyfél V3 lapján és az admin *Értékek* fülén is. Ha egy értéket kivesznek, a doboz újra használható.

## 1.41 · bcb5b08 · 2026-10-27

A belépés három rése bezárult (14 · 14.2), és a Publikálás ellenőrzőlistája nem visz 403-ra (21 · 21.2).

- **„{X} meghívott…” a belépés lapján:** ha egy meglévő fiókot egy újabb szervezethez adnak, a „hozzáadtunk” levél linkje a belépésre visz. Ott a kísérősor: „{Meghívó} meghívott a(z) {Szervezet} felhasználójaként.” Ugyanígy működik a régi meghívó linkje, ha a fiók közben regisztrált. A link alá van írva és 14 napig él, a kísérősort kézzel írt címmel nem lehet előállítani.
- **„Újat kérek” a lejárt belépő linken:** a gomb Turnstile-t kér, és a kódkérés óránkénti keretébe számít (e-mail címenként és IP-nként 5). Eddig csak a 60 másodperces várakozás fogta, így óránként kb. 60 kód volt kérhető.
- **10 hibás jelszó:** a 15 perces szünet ismeretlen és jelszó nélküli címre is ugyanúgy jár (a számláló ilyenkor a cache-ben fut). A „Túl sok hibás jelszó…” üzenetből így nem derül ki, kinek van fiókja. Ismeretlen címnél is lefut a jelszó-hash, így a válaszidő sem árulkodik.
- **Publikálás › Ellenőrzőlista:** a küszöb-sor csak a kapcsolattartónál és a szuperadminnál link a Futás fülre. A többi adminnál link nélkül áll: „a Futás fülön a kapcsolattartó ({név}) vagy a szuperadmin írja felül”.

## 1.40 · 954a275 · 2026-10-27

Az *Admin › Felhasználók* listán sorműveletek vannak: belépés a nevében, inaktiválás és archiválás (33 · 33.2, 33.8). Ami valakinek nem érhető el, az szürke menüpont, ráállva az okával.

- **Sorműveletek:**
  - *Belépek a nevében:* minden adminnak. Szürke, ha a fiók inaktív, archivált, még nem regisztrált vagy admin fiók.
  - *Inaktiválom / Aktiválom:* minden adminnak, a fiók szintjén. Az inaktív fiók azonnal kijelentkezik, és nem tud belépni. A tagságai nem változnak.
  - *Archiválom:* csak szuperadminnak, adminnál szürke („Archiválni csak szuperadmin tud.”). Ez a fióktörlés (31.7), szuperadmin indítja: a fiók azonnal inaktív, a személyes adatok egy órán belül törlődnek (`megis:accounts:purge`), és ez nem vonható vissza. Piros „Archiválom” gombbal kér megerősítést.
  - Admin fióknál (admin, aki egy szervezetnek is tagja) mindhárom szürke: az *Admin felhasználók* között kezelhető.
  - A szerver is ellenőrzi a jogosultságot (`UserPolicy`: `deactivateClient`, `activateClient`, `archive`).
- **Adatlap:** a fejlécben ugyanez a *Műveletek* menü az inaktiválással és az archiválással. A „Belépek a nevében” gomb marad.
- **Állapot:** új „Archivált” állapot és szűrő. Ide tartozik az is, aki maga kérte a törlését. Archivált fiókot nem lehet aktiválni. Az „Inaktív” szűrő az archiváltakat nem mutatja.
- **Napló:** új bejegyzés a „Fiók inaktiválva” és a „Fiók aktiválva”, az adminnal, aki tette. Archiválásnál a „Fióktörlés kérve” sorban a szuperadmin áll.
- **E-mail:** archiváláskor a törlésről szóló levél azt írja, hogy a mégis adminisztrátora törölte a fiókot. Nem azt, hogy „Ha nem te kérted…”.

## 1.39 · 718c419 · 2026-10-27

Az Értéktár a Képességtárhoz hasonlóan felületről is betölthető: *Értéktár → Érték Import* (03 · 3.6). A betöltés csak szuperadminnal működik.

- **Érték Import:**
  - A mégis értéktáblázatát lehet feltölteni, xlsx-ben vagy csv-ben. Ugyanaz a betöltés fut, mint a `megis:values:import` parancsnál.
  - Az előnézet megmutatja, hány új, változott és változatlan érték van, értékenként a változott mezőkkel. Azt is mutatja, melyik érték lesz átnézett, melyik kategória új vagy frissül, és melyik oszlop nem jön be. Utána jön a „Betöltöm”.
  - Ha egy sor hibás, semmi nem kerül be: az előnézet felsorolja a hibás sorokat, és nincs „Betöltöm” gomb.
  - A verzió megjegyzésében a feltöltött fájl neve áll.
- **Jogosultság:** betölteni csak szuperadmin tud. Az admin is látja a menüpontot és az oldalt, de nála a fájlválasztó és a gomb szürke, „A betöltéshez szuperadmin jog kell.” felirattal, és a szerver is elutasítja a kérést.
- **Mintafájl:** az oldalról letölthető a `docs/ertektar-import-minta.xlsx` (három példaérték, a második munkalapon az oszlopok leírása). Minden admin letöltheti.
- **Korábbi betöltések:** ki, mikor, melyik fájlból, mennyi új, változott és változatlan érték lett, és hány új kategória. A parancssori betöltés is ide kerül, „rendszer” névvel.
- Az Értékek listán az „Érték Import” gomb a „+ Új érték” mellett áll.
- **Adatbázis:** `value_imports`.

## 1.38 · 168d4cf · 2026-10-27

Az ingyenes Értéktükör korlátai (06 · ET2): e-mail címenként napi 10 tükör, a beszéd legfeljebb 10 perc, a szöveg ehhez igazodik.

- **Napi limit:** a publikus Értéktükörből e-mail címenként naponta 10 tükör készülhet, éjfélkor nullázódik (`megis.mirror.daily_per_email`). Csak az elindult tükör számít. Felvételnél a feltöltés előtt ellenőrizzük, így a hosszú felvétel nem megy fel hiába. Az IP-címenkénti óránkénti 5 marad. A saját menüs tükröt nem korlátozza.
- **Felvétel:** a 10 perces korlát eddig is megvolt, de háttérbe tett fülön a számláló lelassult, és a felvétel túlfuthatott. Most az idő az órából számolódik, a felvétel így háttérben is 10 percnél áll meg (az „Egy helyzet” felvevőjében is).
- **Szöveg:** a leírt és a feltöltött fájlból kinyert szöveg legfeljebb 10 000 karakter, ami nagyjából 10 perc beszéd (`megis.mirror.text_max_characters`; eddig 20 000 volt, a fájlnak csak mérete volt). A fájl súgója kiírja a korlátot.

## 1.37 · 1bd9315 · 2026-10-27

Az ügyfél adatlapja (33) teljes: linkek a diagnózisokból, a Beérkezőből és a Futásról, szűrő, lapozott és olvasható napló, amely a szervezetével él. A menüben a futó diagnózis is látszik (15.4).

- **Menü (15.4):** a szervezet neve alatt futó diagnózisnál a termék és az időablak áll („Teljes · 03.02. – 04.18.”), utána a legutóbb publikált riport, ahogy eddig.
- **Linkek az adatlapra (33.1):** a felhasználó neve az adatlapjára visz. Ahol megjelenik:
  - a Futás lapon: a vezető, a társak, a naptár és a beszélgetések felhasználója;
  - a diagnózis adatlapján: a vezető, a felhasználói szintű diagnózis alanya és az anyag feltöltője;
  - a Diagnózisok listáján: a felhasználói szintű sor;
  - a Beérkezőben és az *Értékmérések › Beérkező*ben: a sor szövege alatt az a felhasználó, akiről a sor szól.
- **Adatlap:**
  - Szuperadmin nevét csak szuperadmin írhatja át, a „Szerkesztem” gomb a többieknek nem látszik.
  - Új e-mail címnél a „nem kézbesíthető” jelzés törlődik.
  - Az Értéktükör foglalásánál az időpont is látszik („időpontot foglalna · 2026.10.20. 14:05”).
- **Napló (33.6):**
  - 50 soronként lapoz, a legfrissebb elöl.
  - Minden sor mutatja a szervezetet. A részletek: a hozzájárulás fajtája és diagnózisa, a felvétel napja, a naptár forrása, a „nevében” befejezés oka.
  - A „nevében” módosítások a lap nevével állnak („Diagnózis · saját értékek (save)”) a Livewire-komponens technikai neve helyett.
- **Félbehagyott „nevében” munkamenet:** ha az admin Kilépés nélkül megy el, 6 óra után magától lezárul „félbehagyva” okkal, a vége az utolsó kérés ideje. Ütemezett feladat: `close:impersonations`, tízpercenként. A lezárt munkamenet böngészője nem dolgozhat tovább a felhasználó nevében, az admin belépésre kerül.
- **A napló őrzése (33.7):**
  - A szervezethez köthető sorok a szervezettel élnek, és vele törlődnek.
  - Fióktörléskor a megmaradó sorokból a diagnózis és a szervezet neve is kikerül.
  - A magánszemély fiókjával a szervezete is törlődik, és vele a teljes naplója.
- **Felhasználók lista (33.8):**
  - A kereső a teljes névre is talál, bármilyen sorrendben („Kovács Anna”, „Anna Kovács”).
  - Új szűrő: „Csak Értéktükör (diagnózis nélkül)”.
  - Törölt felhasználónál az e-mail helyén „–” áll, itt és a szervezet Felhasználók fülén.
- **Adatbázis:**
  - `audit_events.organization_id` (a szervezettel törlődik);
  - `impersonations.last_seen_at`.

## 1.36 · 698ea1a · 2026-10-27

A lépéslista a prototípus idővonala (V1, V1·b, T2), a Kezdőlapon az indulás napja, és az adminban látszik, mi viszi tovább a szakaszt.

- **Lépéslista (V1, V1·b, T2, V10 Onboarding, Kezdőlap):**
  - A szürke, számozott kártyák helyett idővonal: coral gyűrű ponttal az aktuális, zöld pipa a kész, szürke gyűrű a hátralévő lépés, köztük függőleges vonal. A perc jobbra, a „csak olvasható” a név alatt.
  - T2 és a társ Kezdőlapja: a „Hozzáteszel · amíg fut · egy-egy helyzet” az idővonal utolsó sora, nem külön kártya.
  - Az admin A1 előnézete („Amit a vezető lát az első belépéskor”) ugyanígy, sötét alapon.
- **A végén (V1, T2, Kezdőlap):** keretes doboz helyett egy vonal alatt: „A VÉGÉN · 12.14.”, a szaggatott, kérdőjeles „Belső működés” gyűrű, alatta „A legnagyobb rés”, a fejezetek és a Képességek. A társnál az „Útravaló · a te értékeid”. A Brand gyűrűje nincs benne (az MVP-ben nincs Brand).
- **Kezdőlap, ha már van induló diagnózis:**
  - „A vezető útja” alcíme: „… kb. 16 perc · indulás: 10.26.”, kezdőnap nélkül „indulás: egyeztetés alatt”.
  - „A csapat az induláskor (10.26.) kap meghívót.” A társnál: „… hamarosan meghív a(z) … diagnózisába. Indulás: 10.26.”
  - Ezt látja a szervezet vezetője akkor is, ha a diagnózist más vezeti és már meghívót kapott.
- **Admin, a diagnózis adatlapja:** az összegző kártya alján egy sor mondja meg, mi viszi tovább a szakaszt. Például: „{vezető} meghívót kapott (2026.10.27.), még nem lépett be. Amikor maga belép, a szakasz „Kitöltés” lesz (a nevében belépés nem számít).” Alatta: „A státusz a szakaszt követi, kézzel nem állítható: az olvasatig Draft, az olvasat indításával Jóváhagyásra vár, a publikálással Publikált.”
- **Admin, A1:**
  - „Mentem vázlatként” helyett „Mentem, meghívó nélkül”; utána: „Elmentettük. A vezető még nem kapott meghívót: az „Elküldöm a meghívót” küldi el.”
  - A mező neve „Indulás · a vezető határideje”. A súgó szerint magától nem indít és nem küld semmit.
- Nincs adatbázis-változás.

## 1.35 · 218174c · 2026-10-27

A kitöltés és a kezdőlapok fejlécei, gombjai és szövegei a prototípus szerint („1 · Belépés és kitöltés”: V1–V6, V10–V11, T1–T6).

- **Lépéslapok fejléce (V2–V9, T3–T4):**
  - A lépés szimbóluma a cím előtt: gyökér (V2, V3, V5), fa (V4), talaj (V6), spirál (V6·c), micélium (V9), gyűrűk (T4).
  - A lépés kérdése alcímként a fejlécben, nem a lap törzsében. Az élő számlálók („3 / 4 kész”, „9 / 9 megválaszolva”) a törzsben maradnak.
  - Az első lépés vissza-linkje „Diagnózis” (eddig „Mi vár rád”).
  - A Tovább a következő lépés nevét viseli („A szervezet értékei”, „A márkád alapja”, „A megélésed”); ahol a kezdőlapra visz, „Tovább” marad.
- **Saját értékek (V2, V2·b, V2·c; T3):** a három állapot saját címet kap (`?lap=honnan`, `?lap=uj`), így a fejléc is követi őket.
  - V2·b: vissza „Honnan”, alcím „Ha továbblépsz, ezek lesznek a saját értékeid.”
  - V2·c: cím „Érték hozzáadása”, alcím „Mit jelent számodra?”, vissza a listára.
  - A tükör dátuma a sor jobb szélén. „egy helyzet a hétről · 2 perc”; „az Értéktükörből · 3 helyzetedből 3-ban”. A „3 érték elég.” kikerült.
- **V1, T2:** a lépésszám betűvel („Hét lépés, és indul a diagnózis.”, „Két lépés most, egy amíg fut.”). A T2-n a Hozzáteszel a lépéssor folytatása, nem külön kártya.
- **V4:** a gyökér dobozán is van „Szerkesztem”, a szervezet értékeihez visz.
- **Folyamatok:**
  - V6·b: „soha” jelzés a két kizárt tételen; az „Ami továbbmegy” címke–érték párokban.
  - V6·d: a fő gomb „5 folyamat elfogadása”, elfogad és továbbvisz az összegzésre. A „Később folytatom” a V6-ra visz.
  - V6·e: a diagnózisban „Kész” gomb az összegzésre, és „A folyamat lapja” link a Tárak → Folyamatokba.
  - V6·f: a termék limitje a fejlécben; új „Folyamat” (vissza a listára) és „Később” (a kezdőlapra).
- **V10:**
  - Fejléc: gyűrűk, „Teljes · fut · 03.02. → 04.18.”, alatta „3. hét a 7-ből” (snapshotnál „8. nap a 14-ből”).
  - „Hol tartunk” mellett „3 / 7 hét”. Minden sor elején állapotjel: kész, most, később.
  - „Beszélgetések a mégisszel · 2 / 3 · következő: …”; „7 felvétel · 5 dokumentum · 1 átirat”, az elmondott helyzetek külön; „04.14. → 04.17.”.
  - Mélyítés: cím, „Belső működés” a publikálás napjával, és a Brand szaggatottan, „hamarosan”.
- **T5:**
  - Fejléc: gyűrűk, „{csapat} · Teljes · fut”, alatta „3. hét a 7-ből · publikálás: 04.18.”, és a hét-sáv. A V10 és a T5 ugyanúgy számolja a heteket.
  - A Helyzetek az „Amit eddig behoztál” alatt: „2 elmondva · 1 dokumentum · mindegyikhez a megélésed” (vagy „1 megélés hiányzik”), a darabszámmal.
  - „az elején · 03.05. · öt érték”, „a csapat öt értéke, definícióval”. A Coach helye szaggatottan, „hamarosan”.
- **Feltöltés (V11, V11·b, V11·c, T5·b):**
  - V11: cím „Feltöltés”, gyümölcs-szimbólum, „Nem kell eldöntened, hová tartozik.”, „Teljes · fut · 3. hét”. Az Értéktükör sora: „elmondod · 2 perc · legutóbb 03.04.”. Új szaggatott sor: „Megjelenés · kifelé · poszt, hírlevél, ajánlat”, „hamarosan”. A feltöltőmező: „Ide húzhatsz bármit”, „Fájl kiválasztása”. A naptár „Integráció” cím alatt, saját sorként. Folyamatban: „a rutin megvan · a megélés nyitva”.
  - V11·b: cím „Feltöltés”, alcím „Melyik rutinhoz kötődik?”, alatta a fájl neve vagy „elmondva · 0:42”. A rutinoknál ritmus · felelős · létszám. „Egyikhez sem · a mégis sorolja be”, a nem mért folyamatok a végén, új „Mégse” gomb.
  - V11·c: alcím „Hogyan élted meg ezt a helyzetet?”, alatta „Retró · 03.14. · jegyzet feltöltve”; a blokkok „Az értékek · ezen az alkalmon” és „A fa négy eleme”.
  - T5·b: címke „Helyzetek”, alcím „3 helyzet · 2 megéléssel”, pipa a megadott megélésnél, „03.18.”-as dátumok.
- **T1:** „{vezető} meghívott a(z) {csapat} diagnózisába. 8 perc.”
- **T6:** az „Ahogy az elején megélted…” az alcím, a dátum alatta; a sorokban a név és a mondat egyben („Együttműködés: a helyzeteidben 6 · 3 helyzetből”), „a csapat öt értékén túl”.
- **Nem került bele:** a V11·c megjegyzésénél az Elmondom alapértelmezése (JS-változás kellene hozzá); a 3. mérföldkő funkciói (V4·b, V2·c-javaslatok, V10·b, V1·c, T6-címkék, B1) külön jönnek.
- Nincs adatbázis-változás.

## 1.34 · b43746a · 2026-10-27

A belépés utáni címlap az út előnézetével, és visszaút a kitöltött lépésekhez (26 · 26.4, prototípus: „A vezető útja”, „A csapat hangja”, B2).

- **Címlap, amíg a diagnózis nem nyílt meg a usernek (új):**
  - Eddig csak „Szia …” és „amint elindul” állt rajta. Most az út is látszik, szürkén, nem kattinthatóan.
  - **A vezető útja:** a lépések percekkel, a „csak olvasható” jelzéssel, alatta „A végén”. Ezt látja a még draft diagnózis vezetője, és a szervezet vezetője, ha nincs szerepe egy diagnózisban.
  - Alatta **A csapat hangja · {csapat}:** a társak nevei és a társ útja: Meghívó → Mi vár rád → a lépései → Amíg fut, hozzáteszel → Útravaló. A „A csapat az induláskor kap meghívót.” sor zárja. Csak társakkal járó terméknél.
  - **A társnak** csak a saját útja: „{vezető} hamarosan meghív a(z) {csapat} diagnózisába.” (ha még nincs diagnózis: „Amikor a vezetőd elindítja a diagnózist, meghívót kapsz.”), a lépései, a Hozzáteszel és az Útravaló.
  - A draft továbbra is az adminé: a V1 a meghívó után nyílik, a társ T1-e az „Indul” után.
  - Az admin A1 előnézete és a címlap ugyanabból számolja a vezető lépéseit.
- **Belépés = logó:** a Kezdőlap ugyanazt a diagnózist nyitja, mint a belépés. A publikált diagnózis a belépéskor is megnyílik (vezetőnél V12, társnál T5 az Útravalóval), egyéni szervezetben is az Értéktükör előtt.
- **Visszaút a kitöltött lépésekhez:**
  - Ha a vezető diagnózisa már fut, vagy a társnak nincs több lépése, a kezdőlapról nyitott lépés vissza-nyila és a „Tovább” is a kezdőlapra visz („Diagnózis”, illetve „Kezdőlap”), nem a következő lépésre.
  - V10, Onboarding: a lenyitott lépéslista fölött egy sor mondja meg, meddig javíthatók a lépések, és hogy a szervezet értékei és a márkád alapja az induláskor rögzültek.
  - T5·b: a megadott megélés is újranyitható („megélés megvan”), kitöltve. A lapon ott a coral plusz.
  - V11·c: Beküldöm / Kihagyom után a kezdőlap jön (eddig a Hozzáteszek). A T5·b-ről nyitva oda vezet vissza.
  - V11: a coral plusz helyén coral ×, vissza a kezdőlapra.
- Nincs adatbázis-változás.

## 1.33 · 6e020a2 · 2026-10-26

Leírom · Elmondom · Feltöltöm: a hosszabb szöveges mezők fölött fülsor. A szöveg beírható, elmondható vagy fájlból tölthető.

- **Új közös mező (`x-form.long-text`):**
  - **Leírom:** a megszokott szövegmező, karakterszámlálóval.
  - **Elmondom:** felvétel, legfeljebb 3 perc; az átirat a szöveg végére kerül.
  - **Feltöltöm:** txt, md, docx vagy pdf, legfeljebb 10 MB; a fájl szövege a szöveg végére kerül. A fájlt nem tároljuk, csak beolvassuk.
  - Ha megjött az átirat vagy a fájl szövege, a mező magától visszavált a Leírom fülre, és a szöveg javítható.
- **Hol van:**
  - a folyamat adatlapján a leírás és a saját mérőszám leírása (a dupla „Leírás · opcionális” címke is eltűnt);
  - új folyamat a Tárak → Folyamatokban és a diagnózis folyamatlistáján;
  - „Folyamatok szövegből” (a vezetőnél és az adminnál is);
  - a Beszélgetés négy kérdése, a Képességek helyzetei, az alkalom jegyzete és a riport „Miben nem?” visszajelzése.
- **Rövid mezők:** az 500 karakteres márka- és szervezetiérték-mezőkön marad a mikrofon gomb, fülsor nincs.
- **Ami nem fér el:**
  - Ha az elmondott vagy feltöltött szöveg hosszabb, mint amennyit a mező enged, az AI a maradék helyre tömöríti. Ha már kevés hely maradt, a meglévő szöveggel együtt tömöríti.
  - A mező alatt egy sor szól erről, például: „A fájl szövege 8 400 karakter volt, nem fért el: az AI 1 790 karakterre tömörítette. Nézd át.”
  - Ha az AI nem válaszol, a szöveg a mező határán levágva kerül be, és ezt is jelezzük.
  - Új AI-feladat: *Szöveg tömörítése mezőhöz* (`text_condensing`, kis modell, `szoveg-tomorites` v1 prompt).
- **Értéktükör és Egy helyzet:** ugyanazok a feliratok, ugyanabban a sorrendben: Leírom · Elmondom · Feltöltöm (az Egy helyzetnél csak az első kettő). Alapból továbbra is az Elmondom van kiválasztva.

## 1.32 · 0066c17 · 2026-10-26

Képességmérés a vezető és a társ útjában: kitöltés, futás, kész (NM-12; 17 · 17.3, 23, 25, 26).

- **Képességek lépés (új):**
  - A vezetőnél a Megélés után, a Folyamatok előtt jön, 3 perc. A társnál a T4 után.
  - Csak akkor látszik, ha a képességmérés be van kapcsolva: globálisan (`CAPABILITY_MEASUREMENT`) és a terméknél is. A V1, az A1 előnézete, a lépésszámláló és a meghívók percei ehhez igazodnak.
  - A user nem pontoz, hanem friss helyzeteket mesél el az értékeihez, leírva vagy elmondva. A diktálás átirata a mezőbe kerül, javítható.
  - Kártyánként: az érték a saját szavaival, címkével (a szervezeté, a tiéd, a tiéd is) és egy kérdés:
    - próba: „Mikor került valamibe nemrég ez az érték?”;
    - elakadás: „Mikor kellett volna nemrég ez az érték, és nem úgy ment?”;
    - saját érték: „…és ki látta rajtad kívül?”.
  - **Javasolt kártyák:**
    - Teljes: a megélésben legjobb és legrosszabb szervezeti érték, meg egy saját érték;
    - Snapshot és társ: 1 kártya, a legrosszabbul megélt érték.
  - Másik érték választható, legfeljebb 5 kártya lehet. Egy helyzethez legalább 40 karakter kell.
  - A lépés „Kihagyom”-mal is kész. Az olvasatig javítható, utána csak olvasható.
- **Mérés:**
  - A beküldött helyzet új anyagtípus: *Képesség-helyzet*. A kérdés a mégis sora, a válasz a kitöltőé.
  - Értékmérés nem fut rajta, csak a képességmérés. A javított helyzetet újraméri, a törölt helyzet a nyomait is viszi.
  - Az AI tudja, hogy önbevallás, és hogy a kérdésből nem lehet nyom.
  - Egy ember helyzetei egy forrásnak számítanak (KO-02), a társé külön forrás.
- **Fut (V10):** új sor „Képességek · 3 helyzet elmondva · ebből 1 a társaktól”. Csak szám, válasz nem; az olvasatig a lépésre visz.
- **Kész:**
  - **V12:** Képesség-kártya, legfeljebb 3 erősség és 3 hiány (Snapshotban 1 + 1), konfidenciával, „Érettségi szint a második diagnózistól.”, és link a Képességtárra. A riport R0 és R1 kártyája is ezt mutatja, ha van adat; az R7 és a Bridge továbbra is NM-15.
  - **Tárak → Képességtár (M3, új):** „Jelen lehet · közepes · 4 nyom”. Lenyitva: mit jelent, „Ha megy, így néz ki”, akadály, és egy idézet. A saját helyzet idézete mindig látszik; társé csak bekapcsolt társ-idézetekkel és a társ hozzájárulásával, név nélkül.
  - Csak az admin által jóváhagyott „jelen lehet” és „hiányzik” sor jelenik meg. RC-kód és lépcső nincs. Csábító tétel kevés adattal nem kerül a hiányok közé.
  - Ki látja: aki a riportot. A menü Tárak csoportjában akkor jelenik meg, ha van mit mutatni.
- **Publikálás:** új tétel az ellenőrzőlistán, „Minden megmutatott képesség jóváhagyva”. A Képességek fülre visz, és csak akkor van ott, ha van ilyen sor.
- **V1 és V6:** a vég képén „Képességek · amire építhettek, és ami hiányzik”; az „Amit utána mérünk” blokkban „Képességek · folyamat- és emberoldal · állapot”.
- **Adatbázis:** új tábla, `capability_stories`.

## 1.31 · ba261de · 2026-10-26

A riport a prototípus szerint („2 · Riport”: R0–R9, RM, S1), a fő és az önálló diagnózisnál is (27, 28, 04 · 4.6).

- **Keret minden lapon:**
  - **Fejléc:** szimbólum, a lap címe, a kérdés, a meta-sor („Alany · Termék · 10.26.”); a mélyítő lapon a „diagnózis-nézet” jelvény.
  - **Fejezet-pöttyök** (R0–R8): link a meglévő lapokra, szaggatott pötty, ahol a lap hiányzik vagy hamarosan jön.
  - **Lábléc:** Coach, „2 / 8 · Értékek”, és egy gomb a következő oldalra. A márkád alapja után a zárólap jön, amíg az R6–R8 nincs kész.
- **Lapok:**
  - **R0:** két gyűrű jelmagyarázattal és 80%-os sávval, a lépcső jelölővel, a fejezetek hatszeletes mini-sávval.
  - **R1:** öt színes blokk.
  - **R1·b:** csoportonként minden belső mérőpont.
  - **R1·c:** táblák a csapat-oszlop kiemelésével.
  - **R2–R4:** sorok rés- vagy alkalom-alsorral, utánuk az állítások.
  - **R5:** a fa kilenc eleme körben, lenyíló elemenkénti sorok.
  - **R2·b–R5·b:** PHI, lépcső, megélés, definíció, bizonyíték.
  - **R9:** egy mondat, csendes összegzés, sárga javaslat-blokk a foglalással.
  - **RM:** táblázat és lenyíló listák.
  - **S1:** egy lapon, a „Snapshot · Külső” helyével.
- **Hamarosan:** mindenhol ott van a helyük, inaktívan, „hamarosan” felirattal. Ez a Brand gyűrű, a Brand csoport és a Brand riport; a Képesség; a Coach; a Bridge to Purpose; a Megosztás; a fa „kívül” oldala és további öt eleme.
- **Nézetváltó:** karcsú pill, bal oldalt az „összevetés” link. A visszajelzés vonallal válik el, kártya nélkül.
- Nincs adatbázis-változás.

## 1.30 · 9707836 · 2026-10-26

Az önálló diagnózis riportja a felhasználónál, és előnézete publikálás előtt (04 · 4.6, 06 · 6.5).

- **Riport a felhasználónál (új):**
  - A publikált önálló diagnózis (pl. Értéktükör, feltöltés, kézi vagy aggregált) a fő diagnózis riportjának nézeteivel jelenik meg: válasz, fejezetek a tételekkel és az idézetekkel, zárólap, Melléklet, PDF.
  - A menü *Riportok* blokkjában található.
  - Ki látja: felhasználói szinten csak az érintett; csapat szinten a csapat tagjai és a szervezet vezetői; szervezet és folyamat szinten a szervezet aktív tagjai.
  - Felhasználói szinten „te” a megszólítás („A te diagnózisod”, „5-nek látod, 2-t mutatsz.”).
  - Nincs benne összefoglaló, belső működés, összevetés, nézetváltó, állítás és visszajelzés. Az üres márkád alapja sorok kimaradnak.
- **A mégis javaslata:** új mező a diagnózis admin oldalán, md-szerkesztővel. Az új Alcím gomb mellett félkövér, dőlt, felsorolás és link is van benne. A felhasználó a zárólapon formázva látja. A fő diagnózis zárólapja és PDF-je is formázva mutatja a javaslatot.
- **Előnézet (új):** a diagnózis oldalán „Előnézet” gomb. Telefon vagy asztali keretben végigkattintható a felhasználó riportja publikálás előtt. Az oldal kiírja, ki látja majd, és ott a „Publikálom” gomb.
- Nincs adatbázis-változás.

## 1.29 · dfac402 · 2026-10-26

Folyamatok: leírás, és felvétel szövegből vagy beszédből (11).

- **Leírás:** minden folyamat kaphat opcionális leírást, legfeljebb 2000 karakterben.
  - Megadható a kézi hozzáadásnál (V6·d, Tárak → Folyamatok „+ Rutin”, admin), és az adatlapon (V6·e), ahol elhagyáskor mentődik.
  - A V6·d kártyán két sorig, az admin táblában a név alatt rövidítve látszik.
- **Felvétel szövegből vagy beszédből (új):** a V6·d-n és az admin *Folyamatok* fülén.
  - A szöveg begépelhető vagy diktálható. A diktálás átirata a mezőbe kerül.
  - „Feldolgozom”: az AI kigyűjti a folyamatokat névvel, leírással, ritmussal és szülővel. A szülő a szöveg egy másik folyamata vagy egy meglévő felső szintű folyamat lehet; legfeljebb két szint.
  - A már meglévő nevű folyamat nem jön újra, az alfolyamatai a meglévő alá kerülnek.
  - Előnézet: soronként szerkeszthető név, ritmus, szülő és leírás, a sor kivehető. A felvett folyamatok „Kézzel hozzáadva” jönnek létre.
  - Új AI-feladat: `process_parsing`, prompt: `folyamat-szovegbol` v1. A hívás a gombnyomásra fut, a válaszra várva.
- **Diktálás az adminban:** az admin felvétele az adminé (eddig a diktálás csak ügyféloldalon volt).
- **Adatbázis:** `processes.description`.

## 1.28 · 46056a3 · 2026-10-26

Értéktükör a prototípus szerint: ET4 elmélyülés, a teljes kép a fával, az e-mail a beküldés előtt (06, NM-06).

- **ET2:** a regisztráció első része a beküldés előtt jön. Kártya: „Hova küldjük a tükröd?”, e-mail mező és az ÁSZF meg az adatvédelmi szabályzat elfogadása (a 05-ös jegyben feltöltött hatályos verzióra linkel). Nélkülük a tükör nem küldhető be. A megerősítő levél a beküldéssel megy ki. Meglévő aktív fióknál nem megy levél: a felhasználó az ET5-ön lép be helyben.
- **ET3:** a megtartott értékek neve az ET4-re visz. Lent: az első érték neve és „A teljes kép”.
- **ET4 · elmélyülés (új):**
  - Értékenként egy lap, lapozással („Egyenesség · 1 / 3”).
  - A mégis Coach a kitöltő idézeteiből írja: mit jelent nálad (elfogadható: „Ez az”, vagy átírható: „Átírom”), miben látszik, mi az 1 és mi a 6. Alatta az idézetek, amikből épült.
  - Új AI-feladat: `mirror_deepening`, prompt: `ertektukor-elmelyules` v1. A mérés után külön jobban fut, tükrönként egy hívással; az ET3 nem vár rá.
  - Ha a Coach nem válaszol, az Értéktár szavai állnak a helyén. A hiba az Értékmérések alatt Feldolgozási hiba sort nyit, az „Újra” csak a Coachot indítja.
  - Kikapcsolható: `MIRROR_DEEPENING=false`.
  - Az elfogadott vagy átírt definíció és a két skálavég a saját értékek lépésébe (24 · V2) készen érkezik.
- **ET5 · teljes kép:** charcoal manifesztó-sáv, a fa kilenc eleme egy körön a saját értékek körül, a kíváncsiság-kérdések mellett az elemek jelei. A jelölés azonnal mentődik. **E-mail mező és időpontfoglalás nincs** (a foglalás kódja megmarad, egyelőre nem hívja semmi). Alul: „Nézd meg a leveled”, vagy belépés a meglévő fiókba.
- **A megerősítés után:** a felhasználó a tükre eredményére érkezik a fiókban, onnan az ET4 és az ET5 is elérhető. A megerősítés a feldolgozás vége előtt is jöhet; ilyenkor a diagnózis a feldolgozás végén épül meg. Magánszemélynél a belépés is a legutóbbi tükörre visz (eddig az Értéktárra).
- **Saját menü:** az ET4 itt is megvan. Az *Értéktükör* lapján új lista: „Korábbi tükreid”. Az ET5-ön nincs foglalás.
- **Meglévő fiók:** az ET2-n elfogadott dokumentumverziók a belépéskor a fiókhoz kerülnek.
- **Adatbázis:** `mirror_sessions.confirmation_sent_at`, `mirror_sessions.insight_status`, új tábla: `mirror_value_insights`. Az „awaiting_confirmation” státuszt új tükör már nem kapja.

## 1.27 · d81f3c9 · 2026-10-21

Képességrendszer, K2: képességmérés (NM-12).

- **Mikor fut:** az értékmérés után, automatikusan, minden diagnózis-anyagon, külön jobban. Ide tartozik a Teljes, a Snapshot, az admin-feltöltés és a bejelentkezett tükör. Nem fut a nyilvános tükrön, a diktáláson és a diagnózison kívüli folyamat-alkalmon. Kikapcsolható: globálisan, termékenként, és külön a fődiagnózison kívül.
- **Két AI-lépés:**
  - **A, nyomkeresés:** részenként, semleges kódú képességkatalógussal (K1…). Név, RC- és M-kód, esetnév (DXC, SZV…) és forráskód nem megy az AI-nak. Nyomonként: szó szerinti idézet, hely, szerep, szint (határfelülettel), irány, közvetlenség, nyers vagy feldolgozott, jelölések, E1–E9, alternatív olvasat, jelölt.
  - **B, mintaillesztés:** képességenként, a képesség mintáival (P1…) és a detektor-hibamintákkal mint tiltó szabályokkal (X1…). Minta nélküli képességnél értelmezés a definíció alapján, mindig alacsony.
  - A prompt a mégis Tesztpad-promptját (v0.3) követi.
- **Védelem:**
  - A kérdező mondata és a forrásban nem szereplő idézet kiesik.
  - A gép legfeljebb közepes konfidenciát ad. A hipotézis-minta találatát és az értelmezést ember hagyja jóvá (NY-26).
  - Az újrafuttatás megtartja, amiről admin döntött.
- **Állapot és konfidencia:** diagnózisonként, képesség × szint szerint, a Szabálykönyv szabályaival:
  - AL-01–04;
  - KO-02–07: független forrás, csak önkép, csábító tétel, szint és lefedettség, mikro szervezet, külső hang;
  - egyenetlenség.
  - A feldolgozott és a jelölt nyom csak megerősít. Az admin által beállított érték megmarad. Ha egy jóváhagyott sor automatikus értéke változik, újra jóváhagyásra vár.
- **E1–E9** diagnózisonként, a nyomokból: megvan / részben / nincs / nem tudjuk.
- **Admin:** a diagnózis új *Képességek* füle (fődiagnózisnál fül, sima diagnózisnál gomb). Itt lehet:
  - megerősíteni vagy elvetni a találatot, egyenként vagy képességenként;
  - kihagyni a nyomot;
  - jóváhagyni vagy átírni az állapotot indokkal (a „magas” csak itt adható);
  - akadályt megadni, E1–E9-et jóváhagyni;
  - látni a jelölteket, és újrafuttatni a mérést.
- **Mintatár-számlálók:** az elvetett javaslat téves riasztás. A számlálók névtelen naplóból jönnek, így ügyféltörlés után is megmaradnak. A minta-érettségbe a termék jóváhagyott ügyfelei is beszámítanak. Ha legalább 4 javaslatból legalább a felét elvetik, a minta Beérkező-sort kap felülvizsgálatra (NY-27).
- **Beérkező:** új sortípusok: *Képességek átnézése*, *Minta felülvizsgálata*, *Minta- vagy képesség-jelölt*. A képességmérés hibája saját sort kap, és az „Újra” csak ezt indítja újra, nem az egész anyagot.
- **Parancs:** `megis:capabilities:run {anyag} --dry` a Tesztpad-kalibrációhoz.
- **Nincs még benne:** riport és Bridge (NM-15), érettség a második diagnózistól. A mégisnek szóló nyitott kérdések a tervben vannak: kalibráció, NY-26 értelmezése, nyers vagy feldolgozott átirat, KO-06 adatai, anonimitás, adatkezelési tájékoztató.

## 1.26 · 9e19a31 · 2026-10-21

Képességrendszer, K1: Mintatár (NM-19).

- **Betöltés:** a *Frissítés a mégis tárából* a Képesség és a Képesség-részletek fül mellett a Minta és a Szabálykönyv fület is betölti, egy feltöltésben, ugyanazzal az előnézettel.
  - A Bizonyíték, a Kapcsolat, a Találat és az Esettár ügyféladat, soha nem jön be. A mintákhoz csak a Sheet által számolt darabszámok jönnek (jóváhagyott ügyfelek, javasolt találatok, téves riasztások, bizonyítékok), a képességekhez a megerősített találatok és a kapcsolt bizonyítékok száma. Ezek nem írnak verziót.
  - A régi kézi oszlopok („… (v0.3.2, kézi)”) kimaradnak. Az ideiglenes azonosító (pl. F33-M01) hiba, az ismeretlen képességkód figyelmeztetés.
  - Az illeszkedés lehet egy szó minden kapcsolt képességre, vagy soronként képességenként, megjegyzéssel.
- **Javítás:** az xlsx-beolvasó a képletcellák számított értékét adja, nem a képletet. A Sheet számlálói és a minta-érettség képletek.
- **Minta-érettség:** a Sheet képlete szerint számolva (hipotézis irodalomból vagy saját megfigyelésből / egy esetben láttuk / használt / elavult / elvetett). A valódi v0.4-es táron egyezik a Sheettel: 6 használt, 142 egy esetben látott, 24 + 26 hipotézis.
- **Admin:**
  - *Képességtár → Minták*: lista szűrőkkel (képesség, típus, altípus, érettség, szint, termék, érvényes) és csak olvasható adatlap verziókkal. A detektor-hibaminta jelzést kap: ügyfél soha nem látja.
  - *Képességtár → Szabálykönyv*: a 75 szabály területtel és státusszal.
  - A képesség adatlapján új *Minták* fül, altípus szerint. Minta nélkül: „a felismerés a definíció alapján értelmezés”.
- **Próba a valódi v0.4-es táron:** 75 képesség, 198 minta, 357 képesség–minta kapcsolat, 75 szabály, hiba nélkül. Egy figyelmeztetés: az M-012 illeszkedése „jelenlét (csapat)”, ez a Sheetben javítandó.

## 1.25 · 274f547 · 2026-10-21

UI-finomhangolás, 8. lépés: lezárás.

- **Akadálymentesség:** a képernyőkép-túra minden oldalt ellenőriz is (`UI_A11Y=1`). Mind a 45 fő oldal (ügyfél, riport, tükör, admin, megerősítő ablak, toast, lapozó) hiba nélkül megy át 390 és 1280 px-en.
- **Átlátszóság helyett tömör színek:**
  - A fejlécek alcíme és jegyzete, a V1-előnézet és az inaktív sorok halványítása tömör színű lett; a design system nem használ átlátszóságot.
  - Az ellenőrző az áttetsző szöveg mögött nem találta a hátteret a ragadós előnézetben.
- **A teljes terv kész (1.18–1.25):**
  1. alapok;
  2. visszajelzés;
  3. navigáció;
  4. tokenek;
  5. komponensek;
  6. ügyfélfelület;
  7. adminfelület;
  8. lezárás.

  Előtte–utána képek: 42 oldal, 390 és 1280 px.

## 1.24 · 06d0397 · 2026-10-21

UI-finomhangolás, 7. lépés: az admin képernyői az új komponenseken.

- **Egy művelet, egy kinézet:**
  - A mentés szén, a hozzáadás plusz ikont kap, a mégse mindenhol „Mégse” (három űrlapon „Vissza” volt).
  - A törlés, archiválás, inaktiválás és visszavonás törlés-tónusú, piros „Igen”-nel kér megerősítést; eddig a szerkesztéssel azonos menüpont vagy coral gomb volt.
  - Az emlékeztetés és a meghívás levél ikont kap; soronként egy meghívógomb (a tagoknál kettő volt).
- **Sorműveletek:**
  - A sor fő művelete (szerkesztés, emlékeztetés, megnyitás) gomb marad; a többi és a lezárás a „⋯” menüben van.
  - Négy különböző minta helyett egy.
- **Táblák:**
  - Az üres lista megmondja, mi hiányzik, és felajánlja az első hozzáadását; „Nincs találat.” csak keresésnél vagy szűrésnél áll.
  - A „Lezártak” üres listája is üzen; eddig csak a fejléc látszott.
  - Minden fejléccella rendezhető kinézetű, ahol rendezhető.
- **Oldalak:**
  - Egységes fejléc: vissza link, badge-ek, leírás, műveletek; a kézzel írt h1-ek megszűntek.
  - Űrlap 3xl, adatlap 5xl szélesség. Az Értéktár és a Képességtár szerkesztő és verziók füle azonos szélességű, nem ugrik.
  - A hosszú képesség-űrlap szakaszokra bomlik.
- **Űrlapok:**
  - Rádiócsoportok csempeként, fájlmezők pill-gombbal, a kis checkboxok a közös komponenssel.
  - A mező és gomb sorok a közös elrendezéssel; a 9 kézi igazítás megszűnt.
- **Olvasat:**
  - A három hasáb 1200 px-től; 1024 px-en a középső kb. 96 px széles volt.
  - Egy szakaszcím-méret; a kicsi aláhúzott műveletek gombok.
  - Az md-szerkesztő márkás: magyar tooltipek, látható gombok (B, I, felsorolás, link, előnézet); eddig az ikonok üresek voltak, és az állapotsoron „characters:” állt.
- **Futás és publikálás:**
  - Az „Olvasat indítása” és a „Publikálom” a lap fő gombja (szén, nagy).
  - A küszöb-figyelmeztetés és a jegyzetek értesítő dobozok.
- **Beérkező:** a besorolás, az emlékeztetés, az újrapróbálás és a megnyitás a sorban marad; a lezárás felirata és képernyőolvasós neve egyezik.
- **Menü:** nagyobb célterületek a csoportfejléceken és a menüpontokon.
- **Megerősítő ablak:** egyszerre egy „Igen” gomb van az oldalon; eddig a rejtett párja is ott volt.
- **Mozgás:** a menük, a toast és a dialógus csak rövid mozgással érkezik, áttűnés nélkül, így a szövegük az első pillanattól olvasható. A menü záráskor azonnal eltűnik.
- **Szövegek:** a gombok első személyű igék („Mentem”, „Szerkesztem”, „Törlöm”, „Inaktiválom”, „Archiválom”, „Elküldöm a meghívót”, „Belépek a nevében”…). A „+ ” előtagok helyett plusz ikon. A „→” jelek helyett ikon, képernyőolvasós felirattal.
- **Konvenció-teszt:** minden nézet minden szabályt teljesít; a kivétellisták kiürültek.
- **Teszt:** böngészős teszt a fő ügyfél- és admin-oldalakra: 390 px-en egyik sem szélesebb a képernyőnél.

## 1.23 · 82ce429 · 2026-10-21

UI-finomhangolás, 6. lépés: az ügyfél képernyői az új komponenseken.

- **Diagnózis-lépések:**
  - Lépésjelző sáv a fejlécben a „3 / 6” alatt.
  - A „Tovább” mindenhol ugyanaz: szén, nagy, nyíllal; mellette a „Mentve”. Eddig hét helyen coral, egy helyen szén volt.
  - Ha még nem lehet továbblépni, a gomb alatt ott az ok („Még 2 sorra kell válasz.”); eddig csak szürke volt.
  - A lépés kérdése címsor (h2), az alatta lévő blokkok h3-ak.
  - Hibás küldés után a fókusz az első hibás mezőre ugrik, akár a lap tetején van.
  - A megélés-skála elfér a telefonon: a hat pont egyenlő oszlopban, alattuk a „nem tudom”. Eddig 390 px-en átcsúszott.
  - A saját és a szervezeti érték törlése megerősítést kér; eddig egy „×” azonnal törölt.
  - Az alkalom-megélés figyelmeztet, ha beküldetlen válaszokkal hagynák el az oldalt. Autosave helyett: az adatmodellt nem érinti.
- **Egységes elemek:**
  - Kártyák, link-sorok (cím, alcím, chevron), értesítő dobozok, üres állapotok és lenyitható szakaszok chevronnal (eddig háromféle jelölés vagy semmi).
  - Szegmentált kapcsolók hét helyen (felvétel/leírás, riport-nézet, folyamatfajta, visszajelzés, belépési mód); eddig ötféle kinézet.
  - Választócsempék, fájlmezők, mező és gomb egy sorban.
  - Minden eltávolító gomb 44 px-es ikon-gomb; eddig 24 és 12 px.
- **Riport:**
  - A szakaszcímek kisebbek a fejléc címénél.
  - Egységes lapozó-lábléc: balra a hely vagy a vissza, jobbra a „Következő” gomb. Eddig négyféle volt.
  - A csapat-nézet tiltva is megmondja, miért (eddig csak egérrel olvasható súgóban).
  - A Markdown-szövegek formázva jelennek meg (felsorolás, címsor); eddig a stílus hiányzott.
  - A „›” és „→” jelek helyén ikonok vannak.
- **Értéktükör:**
  - Az ügyfélfelületen nincs dupla h1.
  - A gomb-másolatok helyén a közös gombok; a foglalás gombja a beleegyezésig valóban tiltott.
  - A feldolgozás szimbóluma pulzál, nem forog (a design system nem forgatja a szimbólumokat).
- **Belépés és regisztráció:**
  - A jelszavas belépés kapcsolója a „Belépek” elé került.
  - A kódmező csak számjegyet fogad; a beillesztett kódból kiszűri a szóközt.
  - Az újraküldés visszaszámlálója nem szól másodpercenként a képernyőolvasóban.
  - A „Másik e-mail” egyszer szerepel.
  - A regisztrációnál a jelszó megismétlése csak jelszó után jelenik meg, és a tiltott gomb alatt ott az oka.
  - Az új lapon nyíló linkek ezt kimondják.
- **Profil:**
  - A „Mentve” ugyanaz, mint a lépéseken.
  - A kódmezők hat számjegyesek.
  - A hozzájárulás visszavonása, a naptár lecsatolása és a hang törlése törlés-tónusú.
- **Folyamat-adatlap:** egy visszaút; a „Kész” és a „Vissza a listához” ugyanoda vitt.
- **Szövegek:**
  - A gombok első személyű igék: „Összekötöm a naptárat”, „Mentem a jelszót”, „Elküldöm”, „Emlékeztetem őket”, „Elkezdem a felvételt”, „Rutint adok hozzá”, „Megnyitom”, „Átnevezem”, „Kijelentkezem”.
  - A három jelentésű „Kész” helyett „Befejezem”, illetve „Leállítom”.
  - A dátumtartományok kötőjellel jelennek meg.
  - Az ügyfélnézetek saját nyelvi kulcsokat kaptak az admin kulcsok helyett; a „PHI” és a „PDF” fordítókulcsból jön.
  - Egy fő nézete egyes számban szól („látod”), nem „látjátok”.
- **Konvenció-teszt:** az ügyfél és a közös nézetek kivétel nélkül teljesítik a kártya- és ikonszabályt.

## 1.22 · 4530bdf · 2026-10-21

UI-finomhangolás, 5. lépés: a közös komponenskészlet. A képernyők a 6–7. lépésben állnak át rá.

- **Új komponensek** (`resources/views/components/ui`):

| Komponens | Mire való |
|---|---|
| `card` | Tónus, keret, belső térköz és elem szerint; ez lesz minden fehér doboz |
| `link-row` | Cím, alcím, chevron |
| `icon-button` | 44 px-es kör ikon-gomb, kötelező felirattal |
| `notice` | Info, siker, figyelmeztetés, hiba: egységes színek és képernyőolvasó-szerepek, opcionális cím és akció |
| `empty-state` | Szimbólum, mondat, az első elem hozzáadása |
| `dropdown`, `dropdown-item` | A menük (lásd lent) |
| `tabs` | Aláhúzott és „mappa” változat |
| `segmented`, `segment` | Egy választható a pillek közül |
| `section-header` | Egy méret minden szakaszcímnek |

- **Menük:**
  - Az admin sorműveletek és a két avatar-menü ugyanaz a `dropdown`.
  - Esc-re bezárul, és a fókusz visszaáll a gombra; bezárul kívülre kattintásra és görgetésre is. Rövid beúszással nyílik.
  - A sorműveletek gombja a „továbbiak” (⋯) ikon 44 px-en; eddig a hamburger-ikon volt 28 px-en.
  - Törlésre `danger` tónusú menüpont.
- **Űrlapok:**
  - Egy `field` stílus minden mezőn: a form-komponensek, az admin szűrők, a kereső és a listaszerkesztő eddig háromféle magasságú volt.
  - A kereső nagyító ikont kap. A textarea `maxlength` mellett karakterszámlálót mutat.
  - Új `radio-group` (választócsempék), `file` (pill-gombos fájlmező) és `inline` (mező és gomb egy sorban, a gomb a mezőhöz igazodik, nem a címkéhez).
- **Admin:**
  - Az oldalfejléc vissza linket, badge-eket és leírást is kaphat. A hét oldalon hatástalan `class="mb-0"` helyett `:spaced="false"`.
  - A rendezés jelei (▲▼↕) ikonok.
  - A listaszerkesztő fel/le/törlés gombjai 44 px-es ikon-gombok, a fogd-és-vidd fogó ikon („⋮⋮” helyett).
  - A szervezet és a főbb diagnózis fülsora az új `tabs`.
- **Markdown:** a `prose-megis` stílus (bekezdések, címek, márkás felsoroláspont, coral idézetvonal) a typography plugin nélkül.
- **Teszt:** az admin fiókmenü tesztje átméretezés után újratölt (időzítés).

## 1.21 · ee6ddcf · 2026-10-21

UI-finomhangolás, 4. lépés: a design tokenek lezárása.

- **Csak a márka értékei:**
  - A színek után a sarokívek, az árnyékok és a betűvastagságok is le vannak zárva a Tailwind témában.
  - Sarokív: kártya (20 px), mező (12 px, új `rounded-field`: mezők, menüpontok, betétpanelek), pill és kör.
  - Árnyék nincs.
  - Betűvastagság a Geologica négy vágása: 400, 500, 600, 800.
- **Cserék 46 nézetben:**
  - `rounded-lg`/`rounded-xl`/sima `rounded` → `rounded-field`, illetve pill.
  - `font-bold` → `font-extrabold`: a körbe írt számok és monogramok.
  - A felvevő kártyájának és a Kert csempéinek árnyéka kikerült. A Kert csempéje hoverre 3 px-t emelkedik (a design system szabálya).
- **Lapozó:**
  - Az admin listák lapozója márkás: kerek oldalgombok, az aktuális kitöltve, chevron ikonok, magyar összegzés („1–25, összesen 30”).
  - Eddig a Livewire gyári nézete nem létező színekre és árnyékra hivatkozott.
- **Build:**
  - A CSS már nem függ a lefordított nézetek gyorsítótárától: a `storage/framework/views` és a Laravel-lapozó `@source` sora kikerült.
  - A konvenció-teszt árnyék-, sarokív- és betűvastagság-szabálya kivétel nélkül teljesül.

## 1.20 · 4dc52cd · 2026-10-21

UI-finomhangolás, 3. lépés: navigáció és layoutok.

- **Ügyfél menü (mobil, tablet):**
  - A bezárt menü linkjei eddig Tabbal és képernyőolvasóval elérhetők voltak; most nem.
  - Nyitva a fókusz a menüben marad, a háttér nem görget. Esc-re bezárul, és a fókusz visszaáll a menü gombra.
- **Haza mindenhonnan:**
  - A logó a kezdőlapra visz, és a menü elején „Kezdőlap” pont van.
  - Az Értéktár, a Folyamatok és a Bölcsességtár „‹ Kezdőlap” vissza linket kap; eddig zsákutcák voltak.
- **Fejléc:**
  - A vissza link chevron ikont és 44 px-es célterületet kap.
  - Az avatar és a menü gomb köre marad 36 px, a kattintható terület 44 px.
  - A lépésszám („3 / 6”) nem tűnik el hosszú vissza-felirat mellett.
- **A „+” gomb:**
  - Ikon, árnyék nélkül, az iPhone alsó sávja fölött.
  - A hover nem cream-on-coral (kontraszt).
  - A nyitott menü háttere alá kerül.
  - Az oldal alja nem csúszik alá (T5).
- **Admin menü mobilon:** bezáró gomb, Esc, fókuszcsapda; bezárva nem érhető el. A fejléc gombjai 44 px-esek.
- **A főbb diagnózis hat füle (Áttekintés, Beállítás, Futás, Olvasat, Publikálás, Részdiagnózisok):**
  - Mindegyiken ugyanaz a fejléc: a cím és a szakasz-badge, az adatsáv, a fülek, azonos szélességben.
  - A fejléc mindig a szervezet zöldje, aktív „Diagnózisok” füllel. Eddig fülváltáskor zöld és szén között váltott, és ugrott a szélesség.
- **Admin táblázatok mobilon:** nem szélesítik ki az oldalt. A rendezés rejtett feliratai kilógtak a görgethető táblázatból; öt admin lista 566–897 px széles volt 390 px-en.
- **Hibaoldalak:**
  - Új „Tovább a fiókodba” link: a belépett ügyfelet a kezdőlapjára, mást a belépéshez viszi.
  - A 419 („Lejárt a munkamenet”) újratölti az előző oldalt.
- **Feliratok:** „Health” → „Állapot”.
- **Teszt:** böngészős tesztek a menükre (elérhetőség, fókusz, Esc) és a hazavezető linkekre.

## 1.19 · 2d4a6ce · 2026-10-21

UI-finomhangolás, 2. lépés: visszajelzés.

- **Megerősítés:**
  - A böngésző saját „OK / Mégse” ablaka helyett márkás dialógus kérdez mind a 39 helyen; a hívások nem változtak.
  - Az alapértelmezett gomb a „Mégse”; az Esc és a háttérre kattintás is nemet mond.
  - A `data-confirm-tone="danger"` piros „Igen”-t ad, a `data-confirm-label` megnevezi a gombot (pl. „Törlöm”).
- **Új megerősítések:**
  - Kérdez a kézi átírás visszaállítása (sor és összpontszám) és a „csapat-szám nélkül megy” visszavonása; eddig kérdés nélkül futottak.
  - Nem kérdez a szervezeti érték és a listaszerkesztő „×”-e, mert csak a mentetlen űrlapból vesz ki, és a „kevés adat” jelölés sem, mert visszanyitható.
- **Toast:**
  - A mentés, inaktiválás stb. visszajelzése alul jelenik meg (asztalon jobbra lent), nem a hosszú lap tetején, ahol a lap alján dolgozó felhasználó nem látta.
  - A siker 6 mp után eltűnik, amíg rá nem mutatnak; a hiba bezárásig marad. Ugyanaz az üzenet újra is megjelenik.
  - Livewire-ből: `$this->dispatch('toast', message: …, type: 'error')`.
  - A belépő oldalak a helyükön, az űrlap mellett mutatják az üzenetet.
- **Fióktörlés:** a „Véglegesen törlöm” gomb a törlés tónusát kapja; eddig ugyanolyan coral volt, mint a „Tovább”.
- **Teszt:**
  - Böngészős teszt a megerősítésre és a toastra.
  - A desktop-logó teszt az átméretezés után újratölt, mert időzítéstől függően még a mobil elrendezést számolta.

## 1.18 · dedc812 · 2026-10-21

UI-finomhangolás, 1. lépés: a megrendelő kérésére egységesebb, modernebb felület (terv: 9 lépés, ez az első kettő).

- **Linkek:** a szöveges linkek coral aláhúzást kapnak (a design system szabálya), hoverre halványabbat. Eddig a Tailwind alapstílusa levette az aláhúzást, így ~100 link nem különbözött a szövegtől. A gombnak, sornak, kártyának formázott linkek aláhúzás nélkül maradnak.
- **Gombok:**
  - Új `danger` tónus a törlés jellegű műveletekhez és új `ghost` tónus a harmadlagos szöveggombokhoz.
  - Ikon a felirat előtt vagy után (`icon`, `icon-right`).
  - Hoverre világosodnak, nem sötétednek (design system): a szén zöldre, a coral és a sárga a világos árnyalatára vált.
  - Legalább 40, illetve 44 px magasak, a mobilos érintéshez.
- **Töltési állapot:**
  - Amíg a Livewire a gomb kérését futtatja, a gomb halvány, nem kattintható, és pörgő jelet mutat.
  - A sima űrlapok (belépés, kijelentkezés) ugyanígy jelzik a küldést, és nem küldhetők el kétszer.
  - A letiltott link-gomb nem követhető.
- **Mozgás:** a design system időtartamai (120/200/420 ms); a „csökkentett mozgás” beállításnál nincs animáció.
- **Új ikonok:** chevron balra/fel/le, nyíl fel/le, továbbiak, fogd-és-vidd, rendezés, keresés, mikrofon, feltöltés. A meglévő ikonokból származtatva vagy azok stílusában rajzolva; ideiglenesek, a márkagazda jóváhagyására várnak.
- **EasyMDE:** az admin md-szerkesztőjének karakterszámlálója AA-kontrasztú.
- **Fejlesztői eszközök:**
  - Képernyőkép-túra 42 fő oldalról (390 és 1280 px, `--group=screenshots`).
  - Konvenció-teszt a nézetekre (`tests/Unit/ViewConventionsTest.php`): nincs árnyék, csak márka-sarokív és -betűvastagság, nincs Unicode-ikon, a fehér kártya `x-ui.card`. A kivétellista lépésenként fogy.
- **Verzió:** az 1.17 a configban 1.16 maradt; most 1.18.

## 1.17 · 8c43184 · 2026-10-20

Értéktükör: illesztés v3 (`ertektukor-illesztes` v3).

- **Illesztés, nem teljesülés:** az illesztés arról dönt, melyik értékről szól a szabad érték, nem arról, mennyire teljesül. A gyengén látszó vagy csak kimondott értékek is illeszkednek, a szintet a pontozás adja meg („2 · csak szavak”). A tesztszövegben az „Egészségtudatosság” háromból egyszer helyett háromból háromszor illeszkedett az Egészségre.
- **Több érték egy idézetben:** ha egy idézet több értéket mutat, mindegyik illeszkedik, a legmagasabb konfidenciát az kapja, amelyikről elsősorban szól. A v2 eddig egyre szorított.

## 1.16 · b5a24a8 · 2026-10-20

Az 1.15 staging-újratesztje után.

- **Beszélgetés-kinyerés:** a sorba állított job és a „Válasz és tovább” ugyanazt a választ egyszerre küldhette az AI-nak, így 2 válasz 4 hívásba került. A kinyerés most válaszonkénti zárral fut: a második hívó megvárja az elsőt, és már kinyertnek találja a választ.
- **Logó (A6):** asztali nézetben valóban egy logó; az 1.14-es `desktop:hidden` a logó rétegen kívüli `display` szabálya alatt nem hatott. Böngészős teszttel.
- **AI-egységárak:** a `megis:prices:sync` a LiteLLM árlistájából tölti az `ai_model_prices` táblát (naponta 03:45-kor és minden deploykor, `update.sh`); a `--backfill` beárazza az ár nélkül naplózott hívásokat. A `config/ai.php` ára továbbra is felülírja. A Rendszer → AI mutatja az utolsó frissítést. Új tábla: deploykor migráció kell.
- **Beérkező:** a menü és a kert számlálója azt számolja, amit a lista alapból mutat (a saját és gazdátlan szervezetek tételeit).
- **Tagok listája:** a „Belépés a nevében” mindig ott van az ügyfélfiókoknál; ha nem működhet, szürke, és a súgó megmondja, miért (deaktivált fiók, inaktív a szervezetben, még nem regisztrált, a tagság még nem aktív).
- **Admin menü:** új Szervezetek csoport (Szervezetek, Felhasználók: az eddigi Ügyfelek lista); a Termékek és az Admin felhasználók a Beállítások alá kerültek.

## 1.15 · 693987b · 2026-10-20

A staging-teszt után eldöntött kérdések.

- **Értéknév (K1):** ha a beírt név egy nem választható Értéktár-értéké (pl. egy másik szervezet még át nem nézett AI-értéke), a saját vagy szervezeti érték arra az értékre mutat, a felhasználó saját szövegeivel; abból az értékből semmi nem látszik. Eddig „Ilyen nevű érték már van az Értéktárban, de nem választható.” állt, kiút nélkül.
- **Legnagyobb rés küszöb alatt:** 3 kitöltő alatt a V12, az összefoglaló és a PDF nem nevez meg sort, a kártyán csak a szürke „Még N társ hangja kellett volna…” áll; egy sor sem kap „legnagyobb rés” jelet.
- **Mérőpont (A5):** a V10 futás közben a mérendő pontokat, olvasattól a pontozottakat számolja, mint a Kert és az R0.
- **V1:** a „Kezdjük” nézet marad, amíg a vezető maga nem végzett el egy lépést; az admin által kitöltött szervezeti értékek és márka egyedül nem váltják V1·b-re.
- **A1:** 3-nál kevesebb társnál jelzés a társaknál és az előnézetben: nem lesz csapat-átlag és rés. Indítani így is lehet.
- **Magánszemély V4:** egyes számú kérdések és példák („Mit mond rólad a fa?”, „Miért csinálod?”).
- Marad: a társ csak a rutinokat látja, amelyekben résztvevő (K2); az Útravaló kártyáinak számozása.

## 1.14 · ea800c1 · 2026-10-20

A staging-teszt (`docs/staging-teszt-2026-10-19.md`) javításai.

- **Blur-mentés (B1, S2):** Livewire 4-ben a `wire:model.blur` `.live` nélkül nem küld kérést, így a mezőből kilépéskor mentő hookok nem futottak. Üres márkával a V4 nem volt lezárható (minden magánszemély és minden előre kitöltött márka nélküli szervezetvezető elakadt), a Profil névmódosítása nem mentődött. `wire:model.live.blur` a V3, V4, V6·c és a Profil mezőin; böngészős teszttel.
- **Feltöltés (B2):** a `wire:submit="upload"` a Livewire saját `$wire.upload()`-ját hívta, a komponens metódusa sosem futott. Nem lehetett jogi dokumentumot, folyamat-alkalmat és V11-es fájlt feltölteni, a „Mégse” (`cancelUpload`) sem zárt. Új nevek: `storeVersion`, `closeUpload`, `storeOccasion`, `storeUpload`; arch-teszt tiltja a `$wire` feltöltő-neveit.
- **Küszöb (B3):** a küszöb alatt indított olvasat nem volt publikálható, mert a „Csapat-szám nélkül megy” döntés csak futás közben, a publikálás előtti héten látszott. A Futás fülön most futás és olvasat alatt is elérhető, ha a küszöb hiányzik; az „Olvasat indítása” küszöb alatt figyelmeztet.
- **Beszélgetés (S1):** a „Válasz és tovább” a még feldolgozás alatti válaszok folyamatait elhagyta. Most előbb kinyeri őket; ami a lezárás után érkezik, az is folyamat lesz; a már kinyert választ a job nem küldi újra az AI-nak.
- **Olvasat-szövegek (K5):** `olvasat-szovegek` v2: erősséget, gyengeséget, rést csak PHI-vel rendelkező sorról állít, a puszta megélésről legfeljebb „így látjátok”.
- **Mobil Értéktár (K4):** a kártya nem lóg ki telefonon; a pontszám és a „Részletek” a szöveg alá kerül.
- **Admin:** a státusztörténet a szakaszváltást mutatja („Vázlat → Meghívva”, A3); küszöb alatt a legnagyobb rés választójában nincs rés-szám (A4); azonos nevű szervezetek a választókban a címükkel (A7); az újrapublikálás kérdése nem ígér e-mailt (A9); a jóvá nem hagyott állítás az előnézetben „vázlat” (A10); az Adatok és Értékek fül `h1`-et kap, minden admin oldalra teszttel (A11); az A1 névsúgója alanyfüggő (K6).
- **Ügyféloldal:** egy logó asztali nézetben (A6); a V11 „Feldolgozás alatt · N” csak a nyitott tételeket listázza (A8).
- **Egyéb:** az admin belépés oldalain nincs `$persist` JS-hiba (A1); a `{material}` route-ok csak uuid-ot fogadnak, 500 helyett 404 (A2); a demó-seeder valódi hozzájárulás-szöveget ír, a tartalék „Demó ·” értékei a demó szervezetéi (A12); a tesztek AI- és PDF-beállítása független a helyi `.env`-től.

## 1.13 · 3852f47 · 2026-10-19

Értéktükör: az illesztés csendben üres választ kapott (Értéktükör teszt 1).

- **Gyökérok:** a Gemini a gondolkodási tokeneket is a `max_tokens`-be számolja. Az illesztés válasza a 4096-os plafonnál csonkolódott, a csonka JSON-t az SDK üres válaszként olvasta, a hívás mégis „sikeres” lett, így egyetlen detekció sem illeszkedett, és minden szabad név „AI által detektált” értékként került az Értéktárba.
- **AI-réteg:** a tokenlimitnél csonkolt vagy nem olvasható strukturált válasz sikertelen hívás, a job újrapróbálja, végül riaszt. A napló a finish reasont is mutatja. Ez minden AI-feladatra érvényes.
- **Tokenlimit:** az Értéktükör három lépése 16 384 tokenig mehet (eddig 4096). Ez plafon, a költség a ténylegesen generált tokeneken múlik.
- **Illesztés:** nincs csendes kilépés. Üres katalógusnál figyelmeztetés és riasztás jön, a tükör illesztés nélkül fut le. Ismeretlen detekciókódnál vagy értékkódnál figyelmeztetés jön az AI-hívás azonosítójával, és az is naplózva van, ha egy batchben semmi sem éri el a küszöböt.
- **Katalógus:** a felülvizsgálatlan „AI által detektált” értékek nem illesztési célpontok, különben egy szabad név a saját korábbi másolatára illeszkedne. Ha a szervezet az értékszettjébe vette, marad.
- **Detektálás:** `ertektukor-detektalas` v2. A névpéldák kimaradtak, mert a modell átvette őket. A gyengén látszó értékeket és azt is felveszi, amit a beszélő fontosnak mond, de a tettei mást mutatnak (1–2). A mások szavaiként idézett értékeket nem veszi fel.

## 1.12 · 69156e5 · 2026-10-19

Ügyféloldali fejléc és a vezető első belépése a prototípus szerint.

- **Menü mobilon:** 1200 px alatt a menü nem csúszott be (a `translate-x-full` felülírta a `translate-x-0`-t), így a menü és a Kijelentkezés elérhetetlen volt. Javítva, böngészős teszttel telefonméretben.
- **Fejléc:** a logó mellett a lapcímke („Első belépés”, „3 / 6”, „Diagnózis”, „Kezdőlap”), az avatár a user menüt nyitja (név, szervezet és pozíció, Profil, Kijelentkezés), a menüt egy külön kerek gomb. A fejlécben a cím előtt szimbólum és a cím alatt két sor is állhat.
- **V1, V1·b, T2:** a spirál, a „Mi vár rád”, a „6 lépés, és indul a diagnózis.” és a „kb. 13 perc · 03.02.-ig” sor a fejlécbe került; a lista alatt egymás mellett a „Kezdjük →” és a „Bővebben”. A társ „Kezdem” gombja után a lap újratöltődik, így a T2 a saját fejlécével nyílik.
- **Kezdőlap:** aki a szervezet egyik elindult diagnózisában sem vesz részt (pl. szervezeti vezető, amikor más vezeti a diagnózist), annak „Jelenleg nincs olyan diagnózis, amelyben részt veszel.” A „…amint elindul” szöveg csak akkor marad, ha még nem indult diagnózis, vagy a társ a meghívójára vár.

## 1.11 · 41fb0fe · 2026-10-19

PDF WeasyPrinttel is, Docker nélküli környezetekre (staging).

- **PDF-motor:** `LARAVEL_PDF_DRIVER=weasyprint` mellett a riport és az Útravaló PDF-je a szerver WeasyPrint-binárisával készül (`pontedilana/php-weasyprint`, `LARAVEL_PDF_WEASYPRINT_BINARY`). A Geologica fontot rendszerfontként kell telepíteni. Élesben a Gotenberg marad az alap.
- **Setup check:** a PDF-motor sora WeasyPrintnél lefuttatja a binárist (`--version`), és hibát jelez, ha nem fut. Más drivernél továbbra is csak figyelmeztet.
- **PDF-layout:** a fejléc és a lábléc WeasyPrintnél is teljes szélességű, a tartalom széléhez igazodik, a dátum és az oldalszám jobbra kerül, a magassága pedig egyezik a Gotenbergével. Mindkét motornál: a dobozok és a táblázatsorok nem törnek ketté, a címek nem maradnak egyedül az oldal alján.

## 1.10 · ca1ac9c · 2026-10-18

Az Értéktár a mégis 58 értékével (`docs/data/ÉRTÉKEK KITÖLTÖTT.xlsx`).

- **Értékkártya:** azonosító, angol név, definíció (magyar, angol), hamis pozitívok, átfedések más értékekkel, szakirodalom, szakirodalmi alátámasztottság, transzponálhatóság, SVS-index. A kategória Schwartz alapértéke angol névvel és felsőbb szinttel.
- **Betöltés:** a `megis:values:import` a mégis táblázatának fejléceit is ismeri, a listákat sortörésnél vagy „ · ”-nál bontja. Az értéket az azonosító, ennek hiányában a név alapján frissíti: változásnál új verzió megjegyzéssel, a parancs mezőnként kiírja a változást, a dry-run pontosan megmutatja. Csak a fájl oszlopai íródnak felül. Új minta: `docs/ertektar-import-minta.xlsx`.
- **Admin:** az új mezők szakaszokra bontott űrlapon; szűrés felsőbb szintre, alátámasztottságra és SVS-indexre; keresés angol névben és azonosítóban; a verzió-összevetés a minősítéseket címkével mutatja.
- **Ügyféloldal:** az Értéktárban a név alatt a definíció; a hamis pozitívok, a szakirodalom és a minősítések az adminé.
- **Értékmérés:** az illesztés és a pontozás a definíciót, a hamis pozitívokat és az átfedéseket is megkapja (`ertektukor-illesztes` v2, `ertektukor-pontozas` v2). Más értékek angol neve helyett a kódjuk megy, így az illesztés továbbra sem név alapján dönt.
- **Verziók:** a később felvett, üres mező nem számít változásnak, így a meglévő értékek nem kapnak üres új verziót.

## 1.00 · 8618d4d · 2026-10-15

M7 · Stabilizálás: az első éles kiadás jelöltje. Az élesítés feltételei a `docs/deploy.md` „Élesítés előtt (1.00)” listáján.

- **Akadálymentesség:** böngészős smoke teszt a riport teljes útjára (R0 → Melléklet, Bölcsességtár) és az admin lapjaira (olvasat, publikálás, Képességtár, Beérkező, diagnózisok, ügyfelek, szervezet fülei, tárak, beállítások), axe-ellenőrzéssel. Javítva: a korall szöveg kontrasztja világos háttéren (új `coral-ink` szín, 5,7:1) és sötét kártyán (`coral-light`), a másodlagos szürke szöveg (`grey-600`: #747474 → #666666), az olvasat md-szerkesztőjének címkéje és súgója.
- **Teljesítmény:** a hiányzó indexek a lekérdezett és kaszkádolt idegen kulcsokon (PostgreSQL nem indexeli őket magától); az N+1 lekérdezések éles környezeten kívül hibát dobnak, stagingen naplóba kerülnek. Javítva: a diagnózis indítása, a folyamatok elfogadása, a megnyitás, a soronkénti és blokkonkénti jóváhagyás, az olvasat naplója és a Beérkező napi átnézése.
- **Biztonság:** biztonsági fejlécek minden válaszon (nosniff, SAMEORIGIN keretezés, referrer- és engedély-szabály, élesben https-en HSTS); a markdown e-mailekben a nevek, idézetek és válaszok szövegként maradnak, nem lesz belőlük link. `composer audit` és `npm audit`: nincs ismert sérülékenység.
- **Demó:** `DemoDiagnosisSeeder` a staging-elfogadáshoz (Teljes, 1 vezető, 4 társ, folyamatok, anyagok).
- **Dokumentáció:** élesítési ellenőrzőlista, a demó és a Képességtár betöltése az `install.md`-ben.

## 0.70 · 06c64b1 · 2026-10-14

M6 · Olvasat, publikálás, riport (20, 27, 29, 21, 26.3, 28, 32, 30, 31, 33, 07).

- **Olvasat (20):** *Olvasat* fül három oszloppal (blokkok és sorok · a sor részletei · napló); jóváhagyás soronként és blokkonként; átírás kötelező indoklással; a területek és a fa kézi pontozása egész pontokkal; „nincs elég adat”; nyomok kizárása, kiemelése, kézi hozzáadása és másolása; állítások vázlat → jóváhagyott állapottal, a sor nyomaira építve; md szövegek EasyMDE-vel (160, a márkád olvasata 600 karakter); a legnagyobb rés jóváhagyása; haladás-sáv. Ha egy jóváhagyott sor automatikus értéke változik, a sor „vár” állapotba esik, a napló megmondja, miért.
- **Riport (27):** R0 válasz, R1 összefoglaló gyűrűvel, R1·b belső kép, R2–R5 fejezetek (értékek, folyamatok, területek, márkád alapja) tételekre mélyítve, összehasonlító nézet (R1·c), zárás, Melléklet; nézetváltó (Csapat · Szervezet · Egyén); a Folyamatok fejezet kimarad, ha nincs folyamat; Riportok a menüben. A riport a fagyasztott pillanatképből olvas, a vezető és a szervezet vezetői látják.
- **Útravaló (29):** a társ négy blokkja automatikusan, skálával, a küszöbök alatt (2 helyzet, 3 másik kitöltő) rejtve; PDF-ben is.
- **Publikálás (21):** *Publikálás* fül ellenőrzőlistával, előnézettel telefonon és asztalon (iframe, társ-választóval), publikálás → fagyasztott `report_snapshots`, e-mail csak az első publikáláskor; javítás „frissítve · dátum · ok” jelzéssel; visszavonás indoklással (Olvasat szakasz, Draft státusz).
- **Kezdőlap (26.3):** V12 és V12·b a riporttal, Értékmérés-teaser, Útravaló a társ kezdőlapján.
- **Visszajelzés, Melléklet, PDF (28):** fejezetenkénti visszajelzés diktálással → Beérkező sor „Beépítem” (az olvasatra visz) és „Válaszolok” (e-mail) gombbal; a riport és az Útravaló PDF-je a pillanatképből, Gotenberg-gel.
- **Bölcsességtár (32):** BT1–BT4 és BT6 md-fájlokból (`resources/content/hu/bolcsessegtar`), „Nálatok:” sor a saját riport számával; „Bővebben” linkek.
- **Értéktár, Profil, adatlap (30, 31, 33):** a szervezet értékei mellett a legutóbbi riport pontja és a korábbi definíció jelzése, „Hozzáadom a sajátjaimhoz”; Profil: a diagnózisod állapota, Rád vár (legfeljebb 3 sor), Emberek emlékeztetővel, Adataim törlése jelszóval vagy kóddal; óránkénti végleges törlés névtelen sírkővel, a publikált riport idézetek nélküli javításával; az ügyfél adatlapján diagnózisok, részdiagnózisok, hozzájárulások, törölt felhasználónál csak a napló.
- **Képességtár (07):** *Képességtár* menü (Képességek, Csoportok, Frissítés a mégis tárából); a 12 munkacsoport és az E1–E9 előfeltételek kezdő adatként; adatlap a 7.2 mezőivel, 1000 karakteres listaelemekkel, előfeltételekkel és képesség-függőséggel, csak olvasható részletekkel; verziók megjegyzéssel és összehasonlítással; archiválás; betöltés a Sheet xlsx-exportjából előnézettel (új · változott · változatlan, mezőnként), `[JAVASLAT]` jelöléssel, a „nincs még” és a többi üres érték állapotával, a „félretett” archiválva. Csak a Képesség és a Képesség-részletek fül jön be.
- **Verziók:** a verzió megjegyzést kaphat (mi változott és miért); az Értéktár és a Képességtár verziótörténete közös komponens.
- **Ütemezett feladat:** `purge:deleted-accounts` (óránként). Új setup-check sor: PDF-motor.
- **Új csomagok:** `spatie/laravel-pdf` (Gotenberg), `easymde` (npm, csak az olvasat lapján töltődik be).

## 0.60 · 1dbea23 · 2026-10-14

M5 · Futás (12, 13, 18, 19, 22, 26.1–26.2, 26.4).

- **Alkalmak (12):**
  - V11 Hozzáteszek: Értéktükör, „Egy helyzet” elmondva vagy leírva, feltöltés egy dobozban (hang, txt, md, docx, pdf), feldolgozás-lista állapotokkal, „Újra” és „Kiveszem”;
  - V11·b Hova tartozik: a jogosult mért folyamatok (társnak csak, amelyben résztvevő), a „nem mérjük” szaggatottan, a naptár legutóbbi alkalma előre kijelölve, dátum, felvétel-hozzájárulás (ki és mikor), „Egyikhez sem”;
  - V11·c A megélésed: soronként 1–6 vagy „nem tudom”, eredményesség, megjegyzés diktálással, „Beküldöm” vagy „Kihagyom”;
  - T5·b A helyzeteid: időrendben, „megélés hiányzik” linkkel, a saját hang törölhető;
  - admin: „Besorolásra vár” blokk a *Folyamatok* fülön, folyamatonkénti alkalom-lap az anyagokkal és a megélések számával, „+ Anyag”.
- **Folyamat-diagnózis (13):** Folyamat szintű részdiagnózis folyamatonként; alkalmanként egy pont az anyagok átlagából; n, szórás, minimum (Teljes 2, snapshot 1 „Egy alkalomból” jelzéssel), alfolyamat a szülőbe; megélés vezető és társak szerint; eredményesség-összesítés; kézi átírás kötelező indoklással, az automatikus érték mellette; alkalmak listája pontokkal és idézetekkel; összesítés-leírás; „N új alkalom a publikálás óta”.
- **Számítás (18):**
  - a fő diagnózis négy blokkja: értékek az összes anyagból, folyamatok a folyamat-diagnózisból, területek (Teljes) és a fa kézi pontozásra, megélés és rés;
  - gyűrű-%, lépcső és pont a kerekítetlen értékekből, „–” sorok nélkül; a legnagyobb rés; magánszemélynél és egy embernél az önkép és a helyzetek rése;
  - újraszámolás egyedi, késleltetett jobbal: feldolgozott anyag, megélés, visszavont hozzájárulás, indítás, vezetőcsere, kézi átírás;
  - küszöb (3 társ) és a „csapat-szám nélkül megy” döntés; nézetek adatai (Csapat · Egyén · Szervezet);
  - olvasat indulásakor AI-vázlat az állításokhoz (`diagnozis-allitasok` v1) és az olvasat szövegeihez (`olvasat-szovegek` v1), nevek nélkül; az admin munkája megmarad;
  - *Beállítások → Területek*.
- **Futás (19):** a fő diagnózis fejléce („23. nap / 47”, kapcsolattartó) és *Futás* füle a kapcsolattartónak és a szuperadminnak; mindig a szakasz füle nyílik; kert-nézet szakasz-oszlopokkal, coral kerettel és szűrőkkel, a lista váltóval; Futás lap: időablak, vezető, társak, folyamatok, anyagok, naptár; küszöb-sáv (Szólok · Várok 5 napot · Csapat-szám nélkül megy); emlékeztetők 48 órás korláttal; időablak-módosítás e-maillel; olvasat indítása; beszélgetések a mégissel.
- **Beérkező (22):** napi átnézés (meghívó 5 napja vagy lejárt, kitöltés a kezdés napjáig, küszöb, publikálás napja), automatikus lezárás, soronkénti gombok (besorolás, újra, emlékeztetem, új meghívó, → Futás), 8:00-s összefoglaló a kapcsolattartóknak, azonnali e-mail foglalásnál.
- **Kezdőlap (26):** V10 és V10·b hetekkel vagy napokkal, onboarding, társak „k / n kitöltötte” és vezetői emlékeztető, beszélgetések, „Amit hozzátesztek”, olvasat és riport dátuma; olvasat alatt csak olvasható; coral plusz a V10-en és a T5-ön; a kezdőlap a vezetett, majd a társ-diagnózist nyitja (26.4).
- **Ütemezett feladatok:** `inbox:scan` (07:30), `inbox:digest` (08:00).
- **Javítás:** a véletlenül verziókezelt `megis_dev` SQLite-fájl kikerült, és a `.gitignore`-ba került.

## 0.50 · 225e376 · 2026-10-14

M4 · Fő diagnózis indítása és kitöltés (17, 23, 24, 08 kliens, 11, 10, 09, 25, 31.5–31.6 részben).

- **Fő diagnózis (17):**
  - A1 élő előnézettel: szervezet, kapcsolattartó, alany (csapat, egy ember, magánszemély), csapat vagy új csapat, vezető vagy új vezető, társak kivehetően, időablak, idézet-kapcsoló;
  - a termék a kulcsa szerint adja a lépéseket (`config/diagnosis.php`), és indításkor a diagnózisra mentődik;
  - szakaszgép (Vázlat → Meghívva → Kitöltés → Fut → Olvasat → Publikálva, bárhonnan Lezárva) naplóval; a szakasz adja a státuszt, a részdiagnózisok követik;
  - részdiagnózisok: Értéktükör (6.2, több futó diagnózisnál kérdez), megélés, kézi és feltöltött diagnózis a „Fő diagnózis” mezővel; *Részdiagnózisok* fül;
  - vezetőcsere megerősítéssel, időablak és társak módosítása az olvasatig; aki futás közben a csapatba kerül, társ lesz;
  - szakasz oszlop és szűrő a Diagnózisok listán; *Diagnózisok* és *Folyamatok* fül a szervezeten.
- **Kitöltés kerete (23):** V1 és V1·b lépéslistával, percekkel és „A végén” képpel; lépésszámláló a fejlécben; „Mentve” visszajelzés; csak olvasható lépés; V9 „Indul a diagnózis”, a szervezet értékszett-verziójának rögzítésével.
- **Saját értékek (24):** V2 három úttal (Értéktükörből, Elmondom, hozzáadás vagy választás), V2·b kártyákkal és állapottal (erősen jelen · részben · még nincs beépítve), V2·c; „Felhasználó által felvett” Értéktár-eredet; verzió a lépés elhagyásakor; magánszemélynél a szervezet értékei is.
- **A szervezet értékei és a márkád alapja (08):** V3 3–5 értékkel és „a tiéd is” jelöléssel, V4 négy dobozzal, V5 megélés 1–6 vagy „nem tudom” skálával → Megélés részdiagnózis a rögzített verzióval.
- **Folyamatok (11, 10, 09):**
  - V6 belépő, V6·d detektált lista négy csoporttal (átnevezés nyommal, típusváltó, alfolyamat, elfogadás, kézi hozzáadás), V6·e adatlap (felelős, résztvevők, csapatok, saját mérőszám értékekkel), V6·f összegzés; Snapshot · Belsőben legfeljebb 2 mért folyamat;
  - beszélgetés négy kérdéssel, válaszonként AI-kivonattal, chipekkel és töréspont-jelölttel (`beszelgetes-kivonat` v1);
  - naptár: iCal link, ICS fájl, Google és Microsoft OAuth csak olvasási joggal, titkosított hozzáféréssel; szabályalapú mintakeresés és AI-névjavaslat nevek és e-mailek nélkül (`naptar-elnevezes` v1); összevonási javaslat a beszélgetés folyamataival; napi frissítés, 90 napos metaadat, szétkötés;
  - Tárak → Folyamatok; admin *Folyamatok* fül naptár-sorral, átnézendő blokkal és „a mégis javaslata” szöveggel.
- **Társ útja (25):** meghívó „Fut”-kor, T1 hozzájárulással (szövegváltozattal, naplóval), T2, T3, T4 a rögzített verzióból, T5 kezdőlap; Profil → Hozzájárulásaim és visszavonás a publikálásig (31.5, 31.6).
- **Diktálás:** a szöveges mezők mikrofonnal is kitölthetők (V3, V4, V6·c).
- **Belépés (14.4, 26.4):** a vezetett, majd a futó társ-diagnózis nyílik; a szervezet kezdőlapja a diagnózisokat listázza.
- **Ütemezett feladatok:** `sync:calendars`, `prune:calendar-metadata`. Új setup-check sor: Naptár-összekötés.
- **Új csomagok:** `sabre/vobject`, `laravel/socialite`, `socialiteproviders/microsoft`.

## 0.40 · 5d398e9 · 2026-10-14

M3 · Értékmérés-motor és Értéktükör (06, 12.7 előkészítés, 30 alap, 31.5 részben).

- **Értékmérés-motor (06 · 6.4):**
  - A: szabad detektálás értéklista nélkül, hosszú átiratnál darabolva;
  - B: illesztés az Értéktár név nélküli, csak jelentésből és példákból álló listájára;
  - C: pontozás „nincs adat” vagy 1–6, idézet-hivatkozással;
  - nyers detektálás minden eredménnyel és AI-hívással; a nem illeszkedő értékek „AI által detektált” értékként kerülnek az Értéktárba, a névszabályokkal;
  - promptok: `ertektukor-detektalas`, `ertektukor-illesztes`, `ertektukor-pontozas` (v1).
- **Nyilvános Értéktükör (06 · 6.1):**
  - ET1 nyitó a kezdőlapon;
  - ET2 böngészős felvétel számlálóval, telő körrel, biztató szövegekkel, csendnél támogató kérdéssel; szöveg vagy fájl is;
  - ET3 értékek idézetekkel és „nem az enyém”; ET5 teljes kép kíváncsiság-kérdésekkel;
  - fiók e-mail-megerősítéssel, magánszemély-szervezettel és a jogi elfogadások rögzítésével; meglévő fiók helyben belép jelszóval vagy kóddal;
  - „Időpontot foglalok” Beérkező-sorral; Turnstile, IP-korlát; a névtelen tükör 24 óráig él, utána csak névtelen statisztika marad.
- **Értéktükör a saját menüből (6.2):** a diagnózis az aktív szervezethez kerül.
- **Automatikus diagnózis (6.5):** Értéktükör típus, Automatikus, Jóváhagyásra vár; a sorokon idézetek, „Egyéb detektált értékek”, a forrás átirata és hangja az admin oldalán.
- **Admin feltöltés (6.3):** hang vagy leirat, beszélők felismerése és hozzárendelése, beszélőnként felhasználói diagnózis.
- **Hangfelvételek (6.6, 31.5):** privát tárhely, admin lejátszás, törlés a Profil Felvételeim blokkjából vagy az adminból; csak a hang törlődik.
- **Támogató kérdések (6.7)** rendezéssel és aktív/inaktív kapcsolóval.
- **Ügyféloldali Értéktár (30 alap):** a szervezet értékei a saját definíciójukkal; az Értéktár böngészője keresővel és kategóriaszűrővel.
- **Átírás:** `Transcriber` interfész a `laravel/ai`-ra építve; minta-átíró helyi munkához; `megis:transcription:compare` a 12.7 PoC-hoz.
- **Ütemezett feladat:** `prune:mirror-sessions` óránként. Új setup-check sor: Átírás.

## 0.30 · 8256e0e · 2026-10-14

M2 · Tárak, értékszett, AI-réteg, kézi diagnózisok (03, 08 adatmodell, 04, 16, 22 alap).

- Értéktár (03):
  - értékek listája keresővel (név, szinonimák), szűrőkkel, rendezéssel; kategóriák;
  - érték-űrlap a lista-szerkesztővel (húzás és nyilak), slug, kötelező 1 és 6;
  - verziók minden változással, csak olvasható régi verzió, két verzió összehasonlítása (hozzáadott, törölt, áthelyezett elemek, szöveg szószinten);
  - archiválás, visszaállítás, „Átnézve”, eredet-címkék (03 · 3.7);
  - `megis:values:import`: a kezdő értékek betöltése táblázatból.
- Diagnózisok (04):
  - típusok; lista; diagnózis szervezet, csapat vagy felhasználó szintre, dátummal vagy időszakkal;
  - értékek az Értéktárból a használt verzióval, vagy név szerint új „Diagnózisnál felvett” értékként; a márkád alapja négy sora;
  - mérőszám (1–6 vagy „–”), saját megélés (1–6 vagy „nem tudom”), példák, indoklás, rés;
  - státuszok (Draft → Jóváhagyásra vár → Publikált, visszavonás) naplóval;
  - aggregált adatok betöltése előnézettel, leírással, n-nel, szórással, figyelmeztetésekkel.
- A szervezet értékei (08): adatmodell és verziók; admin *Értékek* fül 3–5 értékkel és a márkád alapjával.
- AI-réteg (16): `AiGateway` feladatonkénti konfigurációval, verziózott promptokkal, `ai_calls` naplóval (pontos modell, tokenek, költség); AI-jobok 3 próbával; Rendszer → AI használat (CSV-vel), AI konfiguráció, Promptok; setup-check sor.
- Beérkező (22 alap): sorok deduplikálással, lezárással és automatikus lezárással; lista szűrőkkel, „Lezártak”, számláló a menüben.
- Fejlesztői adatbázis: AWS RDS (PostgreSQL 14).

## 0.20 · 8015e33 · 2026-10-14

M1 · Identitás, szervezetek, belépés, keret (01, 02, 05, 14, 15, 31 alap, 33 alap).

- Admin (01):
  - `megis:admin:create` és `megis:admin:unlock`;
  - belépés, zárolás 10 hiba után, riasztás a többi szuperadminnak, 6 órás inaktivitási korlát;
  - meghívás, jelszó-visszaállítás, inaktiválás; minden jelszócsere és inaktiválás kilépteti a többi munkamenetet;
  - Adminok lista, profil, Rendszer menü (szuperadmin) a Health lappal.
- Szervezetek (02):
  - termékek; szervezetek slug-gal, magánszemély-szabályokkal és létszám-limittel;
  - csapatok csapatvezetőkkel;
  - tagság címkékkel és pozícióval; „Felvéve” → „Meghívó küldése”; meglévő felhasználó értesítése;
  - a szervezet fülei (Adatok, Csapatok, Felhasználók); a többi fül a saját jegyével jön.
- Jogi dokumentumok (05): verziók hatályba lépéssel, „újra el kell fogadni” kapcsolóval, állandó URL-ek, setup-check sor.
- Ügyféloldal (14, 15, 31):
  - meghívó elfogadása (14 nap) a jogi dokumentumokkal;
  - belépés kóddal vagy linkkel (15 perc), jelszóval opcionálisan; rate limitek űrlaphibaként;
  - 30 napos gördülő munkamenet; újra-elfogadás kérése; kezdőlap-választás az utolsó szervezet szerint;
  - fejléc, menü, szervezetválasztó; PWA (manifest, service worker, offline lap);
  - Profil: név automatikus mentéssel, jelszó beállítása és cseréje.
- Belépés a felhasználó nevében (14.6): külön munkamenet, coral sáv, naplózott változtatások, tiltott jelszócsere és elfogadás.
- Ügyfelek (33 alap): lista, adatlap szervezetekkel, elfogadásokkal és naplóval.
- Minimális nyilvános kezdőlap jogi linkekkel a láblécben.
- Ütemezett feladat: lejárt tokenek takarítása (`prune:user-tokens`).

## 0.10 · 8015e33 · 2026-10-14 (az M1-gyel közös commit)

M0 · Alapozás.

- PHP 8.4, Livewire 4 (class-alapú komponensek, Alpine a saját bundle-ből), S3-flysystem.
- Magyar nyelv és budapesti idő alapból, magyar validációs és rendszerüzenetek.
- Design system: a mégis tokenjei a Tailwind témában. Saját hostolt Geologica. Ikonok, szimbólumok és logók SVG-ként.
- Public layout SEO- és Open Graph-tagekkel. Brandelt hibalapok (403, 404, 419, 429, 500, 503). Ideiglenes kezdőlap.
- Üzemeltetés:
  - log-csatornák (app, auth, ai, mail, queue) érzékenyadat-szűréssel;
  - hibariasztás e-mailben, 15 perces deduplikálással;
  - `GET /health` heartbeattel;
  - `php artisan megis:setup:check`.
- Staging-védelem (jelszó vagy IP), noindex nem éles környezetben, levél-allowlist, `robots.txt` és `sitemap.xml`.
- Turnstile-ellenőrzés és validációs szabály. A foglalt slugok listája.
- Dokumentáció: `install.md`, `deploy.md`, `cron.md`, `changelog.md`.
