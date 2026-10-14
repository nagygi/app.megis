<?php

return [

    'status' => [
        'ok' => 'OK',
        'warning' => 'Figyelmeztetés',
        'error' => 'Hiba',
    ],

    'command' => [
        'status' => 'Állapot',
        'check' => 'Ellenőrzés',
        'details' => 'Részlet',
        'summary_ok' => 'Minden ellenőrzés rendben.',
        'summary_warnings' => ':count figyelmeztetés, hiba nincs.',
        'summary_errors' => ':count hiba. A környezet nincs rendben.',
    ],

    'database' => [
        'label' => 'Adatbázis',
        'ok' => 'Kapcsolódva (:driver).',
        'error' => 'Nem sikerült kapcsolódni: :error',
    ],

    'redis' => [
        'label' => 'Redis',
        'unused' => 'Nincs használatban (a cache, a session és a queue más drivert használ).',
        'ok' => 'Kapcsolódva.',
        'error' => 'Nem sikerült kapcsolódni: :error',
    ],

    'materials_storage' => [
        'label' => 'Fájltár',
        'ok' => 'A próbafájl olvasható (:driver).',
        'error' => 'A próbafájl nem olvasható: :error',
    ],

    'queue_worker' => [
        'label' => 'Queue worker',
        'sync' => 'Szinkron queue, nincs külön worker.',
        'ok' => 'Él (utolsó jel: :time).',
        'missing' => 'Még nem jött jel a workertől.',
        'stale' => 'Nincs friss jel a workertől (utolsó: :time).',
    ],

    'scheduler' => [
        'label' => 'Ütemező',
        'ok' => 'Fut (utolsó futás: :time).',
        'missing' => 'Az ütemező még nem futott.',
        'stale' => 'Az ütemező nem futott az elmúlt 5 percben (utolsó: :time).',
    ],

    'disk_space' => [
        'label' => 'Szabad lemezhely',
        'ok' => ':free MB szabad.',
        'error' => 'Csak :free MB szabad (legalább :min MB kell).',
        'unknown' => 'A szabad hely nem kérdezhető le.',
    ],

    'required_environment' => [
        'label' => 'Kötelező környezeti változók',
        'ok' => 'Mind be van állítva.',
        'error' => 'Hiányzik: :names',
    ],

    'mail' => [
        'label' => 'Levélküldő',
        'ok' => 'Beállítva (:mailer).',
        'ok_connected' => 'Kapcsolódva (:mailer).',
        'local' => 'Helyi levélküldő (:mailer), valódi levél nem megy ki.',
        'production_local' => 'Élesben nem lehet „:mailer” a levélküldő.',
        'error' => 'Nem sikerült kapcsolódni: :error',
    ],

    'turnstile' => [
        'label' => 'Turnstile',
        'ok' => 'A kulcsok be vannak állítva.',
        'missing' => 'Hiányzik a TURNSTILE_SITE_KEY vagy a TURNSTILE_SECRET_KEY.',
        'test_keys' => 'Élesben a Cloudflare tesztkulcsai nem használhatók.',
    ],

    'calendar' => [
        'label' => 'Naptár-összekötés',
        'ok' => 'A Google- és a Microsoft-alkalmazás kulcsai be vannak állítva.',
        'missing' => 'Hiányzik: :variables. Addig csak iCal link, ICS fájl és beszélgetés választható.',
    ],

    'legal_documents' => [
        'label' => 'Jogi dokumentumok',
        'ok' => 'Mindhárom dokumentum fel van töltve.',
        'missing' => 'Nincs feltöltve: :documents',
    ],

    'transcription' => [
        'label' => 'Átírás',
        'ok' => 'Az átíró és az ffprobe rendben.',
        'fake_in_production' => 'Élesben nem futhat a minta-átíró (MEGIS_TRANSCRIBER=fake).',
        'no_model' => 'Nincs átíró modell (AI_TRANSCRIPTION_MODEL); a 12.7 PoC után kell beállítani.',
        'no_ffprobe' => 'Nem található az ffprobe (FFPROBE_PATH), a felvételek hosszát nem tudjuk mérni.',
    ],

    'ai' => [
        'label' => 'AI-feladatok',
        'none_active' => 'Még egyik AI-feladatnak sincs aktív promptja.',
        'ok' => ':count feladat futtatható: prompt, kulcs és egységár rendben.',
        'missing_prompt' => 'A(z) :task feladat promptja hiányzik: :prompt.',
        'missing_key' => 'A(z) :task feladat szolgáltatójának (:provider) nincs API-kulcsa.',
        'missing_price' => 'Nincs egységár ezekhez a modellekhez: :models. A költségük nem számolódik.',
    ],

    'environment_policy' => [
        'label' => 'Környezeti szabályok',
        'ok' => 'A beállítások illenek a környezethez (:environment).',
        'debug_on' => 'Élesben az APP_DEBUG nem lehet bekapcsolva.',
        'url_not_https' => 'Élesben az APP_URL csak https lehet.',
        'staging_unprotected' => 'A staging nincs védve (MEGIS_STAGING_USER vagy MEGIS_STAGING_ALLOWED_IPS).',
        'staging_allowlist_empty' => 'Üres a MEGIS_MAIL_ALLOWLIST. Csak akkor rendben, ha a MAIL_MAILER levélcsapdára (pl. Mailpit) mutat.',
    ],

];
