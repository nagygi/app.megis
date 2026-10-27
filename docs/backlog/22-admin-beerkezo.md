# 22 · Admin felület – Beérkező

## Cél

Egy lista mindenről, ami emberi döntést kér, minden diagnózisból (A5). Ha üres, nincs teendő. A prototípus szerint: „Egy sor, egy döntés, legfeljebb két gomb.”

Szuperadmin és Admin egyaránt használhatja (01).

## Alfeladatok

### 22.1 A lista

- Bal oldali menüpont: *Beérkező*, mellette a nyitott sorok száma. A szám azokat a sorokat számolja, amelyeket a lista alapból mutat (kapcsolattartó: én), ugyanígy a Diagnózisok oldal „Ma {n} dolog vár rád” mondata és az *Értékmérések › Beérkező* száma.
- **Soronként:** típus, diagnózis (szervezet · csapat), a tárgy egy mondatban, mióta, legfeljebb két gomb.
- **Lezárás:**
  - a lezárt sor eltűnik a listából;
  - a „Lezártak” szűrővel visszanézhető, ki és mikor zárta le;
  - bármelyik admin lezárhatja.
- Üres listánál ez áll: „Minden rendben. Mehetsz kávézni.”
- Szűrők: típus, szervezet, kapcsolattartó (alapból: én). Az NFR listaszabályai érvényesek.
- **Értesítés:** a kapcsolattartó minden reggel 8-kor összefoglaló e-mailt kap a saját szervezeteinek nyitott soraival (cron, `cron.md`).

### 22.2 Sortípusok

| Típus | Mikor jön | Gombok |
|---|---|---|
| Besorolás | „Egyikhez sem” anyag (12 · 12.2) | Folyamat ▾ és dátum → Kész · *Egyéb* |
| Feldolgozási hiba | egy anyag feldolgozása az újrapróbálások után is sikertelen (16) | Újra · Megnézem |
| Visszajelzés | „Részben” vagy „Nem így látjuk” a riportban (R2·c), szöveggel vagy hanggal | Beépítem · Válaszolok |
| Meghívó | a vezető vagy egy társ 5 napja nem nyitotta meg a meghívót, vagy a meghívó lejárt (14) | Emlékeztetem · Új meghívó |
| Kitöltés | a vezető a kezdés napjáig nem fejezte be a kitöltést (17) | Emlékeztetem · Megnézem |
| Küszöb | a küszöb veszélyben van (19.4) | → Futás |
| Publikálás | a publikálás napja elmúlt (21) | → Olvasat |
| Értéktükör-foglalás | ET5 „Időpontot foglalok” (06), a jelölt kíváncsiság-kérdésekkel | Megnézem · Kész |

- **Értékmérések:** az Értéktükör sorai (a foglalás és az Értéktükör-felvétel feldolgozási hibája) nem a *Beérkező*ben vannak, hanem külön menücsoportban: *Értékmérések › Beérkező*. Ugyanazok a szűrők és gombok, saját számláló. A reggeli összefoglalóba nem kerülnek bele, a foglalásról azonnali e-mail megy, mert az időérzékeny (az ET5-ön most nincs foglalás, 1.28). A csoport első pontja az *Értéktükrök* lista (06 · 6.1). A foglalás „Megnézem” gombja a tükör adatlapjára visz.
- **Besorolás:** a 12 · 12.6 „Besorolásra vár” sorai itt is megjelennek. Ha bármelyik helyen besorolják, mindkettőből eltűnik. Csak az „Egyikhez sem” anyagok jönnek ide; az alacsony konfidenciájú helyzet nem, mert a 12 nem bírálja felül, amit a felhasználó választott.
- **Meghívó:** 5 nap megnyitás nélkül, vagy ha a meghívó lejárt (14 nap, 14 · 14.1). A sor akkor is megjelenik, ha a vezető a V10-en vagy az M6-on maga küldött emlékeztetőt.
- **Kitöltés:** ha a vezető a kezdés napjáig nem nyomta meg az „Indul a diagnózis” gombot (17.5). A sor magától lezárul, amikor a vezető elindítja a diagnózist, vagy az admin zárja le.
- **Beépítem:**
  - megnyitja az érintett fejezetet az olvasatban (20), mellette a visszajelzés szövegével;
  - a javítás után a fejezet fejlécén „frissítve · {dátum}” jelenik meg (21.4);
  - a visszajelzés pontot nem ír át.
- **Válaszolok:** a mégis válasza e-mailben megy a felhasználónak, név szerinti megszólítással, és a sor lezárul. A riportban nem jelenik meg.

## Nincs benne

- A Coach továbbadott kérdései: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- A visszajelzések és a Coach-témák összesítése diagnózisokon át (a prototípus munkaterve P2-re teszi).
