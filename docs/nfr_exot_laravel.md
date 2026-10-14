
# Globális NFR

A Boost-ben már lefedett technikai irányelveket nem ismételjük itt.

A projekt specifikus NFR, ami ezt kiegészíti: docs\nfr_project.md 

# Tech stack

- Laravel 13 +
- PHP 8.3 
- PostgreSQL
- Redis

# 1. Dokumentáció

- install.md: lokális telepítés, szükséges verziók, indítás, build, teszt parancsok.
- deploy.md: release lépések, konfigurációs pontok, rollback lépések.
- changelog.md: rövid, verzióhoz kötött változáslista.
- readme kiegészítés: projekt-specifikus döntések, külső integrációk.
- cron.md az ütemeett háttér folymatoknak 

# 2. App verziózás

Mindig verziót kell emelni:
- új nagy funkció esetén: például 1.11 => 1.20
- kisebb módosítás, vagy kisebb, közepes új funkció esetén: például 1.20 => 1.21
- kisebb javítás 1.211

Főverziót csak kézzel emelünk.

Kiadás előtt kötelező:
- changelog frissítés,
- verziószám egységes frissítése,
- rollback verzió azonosítása.

Admin felület menü alsó részébe minidg látszódjon az aktuális app verzió 

# 3. Design és UI egységesség

Admin felület: Laravel 13 + Tailwind.

Közös UI alapelvek:
- Minden admin listán az oszlopok alapból rendezhetőek, szűrhető az oszlopok értékeire. A lista végén, entitás nevére megynitható szerkesztésre, a lista végén lehetséges hamburger menübe több funkció
- azonos űrlap viselkedés (hibaüzenet, validáció, siker visszajelzés),
- azonos gomb- és input-stílus tokenek,
- mobil első (responsive) működés a publikus felületeken. 
- minimum AA szintű hozzáférhetőség kritikus felületeken.
- formoknál kép vagy file feltöltsnél írjuk ki a validációs szabályokat,pl: min képméret, opcinális arány
- inputok, ahol tudjuk hogy mit csinál ott az input alá vagy mellé jelenítsünk meg egy rövid magyarázó szöveget. Pl Aktív/inaktív kapcsolónál az inaktív nem jelenik meg az oldalon. 
- Képek feltöltésénél legyen kiírva mindenhova a minimum képméret és a kiterjesztés. Alapból png vagy jpeg legyen engedélyezve. 
- WYSWYG Editornak valamilyen md file formátumú editor legyen mindenhol ahol mást nem kérek
- Ahol "bármenyni szöveges feltétel hozzáadható, mint lista elem" Ott egy listát kell tudnia szerkeszteni, ahol egy-egy lista elem lehet akár egy szó is vagy egy-két mondat. Sorrendje beállítható drag and droppal
- Az admin felület egyedi Laravel Blad templatere és tailwindre épüljön. NE használjon filamentet. 
- WCAG 2.2* A szintű szabványnak minden oldalnak meg kell felelnie. 
- Admin felület user menüjébe látszódjon a jogosultság is. 

## Képek feltöltése

- Képfeltöltésnél az adminban az inputoknál legyen lehetőség törölni is a feltöltött képet, ha kép nélküli állapotot szereték beállítani. 
- Kiválasztáskor már kezdje meg a feltöltést ne csak mentéskor, mutasson preview-et is és egy loader ami mutatja hogy áll a feltöltés. 
- A képet feltöltéskor méretezzük maximum 1920px szélesre az arány megtartásával. Ezen kívül optimalizáljuk webre és úgy mentsük el. Mentéskor a fájl nevéből az ékezetek és sepciális karakterek tűnjenek el helyette - legyen. 
- Használjunk Media Library-t és ki lehessen választani újra a már feltöltött képeket. A feltöltés is media library-n keresztül egy modalba történjen. 

# 4. App lokáció 

- Az időzóna Budapesti idő legyen
- Az alkalmazás nyelve Magyar
- Az admin nyelve: Magyar
- Készüljünk elő arra hogy könnyedén többnyelvűsítéhető legyen 

# 5. Biztonság

Captcha kötelező:
- publikus formok
- bejelentkezés
- admin bejelentkezés

Cloudflare Turnstile legyen a szolgáltató

További kötelező elvárások:

- szerveroldali validáció minden bemenetre,
- jogosultság ellenőrzés minden módosító művelet előtt,
- rate limit beállítás belépésre és érzékeny végpontokra,
- bizalmas adatok csak környezeti változóban,
- dependency biztonsági audit rendszeresen.


# 6. Teljesítmény

Minimum célok:
- publikus oldalaknál gyors első betöltés,
- adatbázis lekérdezéseknél N+1 elkerülése,
- cache használat olvasás-intenzív felületeken.

Kötelező minden új funkciónál:
- teljesítményhatás rövid értékelése,
- szükség esetén cache vagy index terv.

SEO
- A sitemap-be kerüljön bele egy egy új funkció új publikus URL-je. Természetesen csak publikus oldal kerülhet bele. Nem production környezetre legyen noindex. 

# 7 DB

- A DB entitásaihoz, amik adnimból szerkeszthetőek legyen created, updated at és cretaed, updated by mező is. 

# 7. Megbízhatóság és üzemeltetés

Kötelező:
- egységes hibalogolás, több logfájlba
- logok rotálódjanak és törlődjenek  365 nap után 
- éles hibák monitorozhatósága,
- kell egy gyors health check endpoint is amit monitoring szolgáltatásba lehet kötni, ez ellenőrízze a függőségeket.
- setup check console command végzi a környezet beállításának vizsgálatát 

# 8. Tesztelés

A rendszerek legyenek automata tesztekkel tesztelhetőek.

Minimum elvárás minden projektnél:
- smoke tesztek a kritikus felhasználói útvonalakra,
- auth és jogosultsági tesztek,
- legalább egy hibás bemeneti teszt minden kritikus formhoz.

Merge feltétel:
- tesztek zöldek,
- kritikus regresszió nincs,
- review jóváhagyva.


# 9. SEO és tartalmi minimumok (publikus oldalak)

Kötelező:
- egyedi title és meta description,
- canonical használat,
- sitemap és robots beállítás,
- megosztási meta tagek (Open Graph minimum).


# 10. Release és minőségkapu

Minden release előtt kötelező checklist:
- verzió frissítve,
- migrációk ellenőrizve,
- tesztek lefutottak,
- kritikus oldalak kézi ellenőrzése megtörtént,
- rollback terv elérhető.

Élesítés után:
- rövid smoke check,
- log- és hibaszint ellenőrzés.

# 11. AI modell használat 

- AI Model által generált tartalmakat a tartalmakkal együtt hozzá kell kötni a modal pontos verziójához
- A modelek könnyen lecserélhetőek legyenek, egy config vagy amdin beállítással
- Mérje a rendszer a felhasznált tokeneket modellenként, időpontonként