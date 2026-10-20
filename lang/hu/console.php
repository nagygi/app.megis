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

    'prices_sync' => [
        'invalid_model' => 'Hibás modell: :option (szolgáltató:modell kell).',
        'failed' => 'Az árlista nem tölthető le, az árak nem változtak: :message',
        'columns' => ['Modell', 'Forrás', 'Bemenet / 1M', 'Kimenet / 1M', 'Cache / 1M', 'Hang / perc'],
        'missing' => 'Nincs ár az árlistában: :model (a korábbi ára, ha volt, megmarad).',
        'done' => ':count modell ára frissítve.',
        'backfilled' => ':count korábbi hívás utólag beárazva.',
    ],

    'values_import' => [
        'invalid_file' => 'Nem található xlsx vagy csv fájl: :path',
        'missing_name_column' => 'Hiányzik a „Név” oszlop az első sorból.',
        'ignored_column' => 'Ismeretlen vagy ismétlődő oszlop, kimarad: :column',
        'duplicate' => ':line. sor: a(z) „:name” név kétszer szerepel a táblázatban.',
        'duplicate_code' => ':line. sor: a(z) „:code” azonosító kétszer szerepel a táblázatban.',
        'row_error' => ':line. sor: :message',
        'unknown_option' => 'ismeretlen érték a(z) „:field” oszlopban: „:value”.',
        'category_without_name' => 'angol kategórianév vagy felsőbb szint csak magyar kategórianévvel adható meg.',
        'category_conflict' => 'a(z) „:name” kategória „:field” mezője eltér a(z) :first. sorétól.',
        'name_taken' => 'a(z) „:name” név már egy másik értéké (azonosító: :code).',
        'code_conflict' => 'a(z) „:name” érték azonosítója :existing, a táblázatban :code.',
        'same_target' => 'a(z) „:name” értéket már a(z) :first. sor frissíti.',
        'archived_updated' => ':line. sor: a(z) „:name” érték archivált; frissült, de archivált marad.',
        'failed' => 'Hibás sorok miatt semmi nem került be. Javítsd a táblázatot, és futtasd újra.',
        'dry_run' => 'Ellenőrzés kész: :created érték kerülne be újként, :updated változna, :unchanged változatlan maradna. Nem töltöttünk be semmit.',
        'category_created' => 'Új kategória: :name',
        'category_updated' => 'Kategória frissítve: :name',
        'updated_value' => 'Változik: :name (:fields)',
        'reviewed' => 'Átnézettnek jelölve: :name',
        'imported' => ':count érték bekerült az Értéktárba, mindegyik v1-ként.',
        'updated' => ':updated érték új verzióval frissült, :unchanged változatlan maradt.',
        'version_note' => 'Betöltés táblázatból (:file), :date',
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
