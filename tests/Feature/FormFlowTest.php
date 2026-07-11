<?php

use App\Models\Form;
use App\Models\User;
use App\Models\Submission;
use App\Models\FormDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

it('auto-saves draft progress and restores it on page load', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Job Application Form',
        'description' => 'Test form description',
        'slug' => 'job-app',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'description' => 'Basic info',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'text',
                            'label' => 'Full Name',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ],
                [
                    'id' => 'page_2',
                    'title' => 'Page 2',
                    'description' => 'Details',
                    'fields' => [
                        [
                            'id' => 'field_2',
                            'type' => 'text',
                            'label' => 'Company',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // 1. Save draft via auto-save endpoint
    $response = $this->postJson("/f/{$form->slug}/draft", [
        'data' => ['field_1' => 'John Doe'],
        'current_page' => 0,
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    // Assert draft exists in database with flat structure
    $draft = FormDraft::first();
    expect($draft)->not->toBeNull();
    expect($draft->data)->toBe(['field_1' => 'John Doe']);
    expect($draft->current_page)->toBe(0);

    // 2. Visit form with draft cookie — progress should be restored
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->get("/f/{$form->slug}");
    $response->assertOk();
    $response->assertSee('John Doe');
});

it('handles multi-page conditional logic and final submission with draft progress', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Conditional Form',
        'description' => 'Test conditional pages',
        'slug' => 'cond-form',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'description' => 'Experience check',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'radio',
                            'label' => 'Do you have work experience?',
                            'required' => true,
                            'options' => ['Yes', 'No']
                        ]
                    ]
                ],
                [
                    'id' => 'page_2',
                    'title' => 'Page 2',
                    'description' => 'Experience details',
                    'conditionalLogic' => [
                        'action' => 'show',
                        'triggerFieldId' => 'field_1',
                        'triggerValue' => 'Yes'
                    ],
                    'fields' => [
                        [
                            'id' => 'field_2',
                            'type' => 'text',
                            'label' => 'Describe experience',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ],
                [
                    'id' => 'page_3',
                    'title' => 'Page 3',
                    'description' => 'Final review',
                    'fields' => [
                        [
                            'id' => 'field_3',
                            'type' => 'text',
                            'label' => 'Confirm details',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ]
            ]
        ]
    ]);

    $hpTime = Crypt::encryptString((string) (now()->timestamp - 5));

    // 1. Save draft with "Yes" experience (page 2 should be visible)
    $response = $this->postJson("/f/{$form->slug}/draft", [
        'data' => ['field_1' => 'Yes', 'field_2' => 'Software Engineer'],
        'current_page' => 1,
    ]);
    $response->assertOk();

    $draft = FormDraft::first();

    // 2. Final submit — sends last page (page 3) fields through middleware
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->post("/f/{$form->slug}", [
            '_hp_time' => $hpTime,
            'field_3' => 'Verified Info',
        ]);

    $response->assertRedirect(route('forms.show', $form->slug));
    $response->assertSessionHas('success');

    // 3. Verify submission saved correctly with all fields
    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->content['field_1'])->toBe('Yes');
    expect($submission->content['field_2'])->toBe('Software Engineer');
    expect($submission->content['field_3'])->toBe('Verified Info');

    // 4. Verify draft was cleaned up
    expect(FormDraft::count())->toBe(0);
});

it('skips conditional page fields when condition is not met', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Skip Page Form',
        'slug' => 'skip-form',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'radio',
                            'label' => 'Has experience?',
                            'required' => true,
                            'options' => ['Yes', 'No']
                        ]
                    ]
                ],
                [
                    'id' => 'page_2',
                    'title' => 'Page 2',
                    'conditionalLogic' => [
                        'action' => 'show',
                        'triggerFieldId' => 'field_1',
                        'triggerValue' => 'Yes'
                    ],
                    'fields' => [
                        [
                            'id' => 'field_2',
                            'type' => 'text',
                            'label' => 'Experience details',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ],
                [
                    'id' => 'page_3',
                    'title' => 'Page 3',
                    'fields' => [
                        [
                            'id' => 'field_3',
                            'type' => 'text',
                            'label' => 'Final info',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ]
            ]
        ]
    ]);

    $hpTime = Crypt::encryptString((string) (now()->timestamp - 5));

    // Save draft with "No" — page 2 should be hidden
    $this->postJson("/f/{$form->slug}/draft", [
        'data' => ['field_1' => 'No'],
        'current_page' => 0,
    ]);

    $draft = FormDraft::first();

    // Final submit — last visible page is page 3 (skipping page 2)
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->post("/f/{$form->slug}", [
            '_hp_time' => $hpTime,
            'field_3' => 'Done',
        ]);

    $response->assertRedirect(route('forms.show', $form->slug));

    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->content['field_1'])->toBe('No');
    expect($submission->content)->not->toHaveKey('field_2');
    expect($submission->content['field_3'])->toBe('Done');
});

