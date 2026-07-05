<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Form;
use App\Models\Submission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create or find the Test User
        $user = User::updateOrCreate(
            ['email' => 'qwe@qwe.qwe'],
            [
                'name' => 'Test User',
                'password' => bcrypt('qwerqwer'),
                'email_verified_at' => now(),
            ]
        );

        // Clear existing forms and submissions for this user to start fresh
        $user->forms()->each(function ($form) {
            $form->delete();
        });

        // Predefined realistic feedback options with clear sentiments
        $positiveFeedbacks = [
            "The interface is absolutely gorgeous! Love the layout and font choices.",
            "Works perfectly, and the page load times are extremely fast.",
            "Extremely satisfied with the new update. Keep up the excellent work!",
            "This saved our team hours of manual work. Highly recommended.",
            "Fantastic customer experience. Clean and simple.",
            "The responsive layout is perfect. It looks great on both mobile and desktop.",
            "Very smooth form builder, extremely intuitive to use.",
            "Everything works flawlessly. Thank you for this wonderful product!",
            "I love the minimalist design aesthetic. It looks very professional.",
            "Excellent support response. The issue was solved immediately."
        ];

        $neutralFeedbacks = [
            "The tool does what it is supposed to do. Average experience.",
            "A few styling alignment issues in dark mode, but otherwise functional.",
            "Could you add more custom export formats like JSON and XML?",
            "It is a decent product, but the subscription price is a bit high.",
            "Standard form layout. Relatively easy to fill out.",
            "It's fine, but I think having more field template options would be helpful.",
            "The submission went through. Just sharing my feedback.",
            "Is there a way to integrate this directly with Slack notifications?",
            "Average form, no major pros or cons to report.",
            "The loading speed is okay, could be optimized slightly."
        ];

        $negativeFeedbacks = [
            "This app is totally broken. The submit button is completely unresponsive on Safari.",
            "The form load speed is awful, it takes over 10 seconds to render on a slow connection.",
            "I am extremely frustrated with the lack of documentation for API webhooks.",
            "Lost all my progress because it did not auto-save when my connection dropped. Very upset.",
            "The UI design looks dated and looks very amateurish in some sections.",
            "Formatting is completely messed up on mobile. The input fields overlap each other.",
            "Terrible experience, it threw a server error twice when trying to upload files.",
            "Customer service has ignored my request for three days now. Very disappointing.",
            "Why is there no validation on the email field? People are submitting dummy text.",
            "The dark mode colors have terrible accessibility contrast. Can't read anything."
        ];

        $names = ['Liam', 'Olivia', 'Noah', 'Emma', 'Oliver', 'Ava', 'Elijah', 'Charlotte', 'William', 'Sophia', 'James', 'Amelia', 'Benjamin', 'Isabella', 'Lucas', 'Mia'];

        // --- FORM 1: Customer Satisfaction (Traffic Drop Alert) ---
        $form1 = Form::create([
            'user_id' => $user->id,
            'title' => 'Customer Satisfaction Survey',
            'description' => 'Help us improve our service by sharing your feedback.',
            'slug' => 'customer-satisfaction',
            'is_active' => true,
            'schema' => [
                'pages' => [
                    [
                        'id' => 'page_1',
                        'title' => 'Feedback',
                        'description' => 'General feedback',
                        'fields' => [
                            ['id' => 'f1_name', 'type' => 'text', 'label' => 'Full Name', 'required' => true],
                            ['id' => 'f1_rating', 'type' => 'select', 'label' => 'Overall Rating', 'required' => true, 'options' => ['Excellent', 'Good', 'Average', 'Poor']],
                            ['id' => 'f1_comment', 'type' => 'textarea', 'label' => 'Detailed Feedback', 'required' => true, 'analyze_sentiment' => true]
                        ]
                    ]
                ]
            ],
            'settings' => []
        ]);

        // Seed submissions: 35 last week, 2 this week, 13 two weeks ago (Total 50)
        // Traffic drop: last week (35) -> this week (2)
        $this->seedSubmissions($form1, [
            'this_week' => 2,
            'last_week' => 35,
            'older' => 13
        ], [
            'f1_name' => $names,
            'f1_rating' => ['Excellent', 'Good', 'Average', 'Poor'],
            'f1_comment' => [
                'positive' => $positiveFeedbacks,
                'neutral' => $neutralFeedbacks,
                'negative' => $negativeFeedbacks
            ]
        ]);

        // --- FORM 2: Feature Requests (Traffic Spike Alert) ---
        $form2 = Form::create([
            'user_id' => $user->id,
            'title' => 'Feature Request Board',
            'description' => 'Let us know what features you would like to see next.',
            'slug' => 'feature-requests',
            'is_active' => true,
            'schema' => [
                'pages' => [
                    [
                        'id' => 'page_1',
                        'title' => 'Feature Request',
                        'description' => 'Describe your requested feature',
                        'fields' => [
                            ['id' => 'f2_category', 'type' => 'radio', 'label' => 'Category', 'required' => true, 'options' => ['UI Design', 'Performance', 'Integrations', 'Mobile App']],
                            ['id' => 'f2_desc', 'type' => 'textarea', 'label' => 'Feature Description', 'required' => true, 'analyze_sentiment' => true]
                        ]
                    ]
                ]
            ],
            'settings' => []
        ]);

        // Seed submissions: 4 last week, 30 this week, 16 two weeks ago (Total 50)
        // Traffic spike: last week (4) -> this week (30)
        $this->seedSubmissions($form2, [
            'this_week' => 30,
            'last_week' => 4,
            'older' => 16
        ], [
            'f2_category' => ['UI Design', 'Performance', 'Integrations', 'Mobile App'],
            'f2_desc' => [
                'positive' => $positiveFeedbacks,
                'neutral' => $neutralFeedbacks,
                'negative' => $negativeFeedbacks
            ]
        ]);

        // --- FORM 3: Contact Us (Negative Sentiment Spike Alert) ---
        $form3 = Form::create([
            'user_id' => $user->id,
            'title' => 'Support & Helpdesk Contact',
            'description' => 'Need help? Create a support request.',
            'slug' => 'contact-support',
            'is_active' => true,
            'schema' => [
                'pages' => [
                    [
                        'id' => 'page_1',
                        'title' => 'Support Request',
                        'description' => 'Tell us about your issue',
                        'fields' => [
                            ['id' => 'f3_subject', 'type' => 'text', 'label' => 'Subject', 'required' => true],
                            ['id' => 'f3_message', 'type' => 'textarea', 'label' => 'Message Details', 'required' => true, 'analyze_sentiment' => true]
                        ]
                    ]
                ]
            ],
            'settings' => []
        ]);

        // Seed submissions: 25 this week (with 15 negative feedback, i.e. 60%), 25 two weeks ago (Total 50)
        // Negative spike: 60% negative sentiment this week
        $this->seedSubmissions($form3, [
            'this_week' => 25,
            'last_week' => 0,
            'older' => 25
        ], [
            'f3_subject' => ['Bug report', 'Billing issue', 'Account access', 'Question about integrations', 'Form load problem'],
            'f3_message' => [
                'positive' => $positiveFeedbacks,
                'neutral' => $neutralFeedbacks,
                'negative' => $negativeFeedbacks
            ]
        ], [
            // Force 15 negative submissions this week
            'negative_count_this_week' => 15
        ]);
    }

    /**
     * Seed submissions with correct dates and pre-calculated sentiment metadata.
     */
    private function seedSubmissions(Form $form, array $counts, array $pools, array $options = []): void
    {
        $totalThisWeek = $counts['this_week'];
        $forcedNegativesThisWeek = $options['negative_count_this_week'] ?? null;

        // Process this week's submissions
        for ($i = 0; $i < $totalThisWeek; $i++) {
            // Determine sentiment to assign
            $sentiment = 'neutral';
            if ($forcedNegativesThisWeek !== null) {
                $sentiment = ($i < $forcedNegativesThisWeek) ? 'negative' : (rand(0, 1) ? 'positive' : 'neutral');
            } else {
                $randVal = rand(0, 100);
                if ($randVal < 45) $sentiment = 'positive';
                elseif ($randVal < 80) $sentiment = 'neutral';
                else $sentiment = 'negative';
            }

            $date = Carbon::now()->startOfWeek()->addDays(rand(0, 3))->addHours(rand(0, 23))->addMinutes(rand(0, 59));
            $this->createSubmissionRecord($form, $pools, $sentiment, $date);
        }

        // Process last week's submissions
        for ($i = 0; $i < $counts['last_week']; $i++) {
            $randVal = rand(0, 100);
            $sentiment = ($randVal < 50) ? 'positive' : (($randVal < 85) ? 'neutral' : 'negative');
            $date = Carbon::now()->subWeek()->startOfWeek()->addDays(rand(0, 5))->addHours(rand(0, 23))->addMinutes(rand(0, 59));
            $this->createSubmissionRecord($form, $pools, $sentiment, $date);
        }

        // Process older submissions
        for ($i = 0; $i < $counts['older']; $i++) {
            $randVal = rand(0, 100);
            $sentiment = ($randVal < 50) ? 'positive' : (($randVal < 85) ? 'neutral' : 'negative');
            $date = Carbon::now()->subWeeks(2)->startOfWeek()->addDays(rand(0, 5))->addHours(rand(0, 23))->addMinutes(rand(0, 59));
            $this->createSubmissionRecord($form, $pools, $sentiment, $date);
        }
    }

    /**
     * Create single submission record.
     */
    private function createSubmissionRecord(Form $form, array $pools, string $sentiment, Carbon $createdAt): void
    {
        $content = [];
        $aiMetadata = [];

        foreach ($pools as $fieldId => $pool) {
            // Handle regular inputs vs textareas (which support sentiments)
            if (isset($pool['positive']) && isset($pool['neutral']) && isset($pool['negative'])) {
                // Select feedback corresponding to the selected sentiment
                $options = $pool[$sentiment];
                $textValue = $options[array_rand($options)];
                $content[$fieldId] = $textValue;

                // Build rich structured sentiment metadata
                $score = match ($sentiment) {
                    'positive' => rand(75, 98) / 100,
                    'negative' => rand(70, 95) / 100,
                    default => rand(20, 60) / 100,
                };
                $emotions = match ($sentiment) {
                    'positive' => ['satisfied', 'happy', 'excited', 'impressed', 'delighted'],
                    'negative' => ['frustrated', 'disappointed', 'upset', 'annoyed', 'confused'],
                    default => ['indifferent', 'neutral', 'calm'],
                };
                shuffle($emotions);
                $emotions = array_slice($emotions, 0, rand(1, 2));

                $aiMetadata[$fieldId] = [
                    'label' => $sentiment,
                    'score' => $score,
                    'emotions' => $emotions,
                ];
            } else {
                $content[$fieldId] = $pool[array_rand($pool)];
            }
        }

        // Save submission directly using database insert/query (bypassing model events to prevent live API dispatching)
        // Since we already pre-calculate and store sentiment metadata, it's immediately ready for UI rendering.
        Submission::query()->insert([
            'form_id' => $form->id,
            'content' => json_encode($content),
            'ip_address' => '127.0.0.1',
            'ai_metadata' => json_encode([
                'sentiment' => $aiMetadata
            ]),
            'created_at' => $createdAt,
            'updated_at' => $createdAt
        ]);
    }
}
