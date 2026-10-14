<?php

return [

    'menu' => [
        'open' => 'Menü megnyitása',
        'close' => 'Menü bezárása',
        'title' => 'Menü',
        'profile' => 'Profil',
        'logout' => 'Kijelentkezés',
        'switch_organization' => 'Szervezet váltása',
        'mirror' => 'Értéktükör',
        'value_library' => 'Értéktár',
    ],

    'value_library' => [
        'title' => 'Értéktár',
        'question' => 'Mit jelentenek nálatok az értékek?',
        'organization_values' => 'A szervezet értékei',
        'own_values' => 'A saját értékeid',
        'set_header' => ':count érték · a(z) :organization definíciója · :date',
        'library' => 'Az Értéktár',
        'library_lead' => 'A mégis Értéktára: :count érték, böngészhetően.',
        'search' => 'Keresés névben, szinonimákban',
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

    'home' => [
        'title' => 'Kezdőlap',
        'greeting' => 'Szia, :name!',
        'lead' => 'Itt fogod látni a(z) :organization diagnózisát, amint elindul.',
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
    ],

];
