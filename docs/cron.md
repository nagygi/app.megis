# Ütemezett feladatok

Az ütemező percenként fut: `* * * * * php artisan schedule:run`. A feladatok a `routes/console.php`-ban vannak. Mindegyik `onOneServer()`, így több szerveren is csak egyszer fut.

Minden jegy, amely ütemezett feladatot hoz, ide bejegyzi.

| Feladat | Gyakoriság | Mit csinál | Jegy |
|---|---|---|---|
| `heartbeat:scheduler` | percenként | Jelzi a health checknek, hogy az ütemező fut. | üzemeltetés · 3 |
| `heartbeat:queue` | percenként | Egy jobot tesz a queue-ra. Amikor a worker feldolgozza, jelzi a health checknek, hogy a worker él. | üzemeltetés · 3 |
| `prune:user-tokens` | naponta 03:00 | Törli a lejárt vagy felhasznált egyszer használatos linkeket és kódokat (meghívó, belépő kód, jelszó-beállítás) a lejárat után 30 nappal. | 01, 14 |
| `sync:ai-prices` | naponta 03:45 | Frissíti a használt AI-modellek egységárait a LiteLLM nyilvános árlistájából (`ai_model_prices`), és utólag beárazza az ár nélkül naplózott hívásokat. A már beárazott hívás költsége nem változik. Ha az árlista nem érhető el, a régi árak maradnak. Deploykor az `update.sh` is lefuttatja. | 16 · 16.4 |
| `sync:calendars` | naponta 04:00 | Frissíti az összekötött naptárakat (Google, Microsoft, iCal link) a diagnózis időablakának végéig: az elfogadott folyamatok új alkalmait felveszi, új mintát nem javasol. Az időablak végén automatikusan szétköt; megszakadt hozzáférésnél a kapcsolat „megszakadt” állapotba kerül. | 09 · 9.4, 9.5, 9.6 |
| `prune:calendar-metadata` | naponta 03:30 | Törli a naptár-metaadatot (cím, leírás, résztvevők) az összekötéstől számított 90 nap után. A folyamatok és az alkalmak dátuma megmarad. | 09 · 9.5 |
| `inbox:scan` | naponta 07:30 | A Beérkező napi átnézése: sort nyit, ha egy meghívót 5 napja nem nyitottak meg vagy lejárt, ha a vezető a kezdés napjáig nem indította el a diagnózist, ha a küszöb veszélyben van, vagy ha a publikálás napja elmúlt. A megszűnt okú sorokat automatikusan lezárja. | 22 · 22.2 |
| `inbox:digest` | naponta 08:00 | Minden kapcsolattartónak egy e-mail a saját szervezetei nyitott soraival. Ha nincs nyitott sor, nem megy levél. | 22 · 2. kérdés |
| `purge:deleted-accounts` | óránként | Az „Adataim törlése” után legkésőbb 24 órán belül törli a fiók személyes adatait (felvételek, átiratok, saját értékek, megélések, Értéktükrök, nyomok). A felhasználó sora névtelen sírkőként marad; a publikált diagnózisok idézetei kikerülnek, a fejezeten „frissítve” jelzés áll. | 31 · 31.7 |
| `close:impersonations` | tízpercenként | Lezárja a „nevében” munkameneteket, amelyekből az admin „Kilépés” nélkül ment el: ha 6 órája (az admin tétlenségi ideje) nem jött kérés a felhasználó nevében. A vége az utolsó kérés ideje, a naplóba „félbehagyva” kerül. | 33 · 33.6 |
| `onsite-comment:prune` | naponta 00:00 | Csak local és staging környezetben (`exot/onsite-comment`, a csomag maga regisztrálja). Törli a lejárt munkameneteket, átadó tokeneket és meghívókat, valamint az árva képernyőképeket. | jegyek az oldalon |
| `onsite-comment:trello:sync` | ötpercenként | Csak local és staging környezetben, és csak ha az `ONSITE_COMMENT_TRELLO_KEY` és `_TOKEN` meg van adva. Lehúzza a Trello-board változásait a jegyekre, és pótolja az elmaradt pusht. | jegyek az oldalon |
| `prune:mirror-sessions` | óránként | Törli a 24 óránál régebbi, fiókhoz nem kötött Értéktükröket a hanggal, az átirattal és a nyers detektálással együtt, és a meg nem erősített fiókot. Fiókkérés után a 24 óra a megerősítő levéltől számít. A fiók megmarad, ha ugyanarra a címre egy újabb tükör még megerősítésre vár. Csak a névtelen statisztika marad (szabad értéknév, illeszkedés, idézet nélkül). | 06 · 6.1 |
