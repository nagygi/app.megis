<?php

return [

    // The tombstone of a deleted account (31 · 31.7, 33 · 33.7).
    'deleted_user' => 'Törölt felhasználó · #:id',

    'menu' => [
        'open' => 'Menü megnyitása',
        'user_menu' => 'Felhasználói menü',
        'close' => 'Menü bezárása',
        'title' => 'Menü',
        'home' => 'Kezdőlap',
        'profile' => 'Profil',
        'logout' => 'Kijelentkezés',
        'switch_organization' => 'Szervezet váltása',
        'mirror' => 'Értéktükör',
        'value_library' => 'Értéktár',
        'processes' => 'Folyamatok',
    ],

    // The page label in the header, right of the logo (prototype: V1, V10, T5).
    'header' => [
        'first_visit' => 'Első belépés',
        'diagnosis' => 'Diagnózis',
        'home' => 'Kezdőlap',
    ],

    'value_library' => [
        'title' => 'Értéktár',
        'question' => 'Mit jelentenek nálatok az értékek?',
        'organization_values' => 'A szervezet értékei',
        'own_values' => 'A saját értékeid',
        'set_header' => ':count érték · a(z) :organization definíciója · :date',
        'library' => 'Az Értéktár',
        'library_lead' => 'A mégis Értéktára: :count érték, böngészhetően.',
        'search' => 'Keresés névben (magyarul, angolul), szinonimákban',
        'category' => 'Kategória',
        'all_categories' => 'Minden kategória',
        'empty' => 'Nincs ilyen érték.',
        'show' => 'Részletek',
        'hide' => 'Bezárás',
        'low' => 'Az 1',
        'high' => 'A 6',
        'fields' => [
            'meaning' => 'Mit jelent',
            'non_meaning' => 'Mit nem jelent',
            'low_example' => '1 · Mikor nagyon nem teljesül',
            'high_example' => '6 · Mikor teljesül nagyon',
            'example' => 'Példa',
            'examples' => 'Példák',
        ],
        'edit' => 'Szerkesztem',
        'score_from' => 'a :date-i diagnózisból',
        'older_definition' => 'A :date-i diagnózis a korábbi definícióval mért.',
        'add_to_own' => 'Hozzáadom a sajátjaimhoz',
        'added_to_own' => 'A(z) :name bekerült a saját értékeid közé.',
        'already_own' => 'A(z) :name már a saját értékeid között van.',
        'own_full' => 'Legfeljebb :max saját értéked lehet.',
    ],

    'recordings' => [
        'title' => 'Felvételeim',
        'lead' => 'A saját hangfelvételeid. Ha törlöd, csak a hang törlődik, az átirat és a diagnózis megmarad.',
        'empty' => 'Nincs hangfelvételed.',
        'delete' => 'Hang törlése',
        'delete_label' => 'A(z) :date felvétel hangjának törlése',
        'confirm_delete' => 'Biztosan törlöd a hangot? Az átirat és a diagnózis megmarad.',
        'deleted' => 'A hang törölve.',
    ],

    // Hozzájárulásaim (31 · 31.5, 31.6).
    'consents' => [
        'title' => 'Hozzájárulásaim',
        'accepted_at' => 'elfogadva: :date',
        'diagnosis' => 'A(z) :subject diagnózisa',
        'given_at' => 'megadva: :date',
        'revoked_at' => 'visszavonva: :date',
        'revoke' => 'Visszavonom',
        'confirm_answers' => 'Ha visszavonod, a válaszaid és a helyzeteid kikerülnek a(z) :subject diagnózisából, és Útravalót sem kapsz.',
        'confirm_quotes' => 'Ha visszavonod, az idézeteid nem jelennek meg a vezetőnek. A válaszaid a diagnózisban maradnak.',
        'revoked' => 'A hozzájárulásodat visszavontuk.',
        'cannot_revoke' => 'Ez a hozzájárulás már nem vonható vissza.',
    ],

    'home' => [
        'title' => 'Kezdőlap',
        'greeting' => 'Szia, :name!',
        'lead' => 'Itt fogod látni a(z) :organization diagnózisát, amint elindul.',
        'lead_not_participant' => 'Jelenleg nincs olyan diagnózis, amelyben részt veszel.',
        'diagnoses' => 'A diagnózisaid',
        'publishes' => 'publikálás: :date',
    ],

    'profile' => [
        'title' => 'Profil',
        'me' => 'Én',
        'email_hint' => 'Az e-mail címed módosítását a mégistől kérheted.',
        'name_saved' => 'Mentve.',
        'password_title' => 'Jelszó',
        'password_lead_change' => 'A jelszavad cseréjéhez add meg a jelenlegit.',
        'password_lead_set' => 'Még nincs jelszavad, kóddal lépsz be. Ha jelszót állítasz be, egy e-mailben kapott kóddal erősítsd meg.',
        'send_code' => 'Kódot kérek',
        'code_sent' => 'Elküldtük a kódot ide: :email',
        'code' => 'Megerősítő kód',
        'code_invalid' => 'Hibás vagy lejárt kód.',
        'save_password' => 'Jelszó mentése',
        'password_saved' => 'Megváltozott a jelszavad. A többi eszközön kiléptettünk.',
        'current_password' => 'Jelenlegi jelszó',
        'diagnosis_title' => 'A diagnózisod',
        'diagnosis_window' => ':product · :from → :to',
        'contact' => 'Kapcsolattartó a mégistől: :name',
        'state_running' => 'fut · :week. hét a :weeks-ból',
        'state_published' => 'publikálva · :date',
        'awaiting_title' => 'Rád vár',
        'awaiting' => [
            'report' => 'Új riport · :product · :date',
            'peers' => ':count társ még nem válaszolt · emlékeztető küldhető',
            'experience' => 'Megélés hiányzik · :process · :date',
            'peer' => 'A(z) :subject diagnózisában társ vagy · Kezdem',
        ],
        'people_title' => 'Emberek',
        'people_count' => ':total társ · :answered kitöltötte',
        'people_rule' => 'névvel csak ők és a mégis látják · összesítve 3 kitöltő fölött',
        'remind' => 'Emlékeztető',
        'visibility' => 'Ki lát mit',
    ],

    // Adataim törlése (31 · 31.7).
    'deletion' => [
        'title' => 'Adataim törlése',
        'what_goes' => 'Törlődik: a fiókod és minden személyes adatod – felvételek, átiratok, saját értékek, megélések, Értéktükrök.',
        'what_stays' => 'Marad: a publikált diagnózisok összesített számai. A szó szerinti idézeteid kikerülnek a riportból.',
        'when' => 'A fiókod azonnal inaktív lesz, és kiléptetünk; a végleges törlés legkésőbb 24 órán belül lefut. A mentésekben az adat még legfeljebb 30 napig megvan.',
        'start' => 'Törlöm a fiókomat',
        'confirm' => 'Véglegesen törlöm',
        'cancel' => 'Mégse',
        'requested' => 'Kérésedre töröljük a fiókodat és az adataidat. Erről e-mailt is küldünk.',
    ],

];
