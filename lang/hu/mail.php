<?php

return [

    'greeting' => 'Szia!',
    'salutation' => 'mégis',

    // The shared footer of every mail (resources/views/vendor/mail).
    'layout' => [
        'tagline' => 'A mégis segít, hogy a szervezet értékeiből tett legyen.',
    ],

    'admin_invitation' => [
        'subject' => 'Meghívó a mégis admin felületére',
        'body' => ':inviter meghívott a mégis admin felületére. Állítsd be a jelszavad.',
        'action' => 'Jelszó beállítása',
        'validity' => 'A link 24 óráig él.',
    ],

    'admin_password_reset' => [
        'subject' => 'Új jelszó-beállító link a mégis admin felületére',
        'body' => ':admin új jelszó-beállító linket kért neked. A régi jelszavad már nem érvényes.',
        'action' => 'Új jelszó beállítása',
        'validity' => 'A link 24 óráig él.',
    ],

    'password_changed' => [
        'subject' => 'Megváltozott a jelszavad',
        'body' => 'A jelszavad :time-kor megváltozott.',
        'admin_note' => 'Ha nem te voltál, szólj egy szuperadminnak.',
        'customer_note' => 'Ha nem te voltál, írj nekünk: :email',
    ],

    'admin_locked' => [
        'subject' => 'Zárolt admin fiók: :name',
        'body' => ':name (:email) admin fiókja 10 sikertelen belépési kísérlet után zárolódott.',
        'unlock' => 'Feloldás konzolból: php artisan megis:admin:unlock :email',
    ],

    'mirror_confirmation' => [
        'subject' => 'Erősítsd meg a mégis-fiókodat',
        'body' => 'Köszönjük, hogy belenéztél az Értéktükörbe. Erősítsd meg az e-mail címedet, és a tükröd a fiókodba kerül: ott bármikor visszanézheted az értékeidet kibontva, és a teljes képet is.',
        'action' => 'Megerősítem',
        'validity' => 'A link 24 óráig él. Ha addig nem erősíted meg, a tükröd törlődik.',
    ],

    'client_invitation' => [
        'subject' => 'Meghívó a mégis-be',
        'body' => ':inviter meghívott a mégis-be, a(z) :organization felhasználójaként.',
        'action' => 'Regisztráció',
        'validity' => 'A link 14 napig él.',
    ],

    'organization_added' => [
        'subject' => 'Hozzáadtunk a(z) :organization szervezethez',
        'body' => 'Hozzáadtunk a(z) :organization szervezethez a mégis-ben.',
        'action' => 'Belépés',
    ],

    'login_code' => [
        'subject' => 'A belépő kódod: :code',
        'body' => 'A belépő kódod:',
        'action' => 'Belépek',
        'validity' => 'A kód és a link 15 percig él. Ha nem te kérted, hagyd figyelmen kívül.',
    ],

    'password_confirmation_code' => [
        'subject' => 'Megerősítő kód: :code',
        'body' => 'A jelszó beállításához ezt a kódot írd be:',
        'validity' => 'A kód 15 percig él. Ha nem te kérted, hagyd figyelmen kívül.',
    ],

    'email_changed' => [
        'subject' => 'Megváltozott a mégis-fiókod e-mail címe',
        'old_address' => 'A mégis-fiókod e-mail címe erre változott: :email. Ha nem te voltál, írj nekünk: :support.',
        'new_address' => 'Ez lett a mégis-fiókod e-mail címe. Mostantól ide küldjük a leveleket.',
    ],

    'error_alert' => [
        'subject' => '[:environment] Hiba a mégis-ben: :error',
        'intro' => 'Hiba történt a mégis :environment környezetében.',
        'environment' => 'Környezet',
        'error' => 'Hiba',
        'message' => 'Üzenet',
        'url' => 'URL',
        'job' => 'Job',
        'time' => 'Időpont',
        'log_id' => 'Napló-azonosító',
        'throttle_note' => 'Ugyanez a hiba :minutes percig nem küld újabb levelet. A részletek a naplóban vannak.',
    ],

    'diagnosis_leader' => [
        'subject' => 'Elindult a(z) :subject diagnózisa',
        'body_new' => ':admin elindította a(z) :subject diagnózisát a mégis-ben, és téged kért meg, hogy vezesd.',
        'body_existing' => 'Elindult a(z) :subject diagnózisa a mégis-ben.',
        'timing' => 'Kb. :minutes perc, :date-ig.',
        'timing_open' => 'Kb. :minutes perc. A kezdés napja egyeztetés alatt.',
        'action' => 'Kezdjük',
    ],

    'diagnosis_peer' => [
        'subject' => 'Hogyan látod te a(z) :subject működését?',
        'body' => ':leader kíváncsi, hogyan látod te a(z) :subject működését. Kb. :minutes perc.',
        'action' => 'Kezdem',
    ],

    // From the Futás tab (19 · E-mailek).
    'diagnosis_reminder' => [
        'leader_subject' => 'Folytatod? · :subject',
        'leader_body' => 'Még :count lépés van hátra a(z) :subject diagnózisából, kb. :minutes perc.',
        'leader_action' => 'Folytatom',
        'peer_subject' => ':leader kíváncsi, hogyan látod te',
        'peer_body' => ':leader kíváncsi, hogyan látod te. Kb. :minutes perc.',
        'peer_action' => 'Kezdem',
        'threshold_subject' => 'Még néhány társ hangja kell · :subject',
        'threshold_body' => 'A rés csak akkor látszik, ha legalább 3 társ válaszol. Most :answered válaszolt, még :missing kell. Szólnál nekik?',
        'threshold_action' => 'Társak',
        'window_subject' => 'Új dátum · :subject',
        'window_body' => 'A(z) :subject diagnózisa :date-ra készül el.',
        'window_action' => 'Megnézem',
    ],

    // The 8:00 summary and the booking (22, question 2).
    'inbox_digest' => [
        'subject' => 'Beérkező · :count nyitott sor',
        'intro' => 'Ma :count dolog vár rád a szervezeteidnél:',
        'action' => 'Beérkező',
    ],

    'mirror_booking' => [
        'subject' => 'Időpontot foglalna valaki',
        'action' => 'Értékmérések',
    ],
    'mirror_interest' => [
        'subject' => 'Érdeklődés az Értéktükörből: :name',
        'intro' => ':name az Értéktükör után ezekre kíváncsi:',
        'values' => 'Az értékei: :values',
        'reply' => 'Erre a levélre válaszolva neki írsz: :email',
        'action' => 'Az ügyfél adatlapja',
    ],

    // Publishing (21 · E-mailek).
    'diagnosis_published' => [
        'leader_subject' => 'Elkészült a(z) :subject diagnózisa',
        'leader_body' => 'Elkészült a(z) :subject diagnózisa.',
        'leader_action' => 'Megnézem',
        'booking_lead' => 'Ha átbeszélnéd velünk:',
        'booking_action' => 'Időpontot foglalok',
        'peer_subject' => 'Itt az Útravalód',
        'peer_body' => 'Elkészült a(z) :subject diagnózisa, és vele az Útravalód: a te értékeid, ahogy a helyzeteidben látszanak.',
        'peer_action' => 'Megnézem',
    ],

    // "Válaszolok" (22, question 1).
    'feedback_reply' => [
        'subject' => 'A mégis válasza · :chapter',
        'greeting' => 'Kedves :name!',
        'lead' => 'Köszönjük a visszajelzésedet. Ezt írtad:',
        'action' => 'A riport',
    ],

    // "Törlés megtörtént" (31 · E-mailek).
    'account_deleted' => [
        'subject' => 'Töröltük a fiókodat',
        'body' => 'A fiókodat és az adataidat töröltük a mégis-ből. Ha nem te kérted, írj nekünk: :support.',
        // A superadmin archived the account (33 · 33.8).
        'body_by_admin' => 'A mégis adminisztrátora törölte a fiókodat és az adataidat. Ha kérdésed van, írj nekünk: :support.',
    ],

];
