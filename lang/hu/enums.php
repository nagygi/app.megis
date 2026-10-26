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
        'consent_given' => 'Hozzájárulás megadva',
        'consent_revoked' => 'Hozzájárulás visszavonva',
        'impersonation_started' => 'Belépés a nevében: kezdés',
        'impersonation_ended' => 'Belépés a nevében: befejezés',
        'impersonation_action' => 'Módosítás a nevében',
        'email_changed' => 'E-mail cím módosítása',
        'account_deletion_requested' => 'Fióktörlés kérve',
        'account_deleted' => 'Fiók és adatok törölve',
        'recording_deleted' => 'Felvétel hangja törölve',
        'calendar_connected_by_admin' => 'Naptár betöltve a nevében (admin)',
    ],

    'capability_level' => [
        'individual' => 'egyén',
        'team' => 'csapat',
        'organization' => 'szervezet',
        'ecosystem' => 'ökoszisztéma',
    ],

    'capability_status' => [
        'used' => 'használt',
        'candidate' => 'jelölt',
        'literature' => 'csak irodalom',
        'set_aside' => 'félretett',
    ],

    'capability_nature' => [
        'capability' => 'képesség',
        'skill_like' => 'készség-jellegű',
        'questionable' => 'kérdéses',
    ],

    'pattern_type' => [
        'capability' => 'képesség-minta',
        'precondition' => 'előfeltétel-minta',
        'detector_error' => 'detektor-hibaminta',
    ],

    'pattern_subtype' => [
        'presence' => 'jelenlét-jel',
        'absence' => 'hiány-jel',
        'anti_pattern' => 'anti-minta',
        'borderline' => 'határeset',
    ],

    'pattern_maturity' => [
        'hypothesis_literature' => 'hipotézis (irodalom)',
        'hypothesis_own' => 'hipotézis (saját megfigyelés)',
        'seen_once' => 'egy esetben láttuk',
        'used' => 'használt',
        'obsolete' => 'elavult',
        'rejected' => 'elvetett',
    ],

    'pattern_validity' => [
        'valid' => 'érvényes',
        'obsolete' => 'elavult',
        'rejected' => 'elvetett',
    ],

    'pattern_fit' => [
        'exact' => 'pontos',
        'partial' => 'részleges',
    ],

    'pattern_origin' => [
        'own_case' => 'saját eset',
        'external' => 'külső forrás',
        'both' => 'mindkettő',
    ],

    'level_marker' => [
        'boundary' => 'határfelület',
        'micro' => 'mikro szervezet',
    ],

    'pattern_product' => [
        'full' => 'Teljes',
        'snapshot_internal' => 'Snapshot Belső',
        'snapshot_external' => 'Snapshot Külső',
        'value_measurement' => 'Értékmérés',
        'bridge' => 'Bridge',
        'operating_system' => 'OS',
        'mirror' => 'Tükör',
        'testpad' => 'Tesztpad',
        'detector' => 'detektor (tiltó szabály)',
    ],

    'capability_rule_area' => [
        'trace' => 'nyom',
        'state' => 'állapot',
        'confidence' => 'konfidencia',
        'maturity' => 'érettség',
        'priority' => 'prioritás',
        'protection' => 'védelem',
        'precondition' => 'előfeltétel',
        'development' => 'fejlesztés',
    ],

    'trace_direction' => [
        'positive' => 'jelenlét',
        'negative' => 'hiány',
        'mixed' => 'vegyes',
    ],

    'trace_directness' => [
        'observed' => 'megfigyelt viselkedés',
        'self_report' => 'önbevallás',
        'others_opinion' => 'más véleménye',
        'interpretation' => 'szakértői értelmezés',
    ],

    'capability_state' => [
        'present' => 'jelen lehet',
        'absent' => 'hiányzik',
        'no_trace' => 'nincs nyoma',
        'no_data' => 'nincs adat',
    ],

    'capability_confidence' => [
        'low' => 'alacsony',
        'medium' => 'közepes',
        'high' => 'magas',
        'disputed' => 'vitatott',
    ],

    'finding_status' => [
        'proposed' => 'javasolt',
        'confirmed' => 'megerősítve',
        'rejected' => 'elvetve',
    ],

    'finding_kind' => [
        'pattern' => 'minta',
        'interpretation' => 'értelmezés',
    ],

    'precondition_value' => [
        'present' => 'megvan',
        'partial' => 'részben',
        'absent' => 'nincs',
        'unknown' => 'nem tudjuk',
    ],

    'capability_measurement_status' => [
        'pending' => 'vár',
        'analyzing' => 'fut',
        'processed' => 'kész',
        'failed' => 'hiba',
        'skipped' => 'kihagyva',
    ],

    'trace_candidate' => [
        'new_pattern' => 'új minta jelölt',
        'missing_capability' => 'hiányzó képesség jelölt',
    ],

    'capability_rule_status' => [
        'agreed' => 'megállapodott',
        'decided' => 'döntés',
        'accepted' => 'javaslat, elfogadva',
        'proposal' => 'javaslat',
    ],

    'value_origin' => [
        'admin' => 'Értéktár',
        'diagnosis' => 'Diagnózisnál felvett',
        'ai' => 'AI által detektált',
        'organization' => 'Szervezet által felvett',
        'user' => 'Felhasználó által felvett',
    ],

    'value_grade' => [
        'strong' => 'erős',
        'medium' => 'közepes',
        'weak' => 'gyenge',
    ],

    'value_svs_status' => [
        'yes' => 'igen',
        'x_coded' => 'nem (X-kódolt)',
        'to_verify' => 'nem (ellenőrizendő)',
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
        'process' => 'Folyamat',
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
        'capability_review' => 'Képességek átnézése',
        'pattern_review' => 'Minta felülvizsgálata',
        'capability_candidate' => 'Minta- vagy képesség-jelölt',
    ],

    'ai_call_status' => [
        'succeeded' => 'Sikeres',
        'failed' => 'Sikertelen',
    ],

    'material_context' => [
        'public_mirror' => 'Nyilvános Értéktükör',
        'mirror' => 'Értéktükör',
        'admin_upload' => 'Admin feltöltés',
        'dictation' => 'Elmondott mező',
        'occasion' => 'Alkalom anyaga',
    ],

    'material_source' => [
        'recording' => 'elmondva',
        'text' => 'leírva',
        'audio_upload' => 'feltöltött hang',
        'text_upload' => 'feltöltött leirat',
    ],

    'mirror_status' => [
        'processing' => 'feldolgozás alatt',
        'ready' => 'kész',
        'failed' => 'sikertelen',
        'awaiting_confirmation' => 'kész',
        'claimed' => 'kész',
    ],

    'material_status' => [
        'pending' => 'sorban áll',
        'transcribing' => 'átírás',
        'awaiting_speakers' => 'beszélők hozzárendelésére vár',
        'analyzing' => 'elemzés',
        'processed' => 'kész',
        'failed' => 'sikertelen',
    ],

    'main_diagnosis_stage' => [
        'draft' => 'Vázlat',
        'invited' => 'Meghívva',
        'filling' => 'Kitöltés',
        'running' => 'Fut',
        'reading' => 'Olvasat',
        'published' => 'Publikálva',
        'closed' => 'Lezárva',
    ],

    'main_diagnosis_subject' => [
        'team' => 'Csapat',
        'individual' => 'Magánszemély',
        'person' => 'Egy ember, a cég értékeivel',
    ],

    'participant_role' => [
        'leader' => 'Vezető',
        'peer' => 'Társ',
    ],

    'consent_kind' => [
        'answers' => 'A válaszaim névvel csak a mégisnél',
        'quotes' => 'Az idézeteim név nélkül',
    ],

    'process_kind' => [
        'routine' => 'rutin',
        'one_off' => 'egyszeri',
        'excluded' => 'nem mérjük',
    ],

    'process_source' => [
        'calendar' => 'naptár',
        'conversation' => 'beszélgetés',
        'manual' => 'kézi',
    ],

    'process_confidence' => [
        'high' => 'magas',
        'medium' => 'közepes',
        'low' => 'alacsony',
        'manual' => 'kézzel',
        'conversation' => 'beszélgetésből',
    ],

    'process_rhythm' => [
        'weekly' => 'heti',
        'biweekly' => 'kétheti',
        'monthly' => 'havi',
        'quarterly' => 'negyedéves',
        'occasional' => 'alkalmi',
    ],

    'metric_rhythm' => [
        'weekly' => 'heti',
        'monthly' => 'havi',
        'quarterly' => 'negyedéves',
    ],

    'diagnosis_step' => [
        'own_values' => 'Saját értékek',
        'organization_values' => 'A szervezet értékei',
        'brand' => 'A márkád alapja',
        'experience' => 'A megélésed',
        'processes' => 'Folyamatok',
        'visibility' => 'Ki lát mit',
    ],

];
