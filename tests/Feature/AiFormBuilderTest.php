<?php

declare(strict_types=1);

use App\Models\Form;
use App\Models\User;
use App\Services\AiFormBuilderService;
use App\Livewire\FormBuilder;
use App\Livewire\FormBuilderChat;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'ai.gemini.api_key' => 'test-key',
        'ai.gemini.model' => 'gemini-2.5-flash',
        'ai.features.form_builder' => true,
    ]);
});

it('gracefully handles missing api key or disabled feature', function () {
    config(['ai.features.form_builder' => false]);
    $service = new AiFormBuilderService();
    expect($service->isEnabled())->toBeFalse();

    $result = $service->generateFormSchema([], [], 'Hello');
    expect($result)->toBeNull();
});

it('sends correctly structured requests and parses json from gemini api', function () {
    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'AI Generated Survey',
                                    'description' => 'A nice generated survey',
                                    'message' => 'I added name and rating fields.',
                                    'schema' => [
                                        'pages' => [
                                            [
                                                'id' => 'page_test',
                                                'title' => 'Page 1',
                                                'description' => '',
                                                'fields' => [
                                                    [
                                                        'id' => 'field_name',
                                                        'type' => 'text',
                                                        'label' => 'Full Name',
                                                        'placeholder' => 'Enter your name',
                                                        'required' => true,
                                                        'options' => [],
                                                        'analyze_sentiment' => false,
                                                        'conditionalLogic' => null,
                                                    ]
                                                ]
                                            ]
                                        ]
                                    ]
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ])
    ]);

    $service = new AiFormBuilderService();
    $result = $service->generateFormSchema([], [], 'Create survey');

    expect($result)->not->toBeNull()
        ->and($result['title'])->toBe('AI Generated Survey')
        ->and($result['description'])->toBe('A nice generated survey')
        ->and($result['message'])->toBe('I added name and rating fields.')
        ->and($result['schema']['pages'])->toHaveCount(1)
        ->and($result['schema']['pages'][0]['fields'][0]['label'])->toBe('Full Name');
});

it('renders the chat component and updates messages on prompt submission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Http::fake([
        'generativelanguage.googleapis.com/*' => Http::response([
            'candidates' => [
                [
                    'content' => [
                        'parts' => [
                            [
                                'text' => json_encode([
                                    'title' => 'AI Generated Form',
                                    'description' => '',
                                    'message' => 'Here is your contact form.',
                                    'schema' => [
                                        'pages' => [
                                            [
                                                'id' => 'page_contact',
                                                'title' => 'Page 1',
                                                'fields' => []
                                            ]
                                        ]
                                    ]
                                ])
                            ]
                        ]
                    ]
                ]
            ]
        ])
    ]);

    Livewire::test(FormBuilderChat::class, [
        'schema' => ['pages' => []],
        'formId' => null,
    ])
        ->assertSet('prompt', '')
        ->assertSet('isGenerating', false)
        ->set('prompt', 'Create a contact form')
        ->call('sendMessage')
        ->assertSet('prompt', '')
        ->assertSet('isGenerating', false)
        ->assertDispatched('updateChatHistory')
        ->assertDispatched('aiSchemaGenerated');
});

it('parent FormBuilder component handles events from FormBuilderChat and toggles view', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(FormBuilder::class, ['formId' => null])
        ->assertSet('showAiChat', true)
        ->call('toggleAiPanel')
        ->assertSet('showAiChat', false)
        ->dispatch('updateChatHistory', [['role' => 'user', 'content' => 'hello']])
        ->assertSet('aiChatHistory', [['role' => 'user', 'content' => 'hello']])
        ->assertSet('isDirty', true)
        ->dispatch('aiSchemaGenerated', [
            'pages' => [
                [
                    'id' => 'page_test_1',
                    'title' => 'New Page',
                    'description' => 'A description',
                    'fields' => [
                        [
                            'id' => 'field_name',
                            'type' => 'text',
                            'label' => 'Name',
                            'placeholder' => '',
                            'required' => true,
                            'options' => [],
                            'analyze_sentiment' => false,
                            'conditionalLogic' => null,
                        ]
                    ]
                ]
            ],
            'title' => 'Updated Form Title',
            'description' => 'Updated description'
        ])
        ->assertSet('title', 'Updated Form Title')
        ->assertSet('description', 'Updated description')
        ->assertSet('pages.0.title', 'New Page')
        ->assertSet('pages.0.fields.0.label', 'Name');
});
