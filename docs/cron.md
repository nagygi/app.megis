# Ütemezett feladatok

Az ütemező percenként fut: `* * * * * php artisan schedule:run`. A feladatok a `routes/console.php`-ban vannak. Mindegyik `onOneServer()`, így több szerveren is csak egyszer fut.

Minden jegy, amely ütemezett feladatot hoz, ide bejegyzi.

| Feladat | Gyakoriság | Mit csinál | Jegy |
|---|---|---|---|
| `heartbeat:scheduler` | percenként | Jelzi a health checknek, hogy az ütemező fut. | üzemeltetés · 3 |
| `heartbeat:queue` | percenként | Egy jobot tesz a queue-ra. Amikor a worker feldolgozza, jelzi a health checknek, hogy a worker él. | üzemeltetés · 3 |
| `prune:user-tokens` | naponta 03:00 | Törli a lejárt vagy felhasznált egyszer használatos linkeket és kódokat (meghívó, belépő kód, jelszó-beállítás) a lejárat után 30 nappal. | 01, 14 |
| `sync:calendars` | naponta 04:00 | Frissíti az összekötött naptárakat (Google, Microsoft, iCal link) a diagnózis időablakának végéig: az elfogadott folyamatok új alkalmait felveszi, új mintát nem javasol. Az időablak végén automatikusan szétköt; megszakadt hozzáférésnél a kapcsolat „megszakadt” állapotba kerül. | 09 · 9.4, 9.5, 9.6 |
| `prune:calendar-metadata` | naponta 03:30 | Törli a naptár-metaadatot (cím, leírás, résztvevők) az összekötéstől számított 90 nap után. A folyamatok és az alkalmak dátuma megmarad. | 09 · 9.5 |
| `prune:mirror-sessions` | óránként | Törli a 24 óránál régebbi, fiókhoz nem kötött Értéktükröket a hanggal, az átirattal és a nyers detektálással együtt, és a meg nem erősített fiókot. Csak a névtelen statisztika marad (szabad értéknév, illeszkedés, idézet nélkül). | 06 · 6.1 |
