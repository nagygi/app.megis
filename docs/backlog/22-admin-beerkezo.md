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

- **Értékmérések:** az Értéktükör sorai (a foglalás és az Értéktükör-felvétel feldolgozási hibája) nem a *Beérkező*ben vannak, hanem külön menücsoportban: *Értékmérések › Beérkező*. Ugyanazok a szűrők és gombok, saját számláló. A reggeli összefoglalóba nem kerülnek bele, a foglalásról azonnali e-mail megy.
- **Besorolás:** a 12 · 12.6 „Besorolásra vár” sorai itt is megjelennek. Ha bármelyik helyen besorolják, mindkettőből eltűnik.
- **Beépítem:**
  - megnyitja az érintett fejezetet az olvasatban (20), mellette a visszajelzés szövegével;
  - a javítás után a fejezet fejlécén „frissítve · {dátum}” jelenik meg (21.4);
  - a visszajelzés pontot nem ír át.
- **Válaszolok:** lásd 1. kérdés.

## Nincs benne

- A Coach továbbadott kérdései: [NM-18](../backlog_nem_MVP/NM-18-coach.md).
- A visszajelzések és a Coach-témák összesítése diagnózisokon át (a prototípus munkaterve P2-re teszi).

## Nyitott kérdések

1. **Hogyan kapja meg a felhasználó a mégis válaszát?**
   - a) E-mailben, név szerinti megszólítással. A riportban nem jelenik meg.
   - b) A fejezet alján „A mégis válasza” blokkban. Ez szebb, de új kliens-felület.

   **Javaslat:** a), mert nem kell hozzá új ügyféloldali lap.

2. **Admin-értesítés új sorról.**

   **Javaslat:**
   - A kapcsolattartó minden reggel 8-kor egy összefoglaló e-mailt kap a saját szervezeteinek nyitott soraival (cron, `cron.md`).
   - Azonnali e-mail csak az Értéktükör-foglalásnál menjen, mert az időérzékeny.

3. **Értéktükör-foglalás.** A foglalás külső naptárban történik (06, naptárlink a konfigurációból), így a rendszer csak azt tudja, hogy a felhasználó rákattintott.

   **Javaslat:** a kattintás hozza létre a sort („{Név} időpontot foglalna · kíváncsi: belső működés, képességek”), és a mégis kézzel zárja le. Kérdés: milyen foglalási szolgáltatást használ a mégis (Calendly, Google foglalási oldal, más)? Ha van webhookja, a sor később magától lezárulhat.

4. **Alacsony konfidenciájú besorolás.** A prototípus szerint az alacsony konfidenciájú helyzet is ide jön. A 12 viszont nem bírálja felül, amit a felhasználó választott.

   **Javaslat:** MVP-ben csak az „Egyikhez sem” anyagok jönnek ide.

5. **A meghívó-sor küszöbe.**

   **Javaslat:** 5 nap megnyitás nélkül, vagy lejárt meghívó (14 nap, 14 · 14.1). A vezető a V10-en és az M6-on maga is küldhet emlékeztetőt, az admin sora ettől függetlenül megjelenik.

6. **Kitöltés-sor.**

   **Javaslat:** ha a vezető a kezdés napjáig nem nyomta meg az „Indul a diagnózis” gombot (17.5), egy sor jelenik meg. A sor addig marad nyitva, amíg a vezető el nem indítja a diagnózist, vagy az admin le nem zárja.
