<?php

return [

    'user_role' => [
        'admin' => 'Admin',
        'superadmin' => 'Szuperadmin',
    ],

    'admin_status' => [
        'invited' => 'Meghívva',
        'invitation_expired' => 'Meghívó lejárt',
        'active' => 'Aktív',
        'locked' => 'Zárolva',
        'inactive' => 'Inaktív',
    ],

    'organization_type' => [
        'organization' => 'Szervezet',
        'individual' => 'Magánszemély',
    ],

    'membership_status' => [
        'added' => 'Felvéve',
        'invited' => 'Meghívva',
        'invitation_expired' => 'Meghívó lejárt',
        'active' => 'Aktív',
        'inactive' => 'Inaktív',
    ],

    'membership_tag' => [
        'organization_leader' => 'szervezet vezetője',
        'employee' => 'munkatárs',
        'partner' => 'partner',
        'team_leader' => 'csapat vezető',
    ],

    'legal_document_type' => [
        'privacy_policy' => 'Adatkezelési tájékoztató',
        'terms' => 'ÁSZF',
        'cookie_policy' => 'Cookie tájékoztató',
    ],

    'audit_event_type' => [
        'impersonation_started' => 'Belépés a nevében: kezdés',
        'impersonation_ended' => 'Belépés a nevében: befejezés',
        'impersonation_action' => 'Módosítás a nevében',
        'email_changed' => 'E-mail cím módosítása',
    ],

];
