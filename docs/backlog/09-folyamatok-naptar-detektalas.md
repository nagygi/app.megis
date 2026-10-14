# 09 · Folyamatok – naptár összekötése és detektálás

## Cél

A vezető összeköti a naptárát. A rendszer 90 napra visszamenőleg beolvassa az eseményeket, és javaslatot tesz a visszatérő folyamatokra. A javaslatok a detektált folyamatok listájára kerülnek (V6·d, 11). Összekötés után a rendszer a diagnózis végéig naponta frissít, így az elfogadott folyamatok új alkalmai maguktól megjelennek (12).

**Ki használja**

- A szervezet vezetője címkéjű felhasználó, a diagnózis Folyamatok lépésében (V6, V6·b) és a Profilban (M6, 31).
- Magánszemély típusú szervezetnél (02) a felhasználó a saját naptárát köti össze.
- A társ nem köt össze naptárat.

## Alfeladatok

### 9.1 Mit olvasunk, és mit soha (V6·b)

Egy lap az összekötés előtt.

- **Amit olvasunk:**
  - az esemény címe, ideje, ismétlődése;
  - a résztvevők neve és e-mail címe;
  - a meghívó szövege, a leírás és a napirend.
- **Amit soha:** csatolmányok, magánjelölt (privát) bejegyzések.
- **Ami továbbmegy:**
  - a naptár-metaadat 90 napig él;
  - a csatolmányok és a magánjelölt bejegyzések ki vannak zárva;
  - szétkötéskor a metaadat törlődik;
  - a folyamat (rutin-címke) megmarad.
- Gombok: „Naptár összekötése” · „Inkább beszélgetünk” (10).

### 9.2 Források

A „Naptár összekötése” után a felhasználó választ:

- **Google Calendar:** OAuth, csak olvasási jog.
  - A Google ellenőrzése (verification) napokig-hetekig tart, ezért időben el kell indítani. Amíg nincs meg, legfeljebb 100 tesztfelhasználó köthet össze.
- **Microsoft 365 / Outlook**, a Teams naptárát is beleértve: Microsoft Graph, csak olvasási jog.
  - Céges fióknál a cég IT-adminjának jóvá kell hagynia a hozzáférést. Ilyenkor ez jelenik meg: „A céged IT-adminjának jóvá kell hagynia a mégis hozzáférését. Addig beszélgessünk.” A gomb a V6·c-re visz (10).
- **iCal link (ICS URL):**
  - A felhasználó bemásolja a naptára titkos iCal címét.
  - Mellette rövid leírás: hol találja a címet a Google-ban és az Outlookban.
- **ICS fájl:** egyszeri export feltöltése.
  - Nincs folyamatos frissítés. Új fájl feltöltésével frissíthető, egyébként az új alkalmakat a feltöltő adja meg (12).
  - A megengedett formátum és a maximális méret ki van írva.

A hozzáférési adatokat (token, iCal cím) titkosítva tároljuk.

A Google- és a Microsoft-alkalmazás a mégis céges fiókján van regisztrálva, a fejlesztő meghatalmazással kezeli, így a hozzájárulási ablakban a felhasználó a mégis nevét látja. A Google-ellenőrzést a fejlesztés elején el kell indítani; ehhez kell az adatkezelési tájékoztató linkje (05) és egy támogatási e-mail cím.

### 9.3 Detektálás

A detektálás szabályalapú, az AI csak kiegészíti.

- **Beolvasás:**
  - Az utolsó 90 nap eseményeit olvassuk be.
  - Kimaradnak a lemondott, a vezető által elutasított és az egész napos események.
- **Mintázat (szabályalapú):**
  - Egy jelölt egy ismétlődő sorozat, vagy olyan események csoportja, amelyeknek hasonló a címe, hasonló a résztvevőköre, és szabályos időközönként követik egymást.
  - A jelölt adatai:
    - ritmus (pl. „keddenként 9:00”), hossz, létszám;
    - előfordulás (pl. „8/12 hét”, „6/6 alkalom”);
    - konfidencia: magas, közepes vagy alacsony, az előfordulás aránya és a résztvevőkör állandósága alapján.
  - A magas konfidenciájú jelölt az „Erős minta”, a közepes és az alacsony a „Bizonytalan” csoportba kerül (11).
- **AI:**
  - A jelöltekhez az AI javasol nevet, típust (rutin vagy egyszeri) és alfolyamatot (pl. az „Ops heti” alá a „Hibák átbeszélése”).
  - Az AI megkapja a címet, a leírást, a ritmust és a létszámot. A résztvevők nevét és e-mail címét nem kapja meg.
  - A modell- és a promptverziót mentjük (NFR 11).
- **Egyéb:** ami nem áll össze mintává, darabszámmal látszik, két sorban: egyszeri események, nincs hozzákötve.
- Az eredmény a V6·d listára kerül (11). A fejlécben például: „142 esemény · 90 nap”.

### 9.4 Folyamatos frissítés

- A rendszer naponta frissít, a diagnózis időablakának végéig. ICS fájlnál nincs frissítés.
- Ha egy elfogadott folyamat sorozatában új esemény zajlott le, abból alkalom lesz (12), dátummal, idővel és résztvevőkkel.
- Futás közben új mintát nem javasolunk. Új folyamat kézzel vehető fel (11).

### 9.5 Adatkezelés és szétkötés

- **Az esemény-metaadat** (cím, leírás, résztvevők) az összekötéstől számított 90 napig él, utána törlődik.
  - A törlés dátuma látszik a Profilban és az adminban („a metaadat 12.21-ig él”).
  - A törlés után a folyamat (név, ritmus, felelős, résztvevők) és az alkalmak dátuma megmarad.
- **Szétkötés:** a Profilban („Naptár összekötve · 03.02. · Szétkötés”), megerősítéssel.
  - A hozzáférés megszűnik, és a metaadat azonnal törlődik. A folyamatok és az alkalmak megmaradnak.
  - A diagnózis időablakának végén a rendszer automatikusan szétköt.
- **Újraösszekötés:** bármikor lehetséges. A rendszer az új javaslatokat a meglévő folyamatokhoz illeszti (azonos sorozat vagy név alapján), nem hoz létre duplikátumot.

### 9.6 Hibák

- **Sikertelen összekötés:** „Most nem sikerült. Próbáld újra, vagy mondd el a Coachnak.” Utána a V6·c következik (10).
- **Ha futás közben megszakad a kapcsolat** (lejárt vagy visszavont hozzáférés):
  - a Profilban ez jelenik meg: „A naptárkapcsolat megszakadt. Kösd össze újra.”;
  - az adminban a szervezet *Folyamatok* fülén (11) ugyanez látszik.

## Nincs benne

- A naptár folyamatos szinkronja a diagnózis után, az Értékmérésben: [NM-13](../backlog_nem_MVP/NM-13-folyamatok-ertekmeresben.md).
- Szervezeti idő (emberóra) a naptárból: [NM-13](../backlog_nem_MVP/NM-13-folyamatok-ertekmeresben.md).
- A társak naptára.
- Új minták javaslata futás közben.
