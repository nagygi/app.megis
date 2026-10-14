<?php

namespace App\Services\Processes;

use App\Ai\AiContext;
use App\Ai\Tasks\ExtractProcessesTask;
use App\Enums\ProcessConfidence;
use App\Enums\ProcessKind;
use App\Enums\ProcessRhythm;
use App\Enums\ProcessSource;
use App\Jobs\ExtractConversationProcesses;
use App\Models\MainDiagnosis;
use App\Models\Organization;
use App\Models\Process;
use App\Models\ProcessConversation;
use App\Models\User;
use App\Services\Ai\AiGateway;
use Illuminate\Support\Facades\DB;

/**
 * The conversation instead of the calendar (10): answers are saved, every
 * answer is extracted by the AI, and the chips become process cards on V6·d.
 */
class ConversationService
{
    public function __construct(private AiGateway $ai) {}

    public function forUser(Organization $organization, User $user, ?MainDiagnosis $main): ProcessConversation
    {
        return ProcessConversation::query()->firstOrCreate([
            'organization_id' => $organization->id,
            'user_id' => $user->id,
            'main_diagnosis_id' => $main?->id,
        ], ['answers' => [], 'extracts' => [], 'extracted_answers' => []]);
    }

    /**
     * Saves one answer and asks for its extract when it changed.
     */
    public function answer(ProcessConversation $conversation, int $question, string $answer): void
    {
        $answer = trim($answer);
        $answers = $conversation->answers ?? [];

        if (($answers[$question] ?? '') === $answer) {
            return;
        }

        $answers[$question] = $answer;
        $extracts = $conversation->extracts ?? [];

        if ($answer === '') {
            unset($extracts[$question]);
        }

        $conversation->update(['answers' => $answers, 'extracts' => $extracts]);

        if ($answer !== '') {
            ExtractConversationProcesses::dispatch($conversation, $question);
        }
    }

    /**
     * The model and prompt version are logged with the call (NFR · 11).
     */
    public function extract(ProcessConversation $conversation, int $question): void
    {
        $answer = $conversation->answer($question);

        if ($answer === '') {
            return;
        }

        $result = $this->ai->run(
            new ExtractProcessesTask(__('processes.conversation.questions.'.$question), $answer),
            AiContext::forOrganization($conversation->organization, $conversation),
        );

        $chips = collect($result->structured['processes'] ?? [])
            ->filter(fn (array $process) => filled($process['name'] ?? null))
            ->map(fn (array $process) => [
                'name' => mb_substr(trim($process['name']), 0, 255),
                'rhythm' => ProcessRhythm::tryFrom((string) ($process['rhythm'] ?? ''))?->value,
                'rhythm_label' => filled($process['rhythm_label'] ?? null) ? mb_substr($process['rhythm_label'], 0, 255) : null,
                'headcount' => isset($process['headcount']) ? (int) $process['headcount'] : null,
                'unclear' => ($process['kind'] ?? 'process') === 'unclear',
                // Named in the answer to the fourth question (10 · 10.2).
                'breakpoint' => $question === ProcessConversation::BREAKPOINT_QUESTION,
            ])
            ->unique(fn (array $chip) => mb_strtolower($chip['name']))
            ->values()
            ->all();

        DB::transaction(function () use ($conversation, $question, $answer, $chips): void {
            $fresh = ProcessConversation::query()->lockForUpdate()->findOrFail($conversation->id);

            // An answer changed in the meantime gets its own extract.
            if ($fresh->answer($question) !== $answer) {
                return;
            }

            $fresh->update([
                'extracts' => [...($fresh->extracts ?? []), $question => $chips],
                'extracted_answers' => [...($fresh->extracted_answers ?? []), $question => $answer],
            ]);
        });
    }

    /**
     * After the last attempt the answer stays without chips; the page stops waiting.
     */
    public function extractFailed(ProcessConversation $conversation, int $question): void
    {
        $conversation->update([
            'extracted_answers' => [...($conversation->extracted_answers ?? []), $question => $conversation->answer($question)],
        ]);
    }

    /**
     * The chip's × (10 · 10.2).
     */
    public function removeChip(ProcessConversation $conversation, int $question, int $index): void
    {
        $extracts = $conversation->extracts ?? [];
        unset($extracts[$question][$index]);
        $extracts[$question] = array_values($extracts[$question] ?? []);

        $conversation->update(['extracts' => $extracts]);
    }

    /**
     * "Válasz és tovább": process chips become cards "beszélgetésből", the
     * unclear ones go to "Bizonytalan" (10 · 10.3). An existing process with
     * the same name is not duplicated.
     */
    public function finish(ProcessConversation $conversation): int
    {
        return DB::transaction(function () use ($conversation): int {
            $count = 0;

            foreach (ProcessConversation::QUESTIONS as $question) {
                foreach ($conversation->chips($question) as $chip) {
                    $existing = Process::query()
                        ->whereBelongsTo($conversation->organization)
                        ->whereRaw('lower(name) = ?', [mb_strtolower($chip['name'])])
                        ->first();

                    if ($existing !== null) {
                        if ($chip['breakpoint']) {
                            $existing->update(['breakpoint_candidate' => true]);
                        }

                        continue;
                    }

                    Process::create([
                        'organization_id' => $conversation->organization_id,
                        'name' => $chip['name'],
                        'rhythm' => $chip['rhythm'],
                        'rhythm_label' => $chip['rhythm_label'],
                        'headcount' => $chip['headcount'],
                        'kind' => ProcessKind::Routine,
                        'source' => ProcessSource::Conversation,
                        'confidence' => ProcessConfidence::Conversation,
                        'unclear' => $chip['unclear'],
                        'breakpoint_candidate' => $chip['breakpoint'],
                    ]);
                    $count++;
                }
            }

            $conversation->update(['completed_at' => now()]);

            return $count;
        });
    }
}
