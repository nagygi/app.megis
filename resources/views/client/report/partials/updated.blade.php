{{-- "frissítve · {dátum}" on a chapter after a correction or after feedback (21 · 21.4, 28 · 28.1). --}}
@php($update = $updates->get($chapterKey))
@if ($update)
    <p class="text-sm font-semibold text-green">
        {{ __($update->reason === \App\Models\ChapterUpdate::FEEDBACK ? 'report.updated_feedback' : 'report.updated', ['date' => $update->created_at->format('m.d.')]) }}
    </p>
@endif
