# 01 · Admin felület – admin felhasználók kezelése

## Cél

Védett `/admin` felület a mégis csapatának. A ticket öt részből áll: első szuperadmin felvitele és fiókzárolás feloldása konzolból, bejelentkezés, admin felhasználók kezelése, Rendszer menü, user menü.

Az admin ugyanolyan felhasználó, mint az ügyfelek, csak Admin vagy Szuperadmin szerepkört kap. Ugyanaz a fiók elvben ügyfél is lehet, de ehhez nem kell külön működés.

## Szerepkörök

| | Szuperadmin | Admin |
|---|:---:|:---:|
| Admin felület, üzleti funkciók | ✅ | ✅ |
| Admin meghívása, szerkesztése, inaktiválása, resetje | ✅ | ✅ |
| Ugyanezek szuperadminra, illetve szuperadmin kinevezése | ✅ | ❌ (a szuperadmint csak olvashatóként látja) |
| *Rendszer* menü (AI modellek és tokenhasználat: 16, health: [docs/uzemeltetes.md](../docs/uzemeltetes.md)) | ✅ | ❌ |

- Saját magát senki nem inaktiválhatja, és nem fokozhatja le.
- Az utolsó aktív szuperadmin nem inaktiválható, és nem fokozható le.
- A jogosultságot a szerver is ellenőrzi, nem elég elrejteni a gombokat.

## Alfeladatok

### 1.1 Első szuperadmin – konzol parancs

`php artisan megis:admin:create`

- Interaktívan kéri: vezetéknév, keresztnév, e-mail, jelszó (rejtett bevitel, megerősítéssel).
- A jelszó nem adható át argumentumként.
- `--role=admin|superadmin` (alapértelmezés: superadmin).
- `--invite`: jelszó helyett meghívó e-mailt küld.
- Ha az e-mail már létezik, rákérdez, hogy admin szerepkört kapjon-e.
- A parancs leírása bekerül az `install.md`-be.

`php artisan megis:admin:unlock {email}`

- Feloldja a zárolt fiókot (lásd 1.2), és nullázza a hibás próbálkozások számlálóját.
- A leírása bekerül az `install.md`-be.

### 1.2 Bejelentkezés

`/admin/login`

- Mezők: e-mail, jelszó, Turnstile.
- Hibás jelszó, inaktív és nem admin fiók esetén ugyanaz az üzenet jelenik meg: „Hibás e-mail cím vagy jelszó.”
- Önkiszolgáló „Elfelejtett jelszó” nincs. A helyén ez a szöveg áll: „Kérd meg egy másik admint, hogy küldjön új beállító linket.”
- Nincs „Emlékezz rám”.
- Rate limit: 5 próbálkozás percenként, e-mail + IP alapján.
- Fiókzárolás: 10 egymást követő sikertelen próbálkozás után a fiók zárolódik. Ezt a felület ki is írja: „A fiókod zárolva van. Kérd egy szuperadmin segítségét.” Feloldani csak konzolból lehet (`megis:admin:unlock`). Sikeres belépés után a számláló nullázódik.
- Jelszó policy: legalább 8 karakter, mint az ügyfeleknél (14 · 14.3), és ismert kiszivárgott jelszó nem használható. Ugyanez érvényes a jelszó beállításakor és cseréjekor is.
- Session: 6 óra inaktivitás után lejár.
- Jelszócsere, reset vagy inaktiválás után a futó sessionök megszűnnek.
- Nem admin felhasználó az `/admin/*` útvonalakon „Nincs jogosultságod” oldalt kap.

### 1.3 Admin felhasználók felület

**Lista** (az NFR listaszabályai szerint):

- Oszlopok: név, e-mail, szerepkör, állapot (Meghívva / Meghívó lejárt / Aktív / Zárolva / Inaktív), utolsó belépés.
- Kereső: név és e-mail.
- Szűrők: szerepkör, állapot. Az inaktív fiókok alapból rejtve vannak, egy kapcsolóval megjeleníthetők.
- Sorműveletek: szerkesztés, meghívó újraküldése, jelszó reset, inaktiválás / aktiválás.

**Meghívás**

- Mezők: vezetéknév, keresztnév, e-mail, szerepkör.
- Az e-mailben kapott link 24 óráig él, egyszer használható.
- A link a jelszó-beállító oldalra visz (Turnstile-lal), utána a felhasználó belép.

**Szerkesztés:** név, e-mail, szerepkör.

**Jelszó reset**

- Egy másik admin indítja, megerősítő ablakkal.
- Azonnal érvényét veszti a régi jelszó, és minden session megszűnik.
- Az érintett 24 óráig élő, egyszer használható jelszó-beállító linket kap e-mailben.
- Az indító admin a jelszót nem látja.

**Inaktiválás / aktiválás**

- Egy másik admin indítja, megerősítő ablakkal.
- Inaktiváláskor a felhasználó azonnal kijelentkezik, és nem tud belépni.
- Az adatai megmaradnak, és látszik, mit hozott létre vagy módosított korábban.
- Visszaaktiválás után a régi jelszavával újra beléphet.
- Meghívott, de még nem regisztrált fiók is inaktiválható. Ilyenkor a meghívó link érvényét veszti.
- MVP-ben nincs végleges törlés.

### 1.4 Rendszer menü

- A *Rendszer* menücsoport csak szuperadminnak látszik.
- Ha Admin közvetlen URL-lel próbálja megnyitni, „Nincs jogosultságod” oldalt kap.
- Ebben a ticketben csak egy üres áttekintő oldal kell hozzá. A tartalmát a 16-os jegy (AI) és az üzemeltetési leírás (health, [docs/uzemeltetes.md](../docs/uzemeltetes.md)) adja.

### 1.5 User menü

- Tartalma: név, szerepkör badge, *Profilom*, *Kijelentkezés*.
- Az app verziója a láblécben vagy a bal oldali menü alján látszik, nem a user menüben.
- **Profil:**
  - szerkeszthető a név;
  - a jelszó a jelenlegi jelszó megadásával cserélhető;
  - a saját e-mail címét csak szuperadmin módosíthatja, adminnál csak megjelenik;
  - a szerepkör csak megjelenik.

## E-mailek

- **Meghívó:** „{Meghívó} meghívott a mégis admin felületére. Állítsd be a jelszavad. [Jelszó beállítása] A link 24 óráig él.”
- **Reset:** „{Admin} új jelszó-beállító linket kért neked. A régi jelszavad már nem érvényes. [Új jelszó beállítása] A link 24 óráig él.”
- **Jelszó megváltozott:** „A jelszavad {időpont}-kor megváltozott. Ha nem te voltál, szólj egy szuperadminnak.”

## Nincs benne

- 2FA: [NM-01](../backlog_nem_MVP/NM-01-admin-ketfaktoros-azonositas.md).
- Önkiszolgáló elfelejtett jelszó.
- Végleges törlés.
- Audit napló UI.
- Szervezeti címkék (szervezet vezetője, csapat vezető, munkatárs, partner): a 02-es jegyben vannak.
