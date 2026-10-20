<?php

return [

    'home' => 'Vissza a kezdőlapra',
    // /tovabb: a signed-in customer lands on their home, anyone else on the login.
    'account' => 'Tovább a fiókodba',
    'reload' => 'Újratöltöm az oldalt',

    '403' => [
        'title' => 'Nincs jogosultságod',
        'message' => 'Ehhez az oldalhoz nincs hozzáférésed. Ha szerinted kellene, szólj a mégis kapcsolattartódnak.',
    ],

    '404' => [
        'title' => 'Nincs ilyen oldal',
        'message' => 'Ezt az oldalt nem találjuk. Lehet, hogy elírás van a címben, vagy az oldal már nem létezik.',
    ],

    '419' => [
        'title' => 'Lejárt a munkamenet',
        'message' => 'Túl sokáig volt nyitva az oldal. Töltsd újra, és próbáld meg még egyszer.',
    ],

    '429' => [
        'title' => 'Túl sok próbálkozás',
        'message' => 'Várj egy kicsit, és próbáld újra.',
    ],

    // Every other 4xx without a page of its own, e.g. a 405 from a stale link.
    '4xx' => [
        'title' => 'Ez a kérés nem teljesíthető',
        'message' => 'Lehet, hogy egy régi vagy hibás hivatkozásról jöttél. Kezdd újra a kezdőlapról.',
    ],

    '500' => [
        'title' => 'Valami elromlott',
        'message' => 'Hiba történt nálunk. Már tudunk róla. Próbáld újra egy kicsit később.',
    ],

    '503' => [
        'title' => 'Karbantartás',
        'message' => 'Épp karbantartjuk a mégis-t. Hamarosan újra elérhető.',
    ],

];
