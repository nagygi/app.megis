# 34 · Rendszer – visszapattanó levelek

## Cél

Ha egy levél nem kézbesíthető, vagy a címzett spamnek jelöli, a rendszer ezt magától rögzíti, és az admin látja a felhasználónál.

A jelzés helye már kész: a `users.mail_undeliverable_at` mező és „A levél nem kézbesíthető” felirat a szervezet Felhasználók fülén (02 · 2.5), az Ügyfelek listán és az ügyfél adatlapján (33). Hiányzik viszont, ami beállítja: a levélküldő értesítése. Ez a rész a 02-ből vált külön, mert a levélküldő kiválasztására vár (`docs/deploy.md` · „Élesítés előtt”). Kapcsolódik: 02, 33, `docs/uzemeltetes.md` (1. pont).

## Alfeladatok

### 34.1 Értesítés a levélküldőtől

- A levélküldő a visszapattanásról és a panaszról (spamnek jelölés) értesítést küld egy végpontra.
- A végpont csak a levélküldő hitelesített értesítését fogadja el (aláírás vagy titkos kulcs). Minden mást elutasít.
- **Ami jelzést állít be:** a végleges visszapattanás (nem létező cím, megszűnt postafiók) és a panasz. Ilyenkor a címhez tartozó felhasználónál beállítódik a `mail_undeliverable_at`.
- **Ami nem:** az átmeneti hiba (teli postafiók, időtúllépés). Ezt a levélküldő maga próbálja újra.
- Ugyanaz az értesítés kétszer is megérkezhet, a második nem változtat semmin.
- Ismeretlen címnél nem történik semmi, csak naplózzuk.

### 34.2 A jelzés élete

- A jelzés ott látszik, ahol most: a Felhasználók fülön (02 · 2.5), az Ügyfelek listán és az adatlapon (33).
- Ha a szuperadmin átírja a felhasználó e-mail címét (33 · 33.2), a jelzés törlődik.
- Minden beállítás és törlés a felhasználó naplójába kerül (33).

### 34.3 Beállítás

- A végpont címe és a hitelesítés titka a `deploy.md`-be kerül.
- A setup check (NFR) jelzi, ha éles környezetben nincs beállítva.

## Nincs benne

- A levélküldő kiválasztása, a küldő domain SPF-, DKIM- és DMARC-beállítása: `docs/deploy.md` · „Élesítés előtt”.
- Kézbesítési és megnyitási statisztika.

## Nyitott kérdések

1. Melyik levélküldővel dolgozunk?

   **Javaslat:** Amazon SES (eu-central-1), a visszapattanásról és a panaszról SNS-értesítéssel, ahogy a `docs/mvp-terv.md` 2. pontja javasolja.

2. Menjen-e levél a jelzett címre?

   **Javaslat:** igen, a rendszer nem tiltja. A jelzés az adminnak szól, hogy javíttassa a címet. A levélküldő saját tiltólistája úgyis kiszűri a végleg rossz címeket, így a küldő hírneve nem romlik.
