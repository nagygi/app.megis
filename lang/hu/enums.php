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

    'value_origin' => [
        'admin' => 'Értéktár',
        'diagnosis' => 'Diagnózisnál felvett',
        'ai' => 'AI által detektált',
        'organization' => 'Szervezet által felvett',
    ],

    'version_event_type' => [
        'archived' => 'Archiválva',
        'restored' => 'Visszaállítva',
        'reviewed' => 'Átnézve',
    ],

    'diagnosis_level' => [
        'organization' => 'Szervezet',
        'team' => 'Csapat',
        'user' => 'Felhasználói',
    ],

    'diagnosis_status' => [
        'draft' => 'Draft',
        'pending_approval' => 'Jóváhagyásra vár',
        'published' => 'Publikált',
    ],

    'diagnosis_creator' => [
        'admin' => 'Admin',
        'aggregated' => 'Aggregált',
        'automatic' => 'Automatikus',
    ],

    'score_state' => [
        'scored' => 'Pontozva',
        'no_trace' => 'Nincs nyoma',
        'insufficient_data' => 'Nincs elég adat',
        'pending' => 'Számításra vár',
    ],

    'experience_state' => [
        'answered' => 'Válaszolt',
        'dont_know' => 'Nem tudom',
        'missing' => 'Hiányzik',
    ],

    'tree_element' => [
        'root' => 'Értékek (gyökér)',
        'trunk' => 'Purpose (törzs)',
        'crown' => 'Értékajánlat (korona)',
        'shadow' => 'Impact (árnyék)',
    ],

    'inbox_item_type' => [
        'classification' => 'Besorolás',
        'processing_error' => 'Feldolgozási hiba',
        'feedback' => 'Visszajelzés',
        'invitation' => 'Meghívó',
        'completion' => 'Kitöltés',
        'threshold' => 'Küszöb',
        'publication' => 'Publikálás',
        'mirror_booking' => 'Értéktükör-foglalás',
    ],

    'ai_call_status' => [
        'succeeded' => 'Sikeres',
        'failed' => 'Sikertelen',
    ],

    'material_context' => [
        'public_mirror' => 'Nyilvános Értéktükör',
        'mirror' => 'Értéktükör',
        'admin_upload' => 'Admin feltöltés',
    ],

    'material_source' => [
        'recording' => 'elmondva',
        'text' => 'leírva',
        'audio_upload' => 'feltöltött hang',
        'text_upload' => 'feltöltött leirat',
    ],

    'material_status' => [
        'pending' => 'sorban áll',
        'transcribing' => 'átírás',
        'awaiting_speakers' => 'beszélők hozzárendelésére vár',
        'analyzing' => 'elemzés',
        'processed' => 'kész',
        'failed' => 'sikertelen',
    ],

];