it('submits a two-page form when the second page is conditional and shown', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Two Page Conditional',
        'slug' => 'two-page-cond',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'radio',
                            'label' => 'Need more details?',
                            'required' => true,
                            'options' => ['Yes', 'No'],
                        ],
                    ],
                ],
                [
                    'id' => 'page_2',
                    'title' => 'Page 2',
                    'conditionalLogic' => [
                        'action' => 'show',
                        'triggerFieldId' => 'field_1',
                        'triggerValue' => 'Yes',
                    ],
                    'fields' => [
                        [
                            'id' => 'field_2',
                            'type' => 'text',
                            'label' => 'Details',
                            'required' => true,
                            'options' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $hpTime = Crypt::encryptString((string) (now()->timestamp - 5));

    // Mimic frontend: save draft (with page 1 answer) then submit only last page fields
    $draftResponse = $this->postJson("/f/{$form->slug}/draft", [
        'data' => ['field_1' => 'Yes', 'field_2' => 'Extra details'],
        'current_page' => 1,
    ]);
    $draftResponse->assertOk()->assertJsonStructure(['success', 'token']);
    $token = $draftResponse->json('token');

    // Submit without cookie — only header/body token (cookie race scenario)
    $response = $this->postJson("/f/{$form->slug}", [
        '_hp_time' => $hpTime,
        '_draft_token' => $token,
        'field_2' => 'Extra details',
    ], [
        'X-Form-Draft-Token' => $token,
    ]);

    $response->assertOk()->assertJson(['success' => true]);

    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->content['field_1'])->toBe('Yes');
    expect($submission->content['field_2'])->toBe('Extra details');
});

it('rejects last-page submit for conditional page 2 when draft progress is missing', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Two Page No Draft',
        'slug' => 'two-page-no-draft',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'radio',
                            'label' => 'Need more details?',
                            'required' => true,
                            'options' => ['Yes', 'No'],
                        ],
                    ],
                ],
                [
                    'id' => 'page_2',
                    'title' => 'Page 2',
                    'conditionalLogic' => [
                        'action' => 'show',
                        'triggerFieldId' => 'field_1',
                        'triggerValue' => 'Yes',
                    ],
                    'fields' => [
                        [
                            'id' => 'field_2',
                            'type' => 'text',
                            'label' => 'Details',
                            'required' => true,
                            'options' => [],
                        ],
                    ],
                ],
            ],
        ],
    ]);

    $hpTime = Crypt::encryptString((string) (now()->timestamp - 5));

    // No draft: server thinks only page 1 is visible, field_2 is unexpected
    $response = $this->postJson("/f/{$form->slug}", [
        '_hp_time' => $hpTime,
        'field_2' => 'Should fail',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Unable to validate your submission. Please refresh and try again.',
        ]);
});

it('returns 404 for inactive forms on draft save', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Inactive Form',
        'slug' => 'inactive-form',
        'schema' => ['fields' => [['id' => 'f1', 'type' => 'text', 'label' => 'Name', 'required' => false]]],
    ]);

    $form->is_active = false;
    $form->save();

    $response = $this->postJson("/f/{$form->slug}/draft", [
        'data' => ['f1' => 'test'],
    ]);

    $response->assertNotFound();
});

it('returns JSON response for validation errors on final submit when requesting JSON', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Form for JSON validation test',
        'slug' => 'json-val-form',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'text',
                            'label' => 'Name',
                            'required' => true,
                            'options' => []
                        ]
                    ]
                ]
            ]
        ]
    ]);

    $hpTime = Crypt::encryptString((string) (now()->timestamp - 5));

    // Submit without required field_1, expecting JSON
    $response = $this->postJson("/f/{$form->slug}", [
        '_hp_time' => $hpTime,
        // field_1 is omitted
    ]);

    $response->assertStatus(422)
        ->assertJsonStructure(['success', 'errors', 'message'])
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed: The Name field is required.'
        ]);
});

it('returns JSON response for security block on final submit when requesting JSON', function () {
    $user = User::factory()->create();

    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Form for Security JSON test',
        'slug' => 'security-json-form',
        'is_active' => true,
        'schema' => [
            'pages' => [
                [
                    'id' => 'page_1',
                    'title' => 'Page 1',
                    'fields' => [
                        [
                            'id' => 'field_1',
                            'type' => 'text',
                            'label' => 'Name',
                            'required' => false,
                            'options' => []
                        ]
                    ]
                ]
            ]
        ]
    ]);

    // Submit with honeypot triggered (_hp_website filled), expecting JSON
    $response = $this->postJson("/f/{$form->slug}", [
        '_hp_website' => 'bot-content',
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Unable to validate your submission. Please refresh and try again.'
        ]);
});

