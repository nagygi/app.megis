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

    'mirror_prune' => [
        'done' => ':count lejárt névtelen tükör törölve.',
    ],

    'calendar_sync' => [
        'done' => ':count naptár frissítve.',
    ],

    'calendar_prune' => [
        'done' => ':count naptár metaadata törölve.',
    ],

    'transcription_compare' => [
        'usage' => 'Adj meg egy hangfájlt és legalább egy --model=szolgáltató:modell opciót.',
        'invalid_model' => 'Hibás modell: :option (szolgáltató:modell kell).',
        'columns' => ['Szolgáltató:modell', 'Idő (s)', 'Beszélők', 'Szavak', 'Perc', 'Költség', 'Átirat / hiba'],
    ],

    'values_import' => [
        'invalid_file' => 'Nem található xlsx vagy csv fájl: :path',
        'missing_name_column' => 'Hiányzik a „Név” oszlop az első sorból.',
        'ignored_column' => 'Ismeretlen oszlop, kimarad: :column',
        'skipped' => 'Már az Értéktárban van, kimarad: :name',
        'duplicate' => ':line. sor: a(z) „:name” név kétszer szerepel a táblázatban.',
        'row_error' => ':line. sor: :message',
        'failed' => 'Hibás sorok miatt semmi nem került be. Javítsd a táblázatot, és futtasd újra.',
        'dry_run' => 'Ellenőrzés kész, :count érték kerülne be. Nem töltöttünk be semmit.',
        'category_created' => 'Új kategória: :name',
        'imported' => ':count érték bekerült az Értéktárba, mindegyik v1-ként.',
    ],

    'inbox_scan' => [
        'done' => 'Beérkező átnézve: :opened új sor, :closed lezárva.',
    ],

    'inbox_digest' => [
        'done' => ':count összefoglaló ment ki.',
    ],

    'accounts_purge' => [
        'done' => ':count fiók adatai törölve.',
    ],

];
