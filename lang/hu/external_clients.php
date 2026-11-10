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
];
