# Ütemezett feladatok

Az ütemező percenként fut: `* * * * * php artisan schedule:run`. A feladatok a `routes/console.php`-ban vannak. Mindegyik `onOneServer()`, így több szerveren is csak egyszer fut.

Minden jegy, amely ütemezett feladatot hoz, ide bejegyzi.

| Feladat | Gyakoriság | Mit csinál | Jegy |
|---|---|---|---|
| `heartbeat:scheduler` | percenként | Jelzi a health checknek, hogy az ütemező fut. | üzemeltetés · 3 |
| `heartbeat:queue` | percenként | Egy jobot tesz a queue-ra. Amikor a worker feldolgozza, jelzi a health checknek, hogy a worker él. | üzemeltetés · 3 |
| `prune:user-tokens` | naponta 03:00 | Törli a lejárt vagy felhasznált egyszer használatos linkeket és kódokat (meghívó, belépő kód, jelszó-beállítás) a lejárat után 30 nappal. | 01, 14 |
