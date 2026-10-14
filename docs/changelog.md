# Változásnapló

A legfrissebb verzió van felül. Formátum: `## {verzió} · {commit-hash} · {commit dátuma}`.

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
