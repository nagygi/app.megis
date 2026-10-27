<?php

// The calendar connection (09): V6·b, the profile row (31 · 31.5) and the admin's tab (11 · 11.6).

return [

    'title' => 'Naptár',
    'lead' => 'Mit olvasunk, és mit soha.',

    'we_read' => 'Amit olvasunk',
    'read' => [
        'title' => 'Esemény címe, ideje, gyakorisága',
        'title_help' => 'ebből áll össze a mintázat',
        'attendees' => 'Résztvevők',
        'attendees_help' => 'a számuk és a nevük: ebből látszik, kinek a rutinja',
        'invitation' => 'A meghívó szövege',
        'invitation_help' => 'hogy értsük, miről szól az alkalom',
        'description' => 'Leírás, napirend',
        'description_help' => 'ha van a bejegyzésben',
    ],

    'we_never' => 'Amit soha',
    'never' => [
        'attachments' => 'Csatolmányok',
        'attachments_help' => 'dokumentumot a naptárból nem nyitunk meg',
        'private' => 'Magánjelölt bejegyzések',
        'tag' => 'soha',
    ],

    'goes_on' => 'Ami továbbmegy',
    // Label and value side by side (prototype V6·b).
    'goes_on_lines' => [
        'metadata' => 'Naptár-metaadat',
        'excluded' => 'Csatolmány, magánjelölt',
        'disconnect' => 'Szétkötéskor',
        'stays' => 'Megmarad',
    ],
    'goes_on_values' => [
        'metadata' => '90 nap',
        'excluded' => 'kizárva',
        'disconnect' => 'a metaadat törlődik',
        'stays' => 'a rutin és az alkalmak dátuma',
    ],

    'connect' => 'Összekötöm a naptárat',
    'talk' => 'Inkább beszélgetünk',
    'talk_instead' => 'Mondd el inkább a négy kérdésre válaszolva',

    'providers' => [
        'google' => 'Google Naptár',
        'microsoft' => 'Microsoft 365 / Outlook',
        'ics_url' => 'iCal link',
        'ics_file' => 'ICS fájl',
    ],
    'provider_help' => [
        'google' => 'csak olvasási jog',
        'microsoft' => 'a Teams naptára is · csak olvasási jog',
    ],

    'ics_url' => 'A naptár titkos iCal címe',
    'ics_url_help' => 'Google Naptárban: Beállítások → a naptárad → „Titkos cím iCal formátumban”. Outlookban: Beállítások → Naptár → Megosztott naptárak → „Naptár közzététele” → ICS link.',
    'connect_url' => 'Összekötöm',
    'ics_file' => 'Exportált naptárfájl',
    'ics_file_help' => 'Formátum: .ics, legfeljebb :size MB. Egyszeri beolvasás: új fájllal frissíthető.',
    'upload' => 'Feltöltöm',
    'reading' => 'Beolvassuk a naptárat…',

    'connected' => ':provider összekötve · :date',
    'unreadable' => 'Ezt a naptárfájlt nem tudtuk beolvasni. Ellenőrizd, hogy .ics formátumú-e.',
    'url_invalid' => 'Az iCal cím https:// vagy webcal:// kezdetű.',
    'failed' => 'Most nem sikerült. Próbáld újra, vagy mondd el a Coachnak.',
    'access_lost' => 'A naptárkapcsolat megszakadt. Kösd össze újra.',
    'admin_consent' => 'A céged IT-adminjának jóvá kell hagynia a mégis hozzáférését. Addig beszélgessünk.',

    // The profile row (31 · 31.5) and the admin's calendar row (11 · 11.6).
    'profile' => [
        'title' => 'Naptár',
        'connected' => 'Naptár összekötve · :date',
        'by_megis' => 'a kérésedre a mégis töltötte be',
        'metadata_until' => 'a metaadat :date-ig él',
        'broken' => 'A naptárkapcsolat megszakadt. Kösd össze újra.',
        'none' => 'Nincs összekötött naptár.',
        'disconnect' => 'Szétkötés',
        'confirm_disconnect' => 'Biztosan szétkötöd? A naptár-metaadat azonnal törlődik, a folyamatok és az alkalmak megmaradnak.',
        'disconnected' => 'A naptárat szétkötöttük, a metaadat törlődött.',
    ],
    'statuses' => [
        'active' => 'összekötve',
        'broken' => 'megszakadt',
        'disconnected' => 'szétkötve',
    ],

];
