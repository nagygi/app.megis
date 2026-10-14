{{-- Status badge of a diagnosis (04 · 4.3). --}}
@props(['status'])

<x-ui.badge :tone="match ($status) {
    \App\Enums\DiagnosisStatus::Published => 'green',
    \App\Enums\DiagnosisStatus::PendingApproval => 'yellow',
    default => 'neutral',
}">{{ $status->label() }}</x-ui.badge>
