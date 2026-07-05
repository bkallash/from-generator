<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AiFormBuilderService;
use Livewire\Attributes\Reactive;
use Livewire\Component;

final class FormBuilderChat extends Component
{
    #[Reactive]
    public array $schema;

    public ?int $formId = null;
    public array $initialMessages = [];

    public array $messages = [];
    public string $prompt = '';
    public bool $isGenerating = false;
    public string $error = '';

    public function mount(): void
    {
        if (!empty($this->initialMessages)) {
            $this->messages = $this->initialMessages;
        } else {
            $this->resetChatHistory();
        }
    }

    /**
     * Send message to the AI Form Builder service and update parent.
     */
    public function sendMessage(AiFormBuilderService $aiService): void
    {
        $this->error = '';
        $userPrompt = trim($this->prompt);

        if (empty($userPrompt)) {
            return;
        }

        // Add user message to history
        $this->messages[] = [
            'role' => 'user',
            'content' => $userPrompt,
            'timestamp' => now()->format('H:i'),
        ];

        // Let parent know chat history changed (for potential saving later)
        $this->dispatch('updateChatHistory', $this->messages);

        $this->prompt = '';
        $this->isGenerating = true;

        // Dispatch browser event to scroll chat list
        $this->dispatch('scroll-chat-to-bottom');

        // We run the API call in a try/catch or let service handle it.
        // Wait, the service already catches errors and returns null.
        $result = $aiService->generateFormSchema($this->schema, array_slice($this->messages, 0, -1), $userPrompt);

        if ($result === null) {
            $this->error = 'Sorry, there was an issue communicating with the AI service. Please check your connection or try again.';
            $this->isGenerating = false;
            $this->dispatch('scroll-chat-to-bottom');
            return;
        }

        // Add AI response to history
        $this->messages[] = [
            'role' => 'ai',
            'content' => $result['message'],
            'timestamp' => now()->format('H:i'),
        ];

        // Notify parent of updated schema & history
        $this->dispatch('updateChatHistory', $this->messages);
        $this->dispatch('aiSchemaGenerated', [
            'pages' => $result['schema']['pages'],
            'title' => $result['title'],
            'description' => $result['description']
        ]);

        $this->isGenerating = false;
        $this->dispatch('scroll-chat-to-bottom');
    }

    /**
     * Reset chat to default greeting.
     */
    public function clearChat(): void
    {
        $this->resetChatHistory();
        $this->dispatch('updateChatHistory', $this->messages);
        $this->error = '';
        $this->prompt = '';
        $this->isGenerating = false;
    }

    private function resetChatHistory(): void
    {
        $this->messages = [
            [
                'role' => 'ai',
                'content' => "Hi! I'm your AI form building assistant. Describe the form you want to build and I'll generate it instantly. You can refine it with further comments!\n\nTry commands like:\n* *\"Create a job application form with name, resume upload, and email.\"*\n* *\"Make the email field required and add a checkbox for terms.\"*\n* *\"Add a new page for survey questions with a rating dropdown.\"*",
                'timestamp' => now()->format('H:i'),
            ]
        ];
    }

    public function render()
    {
        return view('livewire.form-builder-chat');
    }
}
