<?php

use App\Models\User;
use App\Models\Form;
use App\Models\Submission;
use App\Jobs\GenerateIntelligenceDataJob;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('redirects unauthenticated users to login page', function () {
    $this->get('/dashboard')
        ->assertRedirect('/login');
});

it('renders default dashboard view with expected optimized data and stats', function () {
    $user = User::factory()->create();
    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Form 1',
        'slug' => 'form-1',
        'schema' => ['pages' => []],
        'is_active' => true,
    ]);

    Submission::create([
        'form_id' => $form->id,
        'content' => [],
        'ip_address' => '127.0.0.1',
    ]);

    $response = $this->actingAs($user)
        ->get('/dashboard');

    $response->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHasAll([
            'totalForms' => 1,
            'activeForms' => 1,
            'totalSubmissions' => 1,
            'thisWeekSubmissions' => 1,
            'aiDigest',
            'formHealthMap',
            'alertCacheKey',
        ])
        ->assertViewMissing('forms')
        ->assertViewMissing('submissions')
        ->assertViewMissing('userFormsSimple');

    $response->assertSee('Welcome');
    $response->assertSee('back');
    $response->assertSee('Total Forms');
    $response->assertSee('Submissions');
});

it('renders forms view with paginated forms only', function () {
    $user = User::factory()->create();
    Form::create([
        'user_id' => $user->id,
        'title' => 'Form A',
        'slug' => 'form-a',
        'schema' => ['pages' => []],
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)
        ->get('/dashboard?view=forms');

    $response->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHas('forms')
        ->assertViewMissing('totalForms')
        ->assertViewMissing('submissions')
        ->assertViewMissing('formHealthMap');

    $response->assertSee('Your');
    $response->assertSee('Forms');
    $response->assertSee('Form A');
});

it('renders submissions view with submissions and simple forms list only', function () {
    $user = User::factory()->create();
    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Form B',
        'slug' => 'form-b',
        'schema' => ['pages' => []],
        'is_active' => true,
    ]);

    $submission = Submission::create([
        'form_id' => $form->id,
        'content' => ['field_1' => 'Test answer'],
        'ip_address' => '127.0.0.1',
    ]);

    $response = $this->actingAs($user)
        ->get('/dashboard?view=submissions');

    $response->assertOk()
        ->assertViewIs('dashboard')
        ->assertViewHasAll([
            'submissions',
            'userFormsSimple',
        ])
        ->assertViewMissing('forms')
        ->assertViewMissing('totalForms')
        ->assertViewMissing('formHealthMap');

    $response->assertSee('Form');
    $response->assertSee('Submissions');
    $response->assertSee('Form B');
});

it('dispatches background intelligence generation and handles polling endpoint', function () {
    \Illuminate\Support\Facades\Cache::flush();
    \Illuminate\Support\Facades\Bus::fake();

    $user = User::factory()->create();

    // 1. Initial hit should trigger the background job
    $response = $this->actingAs($user)
        ->get('/dashboard');

    $response->assertOk();
    \Illuminate\Support\Facades\Bus::assertDispatched(GenerateIntelligenceDataJob::class);

    // 2. Test the polling endpoint
    $response = $this->actingAs($user)
        ->get('/dashboard/intelligence');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'aiAlerts',
            'aiDigest',
            'alertCacheKey',
        ])
        ->assertJson([
            'status' => 'loading',
        ]);
});
