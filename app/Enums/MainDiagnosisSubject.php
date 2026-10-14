<?php

namespace App\Enums;

/**
 * Who a main diagnosis is about (17 · 17.1, 17.2).
 */
enum MainDiagnosisSubject: string
{
    // A team: the leader and the peers, measured against the organization's values.
    case Team = 'team';
    // A private-person organization: one person with their own values.
    case Individual = 'individual';
    // One user of a company, measured against the company's values, without peers.
    case Person = 'person';

    public function label(): string
    {
        return __('enums.main_diagnosis_subject.'.$this->value);
    }

    public function level(): DiagnosisLevel
    {
        return match ($this) {
            self::Team => DiagnosisLevel::Team,
            self::Individual => DiagnosisLevel::Organization,
            self::Person => DiagnosisLevel::User,
        };
    }
}
