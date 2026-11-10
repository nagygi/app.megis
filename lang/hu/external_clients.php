<?php

/*
 * Ügyfélmérés (35): what the organization's clients see and are asked.
 */

return [
    // The question a new item starts from; the admin rewrites it per client (35 · 35.3).
    'default_questions' => [
        'value' => 'Mesélj egy helyzetről, amikor a közös munkánkban megmutatkozott – vagy épp hiányzott – ez: „:value”. Mi történt, és milyen volt ezt megélni?',
        'process' => 'Gondolj a legutóbbi alkalomra, amikor részese voltál ennek nálunk: „:process”. Mi történt, mi ment jól, és min változtatnál?',
    ],

    // The personal link (35 · 35.5).
    'invitation' => [
        'title' => 'Ügyfélmérés',
        'lead' => 'A(z) :organization kíváncsi, hogyan éled meg a közös munkát. Egy gombnyomás, és a kérdésekhez jutsz; jelszó nem kell.',
        'button' => 'Tovább a kérdésekhez',
        'accept_needed' => 'A továbblépéshez fogadd el az ÁSZF-et és az adatkezelési tájékoztatót.',
        'expired_title' => 'Ez a link már nem érvényes',
        'expired' => 'A link lejárt, vagy már használtad. A belépőoldalon az e-mail címeddel kérhetsz belépőkódot.',
        'to_login' => 'Belépőkódot kérek',
    ],

    // The measurement page (35 · 35.6).
    'measurement' => [
        'title' => 'Ügyfélmérés',
        'intro' => 'A(z) :organization szeretné megérteni, hogyan látod kívülről a működését. :count kérdés vár rád; mindegyikre elmondhatod, leírhatod, vagy fájlból feltöltheted a válaszod.',
        'intro_deadline' => 'A válaszaidat :date napig küldheted el, addig módosíthatod is őket.',
        'consent' => 'A válaszaimat a mégis névvel látja, és a(z) :organization diagnózisában név nélkül, összesítve használja fel.',
        'consent_required' => 'A kezdéshez jelöld be a hozzájárulást.',
        'start' => 'Kezdem',
        'progress' => ':current / :total',
        'value_label' => 'Érték',
        'process_label' => 'Folyamat',
        'answer' => 'Válaszod',
        'previous' => 'Vissza',
        'next' => 'Tovább',
        'to_summary' => 'Áttekintés',
        'summary_title' => 'Áttekintés',
        'summary_lead' => 'Átnézheted és módosíthatod a válaszaidat. Az üresen hagyott kérdés kimarad.',
        'empty_answer' => 'Nincs válasz.',
        'edit' => 'Módosítom',
        'submit' => 'Beküldöm',
        'nothing_to_submit' => 'Legalább egy kérdésre válaszolj.',
        'sent_title' => 'Köszönjük!',
        'sent' => 'A válaszaid megérkeztek. :date napig módosíthatod őket.',
        'review' => 'Megnézem a válaszaimat',
        'closed_title' => 'A mérés lezárult',
        'closed' => 'Erre a mérésre már nem lehet válaszolni. Köszönjük, ha részt vettél benne.',
        'no_items' => 'Most nincs kérdés, amire válaszolhatnál.',
    ],

    'consent' => [
        'version' => '2026-11-10',
        'text' => 'A válaszaimat a mégis névvel látja, és a(z) :organization diagnózisában név nélkül, összesítve használja fel.',
    ],

    // A client's subdiagnosis (35 · 35.7, 35.8).
    'diagnosis_name' => 'Ügyfélmérés · :client',
    'subject' => 'Ügyfél · :name',

    // The speakers of the written exchange the measurement reads (35 · 35.7).
    'speaker_question' => 'Kérdés',
    'speaker_client' => 'Ügyfél',
];
