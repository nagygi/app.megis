<?php

return [

    'speaker' => 'Beszélő :number',
    'doc_not_supported' => 'A .doc fájlt nem tudjuk beolvasni. Mentsd docx-ként, és töltsd fel újra.',
    'unsupported' => 'Ezt a fájltípust nem tudjuk beolvasni: :extension.',
    'unreadable' => 'Nem tudtuk beolvasni a fájlt.',
    'empty' => 'A fájlban nincs szöveg.',
    'too_long' => 'A hangfájl legfeljebb :minutes perces lehet.',
    'text_too_long' => 'A fájl szövege legfeljebb :characters karakter lehet (nagyjából 10 perc beszéd). Rövidítsd, vagy írd le a lényegét.',
    'failure_summary' => 'Nem tudtuk feldolgozni: :name',

    // The situation of an occasion's material in the detection and scoring prompts (13 · 13.2).
    'engine_situation' => "# A szöveg helyzete\n\nA szöveg egy szervezeti folyamat egyik alkalmáról szól: „:process”:rhythm:parent. Ez a helyzet megértéséhez kell. Az értékeket továbbra is csak abból olvasd ki, ami a szövegben van.",
    'engine_situation_rhythm' => ', ritmusa: :rhythm',
    'engine_situation_parent' => ', a(z) „:parent” folyamat része',

];
