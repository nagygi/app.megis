<?php

namespace App\Jobs;

use App\Models\Organization;
use App\Models\ProcessConversation;
use App\Services\Processes\ConversationService;
use Illuminate\Database\Eloquent\Model;
use Throwable;

/**
 * The AI extract after one answer of the conversation (10 · 10.2).
 */
class ExtractConversationProcesses extends AiTaskJob
{
    public function __construct(
        public ProcessConversation $conversation,
        public int $question,
    ) {
        parent::__construct();
    }

    public function handle(ConversationService $conversations): void
    {
        $conversations->extract($this->conversation->fresh(), $this->question);
    }

    protected function markFailed(?Throwable $exception): void
    {
        app(ConversationService::class)->extractFailed($this->conversation->fresh(), $this->question);
    }

    protected function failureSummary(): string
    {
        return __('processes.conversation.failure_summary', ['name' => $this->conversation->user->name]);
    }

    protected function failureOrganization(): ?Organization
    {
        return $this->conversation->organization;
    }

    protected function failureSubject(): ?Model
    {
        return $this->conversation;
    }
}
