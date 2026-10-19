<?php

return [

    'meta_title' => 'Értéktükör',
    'meta_description' => 'Mesélj egy helyzetet, és megmutatjuk, mely értékeid hallatszanak ki belőle. Belépés nélkül, ingyen.',

    // ET1 · nyitó
    'manifesto' => [
        'line1' => 'Az érték',
        'line2' => 'nem az a tőke,',
        'line3' => 'amit pénzben mérnek.',
        'body' => 'Hanem az, ami abban látszik, amit mondasz és amit teszel.',
        'closing' => 'Minden áldott nap.',
    ],
    'start' => 'Belenézek a tükörbe',
    'have_account' => 'Van fiókom',

    // ET2 · felvétel
    'title' => 'Értéktükör',
    'prompt' => 'Mesélj egy helyzetet a hétről, amikor nem az idő vagy a pénz számított, hanem az, ami igazán fontos neked.',
    'hint' => 'Legalább egy percig mesélj, ha teheted, többet.',
    'mode_record' => 'Elmondom',
    'mode_text' => 'Inkább leírom',
    'mode_file' => 'Fájlt töltök fel',
    'record_start' => 'Felvétel indítása',
    'record_stop' => 'Megállítom',
    'restart' => 'Újra',
    'recording' => 'Felvétel folyamatban',
    'motivation' => [
        'start' => 'Mesélj tovább…',
        'one_minute' => 'Jó kezdet. Mi történt utána?',
        'three_minutes' => 'Ebből már gazdag tükör lesz.',
    ],
    'warning' => 'Még két perc van hátra.',
    'max_reached' => 'Elérted a 10 percet, a felvétel megállt.',
    'short' => 'Rövid lett, egy percnél kevesebb. Ha teheted, mesélj még, de így is beküldheted.',
    'mic_denied' => 'Nem kaptunk hozzáférést a mikrofonhoz. Engedélyezd a böngészőben, vagy írd le inkább.',
    'unsupported' => 'Ez a böngésző nem tud hangot felvenni. Írd le inkább, vagy tölts fel fájlt.',
    'uploading' => 'Feltöltés…',
    'text_label' => 'A helyzet, leírva',
    'text_help' => 'Legalább néhány mondat. Minél konkrétabb, annál pontosabb a tükör.',
    'file_label' => 'Fájl',
    'file_help' => 'Formátum: :formats · legfeljebb :size MB. A .doc fájlt mentsd docx-ként.',
    'submit' => 'Kész · mutasd a tükröt',
    'throttled' => 'Most túl sok tükör készült erről a hálózatról. Próbáld újra egy óra múlva.',
    'recording_missing' => 'Előbb vedd fel, amit mesélnél.',

    // Feldolgozás
    'processing' => 'Olvassuk, amit mondtál…',
    'processing_hint' => 'Egy-két perc. Ezt a lapot nyitva hagyhatod.',
    'failed' => 'Nem tudtuk feldolgozni. Próbáld újra.',
    'try_again' => 'Újra próbálom',
    'failed_kept' => 'Amit küldtél, megvan, nem kell újra beküldened.',
    'start_over' => 'Inkább újrakezdem',
    'retry_limited' => 'Most többször nem próbálhatjuk újra. Kezdd újra, vagy nézz vissza egy óra múlva.',
    'expired' => 'Ez a tükör már nem elérhető. A névtelen tükör 24 óráig él.',

    // ET3 · az értékeid
    'values_title' => 'Az értékeid',
    'values_lead' => 'Ez hallatszik ki abból, amit mondtál.',
    'auto_with_quotes' => 'automatikus · idézetekkel',
    'not_mine' => 'nem az enyém',
    'mine_again' => 'mégis az enyém',
    'not_mine_label' => ':name: nem az enyém',
    'mine_again_label' => ':name: mégis az enyém',
    'no_values' => 'Ebből most nem hallottunk ki értéket. Próbáld újra egy konkrétabb helyzettel.',
    'continue' => 'A teljes kép',

    // ET5 · teljes kép
    'full_title' => 'Teljes kép',
    'back_to_values' => 'Az értékeid',
    'vision' => 'Kell egy vízió, ami a belső forrásodtól a hatásodig tart.',
    'your_values' => 'a te értékeid',
    'curious' => 'Kíváncsi vagy?',
    'curiosity' => [
        'inner' => ['question' => 'Hogyan jelennek meg a belső működésben?', 'hint' => 'a rutinjaitokban, a döntésekben, a rossz hír útján', 'short' => 'belső működés'],
        'outer' => ['question' => 'Hogyan látszanak kívülről?', 'hint' => 'a megjelenéseken, az ügyfél szemével', 'short' => 'kívülről'],
        'gap' => ['question' => 'Hol válik el a kettő?', 'hint' => 'ahol belül mást mondtok, mint kifelé', 'short' => 'a kettő között'],
        'capabilities' => ['question' => 'Mire építhetsz, mi hiányzik?', 'hint' => 'a képességek, amik az értékből tettet csinálnak', 'short' => 'képességek'],
    ],
    'see_answers' => 'Lépj be és nézd meg a válaszokat',
    'promise' => 'Részletesen, az Értéktárban: mit jelent, miben látszik, mi az 1 és a 6.',
    'and_more' => 'És még :count érték, böngészhetően.',
    'email' => 'E-mail cím',
    'consent_before' => 'Elfogadom az',
    'consent_terms' => 'ÁSZF',
    'consent_middle' => '-et és az',
    'consent_privacy' => 'adatvédelmi szabályzatot',
    'consent_required' => 'A fiókhoz el kell fogadnod az ÁSZF-et és az adatvédelmi szabályzatot.',
    'create_account' => 'Létrehozom a fiókot',
    'book' => 'Időpontot foglalok',
    'book_help' => 'A foglalási naptár új ablakban nyílik meg.',
    'finish' => 'Kész',
    'booking_saved' => 'Megnyitottuk a foglalási naptárat. A mégis látja, mire vagy kíváncsi.',
    'saved_to' => 'A tükröd a(z) :organization diagnózisai közé került. A mégis átnézi, mielőtt a platformon megjelenik.',

    'check_mail' => 'Nézd meg a leveled',
    'check_mail_lead' => 'Küldtünk egy megerősítő linket ide: :email. A link 24 óráig él.',
    'existing' => 'Ehhez az e-mailhez már van fiókod. Lépj be, és a tükröd hozzá kerül.',
    'login_password' => 'Jelszóval lépek be',
    'login_code' => 'Kódot kérek e-mailben',
    'code_sent' => 'Elküldtük a kódot ide: :email. 15 percig érvényes.',
    'code' => 'Belépő kód',
    'password' => 'Jelszó',
    'login' => 'Belépek',
    'login_failed' => 'Hibás e-mail cím vagy jelszó.',
    'code_invalid' => 'Hibás vagy lejárt kód.',

    // Megerősítés
    'confirm_title' => 'Még egy lépés',
    'confirm_lead' => 'Add meg a neved. Jelszót most nem kötelező beállítani: e-mailben kapott kóddal is beléphetsz, és később a Profilban is beállíthatod.',
    'confirm_button' => 'Kész, belépek',
    'confirm_expired' => 'Ez a link lejárt, vagy már felhasználták. A névtelen tükör 24 óráig él.',

    'diagnosis_name' => 'Értéktükör · :date',
    'booking_summary' => ':name időpontot foglalna',
    'booking_summary_curious' => ':name időpontot foglalna · kíváncsi: :curiosity',
    'someone' => 'Valaki',

    // 17 · 17.6: a mirror from the own menu belongs to a running diagnosis.
    'which_diagnosis' => 'Melyik diagnózishoz tartozzon?',
    'which_diagnosis_required' => 'Válaszd ki, melyik diagnózishoz tartozzon.',
    'back_to_diagnosis' => 'A tükröd elkészült: az új értékeidet a saját értékeid között nézheted át.',

];
