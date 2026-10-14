<?php

return [

    'admin_create' => [
        'invalid_role' => 'A szerepkör admin vagy superadmin lehet.',
        'last_name' => 'Vezetéknév',
        'first_name' => 'Keresztnév',
        'email' => 'E-mail',
        'invalid_email' => 'Érvénytelen e-mail cím.',
        'existing' => 'Ez az e-mail cím már létezik. Kapjon :role szerepkört?',
        'cancelled' => 'Megszakítva, semmi nem változott.',
        'password' => 'Jelszó',
        'password_confirmation' => 'Jelszó még egyszer',
        'invited' => 'Meghívót küldtünk ide: :email',
        'created' => ':email létrehozva, szerepkör: :role.',
    ],

    'admin_unlock' => [
        'not_found' => 'Nincs ilyen admin: :email',
        'unlocked' => ':email fiókja feloldva, a hibás próbálkozások számlálója nullázva.',
    ],

];
