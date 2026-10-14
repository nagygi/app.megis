# mégis

A mégis MVP-je: admin felület, nyilvános Értéktükör és az ügyféloldali diagnózis-út.

- Követelmények: `docs/backlog/` (01–33), NFR (`docs/nfr_exot_laravel.md`, `docs/nfr_project.md`), üzemeltetés (`docs/uzemeltetes.md`).
- Telepítés: `docs/install.md`. Kiadás és rollback: `docs/deploy.md`. Ütemezett feladatok: `docs/cron.md`. Változásnapló: `docs/changelog.md`.

## Stack

Laravel 13, PHP 8.4, PostgreSQL, Redis, Livewire 4 + Alpine, Tailwind 4, Pest.

## Projekt-döntések

- **Admin felület:** saját Blade és Tailwind, Filament nélkül (NFR · 3).
- **Livewire:** class-alapú komponensek (`app/Livewire`). A Livewire és az Alpine a `resources/js/app.js`-ből indul, nem automatikus injektálással, így minden lapon elérhető az Alpine.
- **Design system:** a `prototype 3/_ds` tokenjei a `resources/css/app.css` `@theme` blokkjában vannak. Csak a brand palettája használható, a Tailwind alapszínei ki vannak kapcsolva. Az ikonok, szimbólumok és logók a `resources/images/megis` alatt vannak, CSS-maszkként és háttérképként (`resources/css/megis/assets.css`).
- **Font:** Geologica, a Bunny Fonts-ról build közben letöltve és saját hostról kiszolgálva. Futás közben nincs külső fontkérés.
- **Akadálymentesség:** a gombok és szövegek AA-kontrasztúak. A coral gombon fekete a szöveg, mert krémmel csak 3:1 lenne. A hibaüzenet charcoal szövegű, coral jellel.
- **Nyelv és idő:** magyar és `Europe/Budapest` alapból. Minden felületi szöveg a `lang/hu` alatt van.
- **Verzió:** egy helyen, a `config/megis.php`-ban.
- **Üzemeltetés:**
  - napló öt csatornán (app, auth, ai, mail, queue), érzékenyadat-szűréssel;
  - hibariasztás e-mailben, 15 perces deduplikálással;
  - `GET /health` session nélkül, a részletek tokennel kérhetők;
  - `php artisan megis:setup:check`.

## Külső integrációk

| Szolgáltatás | Mire | Állapot |
|---|---|---|
| Cloudflare Turnstile | captcha a publikus formokon és a belépéseknél | ellenőrzés kész (`App\Rules\Turnstile`) |
| S3-kompatibilis tár (EU) | hangfájlok, átiratok, dokumentumok, jogi PDF-ek | `materials` disk |
| Tranzakciós levélküldő | rendszerlevelek, megis.hu feladóval | konfigurációból |
| AI-szolgáltatók (Anthropic, OpenAI, Gemini) | értékfelismerés, kivonatok, vázlatok | 16-os jegy |
| Átíró szolgáltató | átirat beszélő-felismeréssel | 12.7-es PoC dönt |
| Google Calendar, Microsoft 365 | naptár összekötése, csak olvasás | 09-es jegy |
