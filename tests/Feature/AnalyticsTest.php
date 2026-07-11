<?php

use App\Jobs\AnalyzeSubmissionSentiment;
use App\Livewire\Analytics;
use App\Models\Form;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the analytics dashboard view with the Livewire component', function () {
    $user = User::factory()->create();
    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Feedback Survey',
        'slug' => 'feedback-survey',
        'schema' => [
            'pages' => [[
                'id' => 'page_1',
                'fields' => [
                    [
                        'id' => 'rating',
                        'type' => 'select',
                        'label' => 'Rating',
                        'options' => ['Good', 'Bad'],
                    ],
                ],
            ]],
        ],
        'is_active' => true,
    ]);

    Submission::create([
        'form_id' => $form->id,
        'content' => ['rating' => 'Good'],
        'ip_address' => '127.0.0.1',
    ]);

    $this->actingAs($user)
        ->get('/dashboard?view=analytics')
        ->assertOk()
        ->assertSeeLivewire(Analytics::class)
        ->assertSee('Analytics')
        ->assertSee('Overview')
        ->assertSee('Feedback Survey');
});

it('wires filter bar, kpis, field distributions, and charts for a form with submissions', function () {
    Queue::fake([AnalyzeSubmissionSentiment::class]);

    $user = User::factory()->create();
    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Product Form',
        'slug' => 'product-form',
        'schema' => [
            'pages' => [[
                'id' => 'page_1',
                'fields' => [
                    [
                        'id' => 'choice',
                        'type' => 'radio',
                        'label' => 'Favorite Color',
                        'options' => ['Red', 'Blue'],
                    ],
                    [
                        'id' => 'comments',
                        'type' => 'textarea',
                        'label' => 'Comments',
                        'analyze_sentiment' => true,
                    ],
                ],
            ]],
        ],
        'is_active' => true,
    ]);

    Submission::create([
        'form_id' => $form->id,
        'content' => ['choice' => 'Red', 'comments' => 'Love it'],
        'ip_address' => '127.0.0.1',
        'ai_metadata' => [
            'sentiment' => [
                'comments' => [
                    'label' => 'positive',
                    'score' => 0.92,
                    'emotions' => ['joy'],
                ],
            ],
        ],
    ]);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertSet('selectedFormId', $form->id)
        ->assertSet('range', '30')
        ->assertSee('In Period')
        ->assertSee('This Week')
        ->assertSee('Submissions Over Time')
        ->assertSee('Favorite Color')
        ->assertSee('Red')
        ->assertSee('AI Sentiment Analysis')
        ->assertSee('Comments')
        ->assertSee('Top Emotions')
        ->assertSee('joy')
        ->assertSeeHtml('id="analyticsLineChart"')
        ->assertSeeHtml('id="chart-comments"')
        ->set('range', '7')
        ->assertSet('range', '7')
        ->assertSee('last 7 days');
});

it('shows empty state when the selected form has no submissions', function () {
    $user = User::factory()->create();
    Form::create([
        'user_id' => $user->id,
        'title' => 'Empty Form',
        'slug' => 'empty-form',
        'schema' => ['pages' => [['id' => 'page_1', 'fields' => []]]],
        'is_active' => true,
    ]);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertSee('No submissions yet')
        ->assertDontSee('Submissions Over Time');
});

it('preserves ephemeral generateInsights messages instead of clobbering them on render', function () {
    $user = User::factory()->create();
    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Quiet Form',
        'slug' => 'quiet-form',
        'schema' => ['pages' => [['id' => 'page_1', 'fields' => []]]],
        'is_active' => true,
        'ai_insights' => null,
    ]);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->set('selectedFormId', $form->id)
        ->call('generateInsights')
        ->assertSet('aiInsights', 'No submissions found for this form in the selected time range.')
        ->assertSee('No submissions found for this form in the selected time range.');
});

it('hydrates saved AI insights from the form on first load', function () {
    $user = User::factory()->create();
    $form = Form::create([
        'user_id' => $user->id,
        'title' => 'Saved Insights Form',
        'slug' => 'saved-insights',
        'schema' => ['pages' => [['id' => 'page_1', 'fields' => []]]],
        'is_active' => true,
        'ai_insights' => '## Summary\n\nResponse volume is steady.',
        'ai_insights_updated_at' => now(),
    ]);

    Submission::create([
        'form_id' => $form->id,
        'content' => [],
        'ip_address' => '127.0.0.1',
    ]);

    Livewire::actingAs($user)
        ->test(Analytics::class)
        ->assertSet('selectedFormId', $form->id)
        ->assertSet('aiInsights', '## Summary\n\nResponse volume is steady.')
        ->assertSee('AI-Powered Insights')
        ->assertSee('Response volume is steady');
});

it('selects form from the dashboard query string when owned by the user', function () {
    $user = User::factory()->create();
    $first = Form::create([
        'user_id' => $user->id,
        'title' => 'First Form',
        'slug' => 'first-form',
        'schema' => ['pages' => []],
        'is_active' => true,
    ]);
    $second = Form::create([
        'user_id' => $user->id,
        'title' => 'Second Form',
        'slug' => 'second-form',
        'schema' => ['pages' => []],
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get('/dashboard?view=analytics&form='.$second->id)
        ->assertOk();

    Livewire::withQueryParams(['form' => $second->id])
        ->actingAs($user)
        ->test(Analytics::class)
        ->assertSet('selectedFormId', $second->id)
        ->assertSee('Second Form');
});
