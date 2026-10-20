# Változásnapló

A legfrissebb verzió van felül. Formátum: `## {verzió} · {commit-hash} · {commit dátuma}`.

## 1.25 · {hash} · 2026-10-21

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
- **Futás (19):** a fő diagnózis fejléce („23. nap / 47”, kapcsolattartó) és *Futás* füle a kapcsolattartónak és a szuperadminnak; mindig a szakasz füle nyílik; kert-nézet szakasz-oszlopokkal, coral kerettel és szűrőkkel, a lista váltóval; Futás lap: időablak, vezető, társak, folyamatok, anyagok, naptár; küszöb-sáv (Szólok · Várok 5 napot · Csapat-szám nélkül megy); emlékeztetők 48 órás korláttal; időablak-módosítás e-maillel; olvasat indítása; beszélgetések a mégisszel.
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
