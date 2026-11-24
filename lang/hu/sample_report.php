<?php

/*
 * The sample report an invited member sees before their own (26 · 26.4):
 * made-up data, marked as a sample everywhere. The mégis reviews the words.
 */
return [

    'title' => 'Példa riport',
    'subtitle' => 'Így fog kinézni',
    'band_title' => 'Minta',
    'band' => 'Ez egy példa riport kitalált adatokkal. A te riportod a mégis csapat elemzése és jóváhagyása után jelenik meg.',
    'organization' => 'Minta Kft.',
    'lead' => 'A riport megmutatja, mennyire élnek az értékeitek a mindennapi működésben: a rutinokban, a döntésekben, abban, ahogy egymással dolgoztok.',
    'percent' => 64,
    'points' => '23 / 36 pont · 9 mérőpont',
    'values_label' => 'Értékek · 4 mérőpont',
    'values' => [
        ['name' => 'Egyenesség', 'meta' => 'a rossz hír is időben eljut', 'value' => 4.6],
        ['name' => 'Felelősségvállalás', 'meta' => 'a vezetői körben él, a projektekben kevésbé', 'value' => 3.4],
        ['name' => 'Együttműködés', 'meta' => '5-nek látjátok, 3-at mutattok', 'value' => 3.0],
        ['name' => 'Nyitottság', 'meta' => 'még csak szavak', 'value' => 2.0],
    ],
    'processes_label' => 'Folyamatok · 5 mérőpont',
    'processes' => [
        ['name' => 'Vezetői kör', 'meta' => 'hetente · 6 alkalom', 'value' => 4.2],
        ['name' => 'Ügyfél-review', 'meta' => 'kéthetente · 3 alkalom', 'value' => 3.8],
        ['name' => 'Onboarding', 'meta' => 'havonta · 1 alkalom', 'value' => 2.4],
    ],
    'takeaway_title' => 'Útravaló',
    'takeaway' => 'Az Egyenességre építhettek: a vezetői körben már beépült. A Nyitottság a következő lépés: hol mondjátok, és hol látszik?',
    'back' => 'Vissza a kezdőlapra',
    'link' => 'Így fog kinézni a riport · minta',

];
