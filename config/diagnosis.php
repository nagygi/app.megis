<?php

/*
|--------------------------------------------------------------------------
| Main diagnoses (17, 23, 25)
|--------------------------------------------------------------------------
|
| The diagnosis flow of each product, by the product key. A product without
| an entry here has no diagnosis flow: Snapshot · Külső cannot be started
| yet (17 · 17.2). The A1 preview and V1 read the same table (17 · 17.3).
|
*/

return [

    'products' => [
        'snapshot_internal' => [
            // The default length of the time window, from the start (17 · 17.2).
            'days' => 14,
            'has_peers' => false,
            // At most this many measured processes (11, 18 · 18.1); null: no limit.
            'max_measured_processes' => 2,
            // The capability measurement reads its materials (NM-12).
            'capability_measurement' => true,
        ],
        'full' => [
            'days' => 42,
            'has_peers' => true,
            'max_measured_processes' => null,
            'capability_measurement' => true,
        ],
    ],

    // The leader's steps by subject, in the order of V1 (17 · 17.3). A private
    // person has no "A szervezet értékei": they come from the own values (08).
    'steps' => [
        'team' => ['own_values', 'organization_values', 'brand', 'experience', 'processes', 'visibility'],
        'person' => ['own_values', 'organization_values', 'brand', 'experience', 'processes', 'visibility'],
        'individual' => ['own_values', 'brand', 'experience', 'processes', 'visibility'],
    ],

    // A peer's steps now; "Hozzáteszel" runs while the diagnosis runs (25 · 25.3).
    'peer_steps' => ['own_values', 'experience'],

    // Estimated minutes per step (23, question 1); a read-only step takes one minute.
    'minutes' => [
        'own_values' => 2,
        'organization_values' => 3,
        'brand' => 2,
        'experience' => 3,
        'processes' => 2,
        'visibility' => 1,
    ],
    'readonly_step_minutes' => 1,

    // The minutes in the peer invitation and on T1 (25 · 25.2).
    'peer_minutes' => 8,

    // Reading starts this many days before publishing; the leader sees it only for information (17 · 17.2).
    'reading_offset_days' => 4,

    // Own values on V2·b: the interface suggests three (24, question 1).
    'own_values' => [
        'min' => 1,
        'max' => 5,
        'suggested' => 3,
        // A private person's own values are also the organization's values (08).
        'individual_min' => 3,
    ],

    // A burst of events (materials, experiences) is recalculated once (plan 3.6).
    'recalculation_delay_seconds' => (int) env('DIAGNOSIS_RECALCULATION_DELAY', 20),

];
