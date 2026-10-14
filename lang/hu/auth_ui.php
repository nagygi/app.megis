<?php

return [

    'password_hint' => 'Legalább :min karakter. Ismert, kiszivárgott jelszó nem használható.',
    'email' => 'E-mail cím',
    'password' => 'Jelszó',
    'password_confirmation' => 'Jelszó még egyszer',
    'new_password' => 'Új jelszó',
    'current_password' => 'Jelenlegi jelszó',
    'throttled' => 'Túl sok próbálkozás. Próbáld újra :seconds másodperc múlva.',

    'admin' => [
        'login_title' => 'Admin belépés',
        'login_button' => 'Belépek',
        'failed' => 'Hibás e-mail cím vagy jelszó.',
        'locked' => 'A fiókod zárolva van. Kérd egy szuperadmin segítségét.',
        'forgot_password' => 'Kérd meg egy másik admint, hogy küldjön új beállító linket.',
        'session_expired' => 'A munkameneted lejárt. Lépj be újra.',
        'logout' => 'Kijelentkezés',
        'setup_title' => 'Jelszó beállítása',
        'setup_lead' => 'Állítsd be a jelszavad a mégis admin felületéhez.',
        'setup_button' => 'Beállítom és belépek',
        'setup_invalid_title' => 'Ez a link már nem érvényes',
        'setup_invalid' => 'A link lejárt vagy már felhasználták. Kérj újat egy másik admintól.',
    ],

    'customer' => [
        'login_title' => 'Belépés',
        'login_button' => 'Belépek',
        'with_password' => 'Jelszóval lépek be',
        'with_code' => 'Inkább kódot kérek',
        'code_sent' => 'Ha ehhez a címhez tartozik fiók, elküldtük a belépő kódot.',
        'no_account' => 'Még nincs fiókod?',
        'no_account_link' => 'Nézz bele a tükörbe.',
        'failed' => 'Hibás e-mail cím vagy jelszó.',
        'password_paused' => 'Túl sok hibás jelszó. 15 percig csak kóddal léphetsz be.',
        'invitation_context' => ':inviter meghívott a(z) :organization felhasználójaként.',

        'code_title' => 'Nézd meg a leveled',
        'code_lead' => 'Küldtünk egy belépő kódot és egy linket ide: :email. Ha másik eszközön olvasod a levelet, írd be ide a kódot.',
        'code' => 'Belépő kód',
        'code_help' => 'A levélben kapott 6 jegyű kód. 15 percig él.',
        'code_button' => 'Belépek',
        'code_invalid' => 'Hibás vagy lejárt kód. Ellenőrizd, vagy kérj újat.',
        'resend' => 'Újraküldöm',
        'resend_wait' => ':seconds másodperc múlva küldhetsz újat.',
        'other_email' => 'Másik e-mail',

        'link_title' => 'Belépés',
        'link_lead' => 'Egy gombnyomás, és bent vagy.',
        'link_button' => 'Belépek',
        'link_expired_title' => 'Ez a link már lejárt',
        'link_expired' => 'A belépő link 15 percig él, és csak egyszer használható.',
        'request_new' => 'Újat kérek',

        'logout' => 'Kijelentkezés',
        'paused_title' => 'A hozzáférésed szünetel',
        'paused' => 'A hozzáférésed szünetel. Keresd a mégis kapcsolattartódat.',
    ],

    'registration' => [
        'title' => 'Regisztráció',
        'context' => ':inviter meghívott a(z) :organization felhasználójaként.',
        'last_name' => 'Vezetéknév',
        'first_name' => 'Keresztnév',
        'password_optional' => 'Jelszó',
        'password_optional_help' => 'Ha nem adsz meg jelszót, e-mailben kapott kóddal lépsz be.',
        'accept' => 'Elfogadom az :terms és az :privacy.',
        'accept_terms' => 'ÁSZF-et',
        'accept_privacy' => 'adatkezelési tájékoztatót',
        'accept_without_documents' => 'Elfogadom az ÁSZF-et és az adatkezelési tájékoztatót.',
        'button' => 'Regisztrálok',
        'expired_title' => 'Ez a meghívó már lejárt',
        'expired' => 'Ez a meghívó már lejárt. Kérj újat attól, aki meghívott.',
        'accept_required' => 'A regisztrációhoz el kell fogadnod az ÁSZF-et és az adatkezelési tájékoztatót.',
    ],

    'legal' => [
        'title' => 'Változtak a feltételek',
        'changed' => 'Változott a(z) :document.',
        'open' => 'Elolvasom',
        'accept' => 'Elfogadom',
        'logout' => 'Kijelentkezem',
        'impersonating' => 'A felhasználó nevében nem fogadhatod el. A következő belépéskor ő fogadja el.',
    ],

];
