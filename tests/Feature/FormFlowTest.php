<?php

use App\Models\Form;
use App\Models\User;
use App\Models\Submission;
use App\Models\FormDraft;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;

uses(RefreshDatabase::class);

it('handles multi-page navigation, validation, drafts, and conditional pages skipping', function () {
    $user = User::factory()->create();

    // Create a multi-page form with conditional logic on page 2
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

    // 1. Visit Page 1
    $response = $this->get("/f/{$form->slug}?page=1");
    $response->assertOk();

    // Generate honeypot time token
    $hpTime = Crypt::encryptString((string) (now()->timestamp - 5));

    // 2. Submit Page 1 with "No" experience (skips Page 2)
    $response = $this->post("/f/{$form->slug}/page/1", [
        '_hp_time' => $hpTime,
        'field_1' => 'No',
    ]);
    
    // Should save progress and redirect to page 3 (skipping page 2)
    $response->assertRedirect(route('forms.show', ['slug' => $form->slug, 'page' => 3]));
    
    // Assert draft exists in database
    $draft = FormDraft::first();
    expect($draft)->not->toBeNull();
    expect($draft->data)->toHaveKey(0);
    expect($draft->data[0]['field_1'])->toBe('No');

    // 3. Clear session to simulate expiry/new session
    session()->forget("form_progress.{$form->id}");

    // Visit Page 1 again - should recover progress from DB draft via the cookie
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->get("/f/{$form->slug}?page=1");
    $response->assertOk();
    expect(session()->get("form_progress.{$form->id}"))->not->toBeEmpty();

    // 4. Submit Page 1 with "Yes" experience (includes Page 2)
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->post("/f/{$form->slug}/page/1", [
            '_hp_time' => $hpTime,
            'field_1' => 'Yes',
        ]);
    
    // Should redirect to page 2 now
    $response->assertRedirect(route('forms.show', ['slug' => $form->slug, 'page' => 2]));

    // 5. Submit Page 2 with empty "field_2" (should fail validation because page 2 is active)
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->post("/f/{$form->slug}/page/2", [
            '_hp_time' => $hpTime,
            'field_2' => '',
        ]);
    $response->assertRedirect();
    $response->assertSessionHasErrors('field_2');

    // 6. Submit Page 2 with valid details
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->post("/f/{$form->slug}/page/2", [
            '_hp_time' => $hpTime,
            'field_2' => 'Software Engineer',
        ]);
    $response->assertRedirect(route('forms.show', ['slug' => $form->slug, 'page' => 3]));

    // 7. Final submit Page 3
    $response = $this->withCookie("form_draft_{$form->id}", $draft->token)
        ->post("/f/{$form->slug}", [
            '_hp_time' => $hpTime,
            'field_3' => 'Verified Info',
        ]);
    $response->assertRedirect(route('forms.show', $form->slug));
    $response->assertSessionHas('success');

    // 8. Assert Submission is saved correctly
    $submission = Submission::first();
    expect($submission)->not->toBeNull();
    expect($submission->content['field_1'])->toBe('Yes');
    expect($submission->content['field_2'])->toBe('Software Engineer');
    expect($submission->content['field_3'])->toBe('Verified Info');

    // 9. Assert draft is deleted from database
    expect(FormDraft::count())->toBe(0);
});
