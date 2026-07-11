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

        // 2. Define feedback pools for sentiment analysis fields
        $positiveApiFeedbacks = [
            "The API documentation is clear and webhook integration was a breeze.",
            "Fast response times, and the endpoints are RESTful and clean.",
            "Integrating this with our CRM took less than 10 minutes, highly recommend!",
            "Brilliant developers' portal and excellent uptime on your API endpoints.",
            "OAuth integration was smooth and scopes were extremely descriptive.",
            "The webhook retries and logging made debugging our integration very easy."
        ];

        $neutralApiFeedbacks = [
            "The API works, but we'd love more rate limit capacity for business accounts.",
            "Endpoints are standard. Nothing special but gets the job done without issues.",
            "Is there a plan to release an official SDK for Go and Python?",
            "Documentation could have more code snippets in Node.js.",
            "Standard API. The JSON responses are well structured."
        ];

        $negativeApiFeedbacks = [
            "The API keeps throwing 500 errors and webhook payloads are missing key fields.",
            "The authentication system is overly complicated and poorly documented.",
            "Extremely slow response times on the analytics API endpoint under load.",
            "We encountered rate limit issues immediately, and the headers are inconsistent.",
            "Webhooks fail frequently and there is no way to manually replay them from the portal."
        ];

        $negativeImprovementSuggestions = [
            "The dark mode contrast is terrible; please fix the accessibility issues.",
            "The interface layout is completely broken on smaller mobile screens.",
            "Please allow us to export data as CSV or Excel.",
            "Your support team takes days to respond to simple billing questions.",
            "The submission took way too long to save, throwing unexpected timeouts.",
            "Form building is laggy when handling more than 5 pages. Please optimize.",
            "There's no auto-save feature, and I lost all my progress when my connection dropped."
        ];

        $companies = ['Acme Corp', 'TechStart Inc', 'Global Retailers', 'Fintech Pro', 'Innovate LLC', 'Nova Solutions', 'Peak Software', 'Alpha Logistics', 'Nexus Labs', 'Quantum Health'];

        // 3. Create the single, complex conditional Form
        $form = Form::create([
            'user_id' => $user->id,
            'title' => 'Comprehensive Feedback Hub',
            'description' => 'A centralized feedback portal for individuals and businesses.',
            'slug' => 'feedback-hub',
            'is_active' => true,
            'schema' => [
                'pages' => [
                    [
                        'id' => 'page_1',
                        'title' => 'Customer Classification',
                        'description' => 'Tell us about yourself and how you interact with us.',
                        'fields' => [
                            [
                                'id' => 'cust_type',
                                'type' => 'radio',
                                'label' => 'Customer Type',
                                'required' => true,
                                'options' => ['Business', 'Individual']
                            ],
                            [
                                'id' => 'deep_feedback',
                                'type' => 'radio',
                                'label' => 'Would you like to provide detailed feedback?',
                                'required' => true,
                                'options' => ['Yes', 'No']
                            ],
                            [
                                'id' => 'referral_source',
                                'type' => 'select',
                                'label' => 'How did you hear about us?',
                                'required' => false,
                                'options' => ['Search Engine', 'Friend', 'Social Media', 'Ad', 'Other'],
                                'conditionalLogic' => [
                                    'action' => 'show',
                                    'triggerFieldId' => 'cust_type',
                                    'triggerValue' => 'Individual'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'page_2',
                        'title' => 'Business & Technical Insights',
                        'description' => 'Details about your company and developer interactions.',
                        'conditionalLogic' => [
                            'action' => 'show',
                            'triggerFieldId' => 'cust_type',
                            'triggerValue' => 'Business'
                        ],
                        'fields' => [
                            [
                                'id' => 'company_name',
                                'type' => 'text',
                                'label' => 'Company Name',
                                'required' => true
                            ],
                            [
                                'id' => 'uses_api',
                                'type' => 'radio',
                                'label' => 'Do you integrate with our API?',
                                'required' => true,
                                'options' => ['Yes', 'No']
                            ],
                            [
                                'id' => 'api_feedback',
                                'type' => 'textarea',
                                'label' => 'API Integration Feedback',
                                'required' => false,
                                'analyze_sentiment' => true,
                                'conditionalLogic' => [
                                    'action' => 'show',
                                    'triggerFieldId' => 'uses_api',
                                    'triggerValue' => 'Yes'
                                ]
                            ]
                        ]
                    ],
                    [
                        'id' => 'page_3',
                        'title' => 'General User Experience',
                        'description' => 'Help us improve our service by sharing your experience.',
                        'conditionalLogic' => [
                            'action' => 'show',
                            'triggerFieldId' => 'deep_feedback',
                            'triggerValue' => 'Yes'
                        ],
                        'fields' => [
                            [
                                'id' => 'experience_rating',
                                'type' => 'select',
                                'label' => 'Overall Experience',
                                'required' => true,
                                'options' => ['Excellent', 'Good', 'Average', 'Poor']
                            ],
                            [
                                'id' => 'improvement_suggestions',
                                'type' => 'textarea',
                                'label' => 'Please share how we can improve',
                                'required' => false,
                                'analyze_sentiment' => true,
                                'conditionalLogic' => [
                                    'action' => 'show',
                                    'triggerFieldId' => 'experience_rating',
                                    'triggerValue' => 'Poor'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'settings' => []
        ]);

        // 4. Seed exactly 100 submissions with varying feedback and submission dates
        for ($i = 0; $i < 100; $i++) {
            $content = [];
            $aiMetadata = [];

            // Page 1 responses
            $custType = rand(0, 1) ? 'Business' : 'Individual';
            $deepFeedback = (rand(0, 100) < 65) ? 'Yes' : 'No';

            $content['cust_type'] = $custType;
            $content['deep_feedback'] = $deepFeedback;

            if ($custType === 'Individual') {
                $referralSources = ['Search Engine', 'Friend', 'Social Media', 'Ad', 'Other'];
                $content['referral_source'] = $referralSources[array_rand($referralSources)];
            }

            // Page 2 responses (Conditional: cust_type === 'Business')
            if ($custType === 'Business') {
                $content['company_name'] = $companies[array_rand($companies)];
                $usesApi = rand(0, 1) ? 'Yes' : 'No';
                $content['uses_api'] = $usesApi;

                // Conditional field: api_feedback (only if uses_api === 'Yes')
                if ($usesApi === 'Yes') {
                    $randVal = rand(0, 100);
                    if ($randVal < 45) {
                        // Positive
                        $sentiment = 'positive';
                        $feedback = $positiveApiFeedbacks[array_rand($positiveApiFeedbacks)];
                        $score = rand(75, 98) / 100;
                        $emotions = ['satisfied', 'excited', 'impressed', 'delighted'];
                    } elseif ($randVal < 75) {
                        // Neutral
                        $sentiment = 'neutral';
                        $feedback = $neutralApiFeedbacks[array_rand($neutralApiFeedbacks)];
                        $score = rand(20, 60) / 100;
                        $emotions = ['indifferent', 'neutral', 'calm'];
                    } else {
                        // Negative
                        $sentiment = 'negative';
                        $feedback = $negativeApiFeedbacks[array_rand($negativeApiFeedbacks)];
                        $score = rand(70, 95) / 100;
                        $emotions = ['frustrated', 'disappointed', 'upset', 'annoyed'];
                    }

                    $content['api_feedback'] = $feedback;
                    shuffle($emotions);
                    $aiMetadata['api_feedback'] = [
                        'label' => $sentiment,
                        'score' => $score,
                        'emotions' => array_slice($emotions, 0, rand(1, 2)),
                    ];
                }
            }

            // Page 3 responses (Conditional: deep_feedback === 'Yes')
            if ($deepFeedback === 'Yes') {
                $ratings = ['Excellent', 'Good', 'Average', 'Poor'];
                $randRating = rand(0, 100);
                if ($randRating < 40) {
                    $rating = 'Excellent';
                } elseif ($randRating < 75) {
                    $rating = 'Good';
                } elseif ($randRating < 90) {
                    $rating = 'Average';
                } else {
                    $rating = 'Poor';
                }
                $content['experience_rating'] = $rating;

                // Conditional field: improvement_suggestions (only if experience_rating === 'Poor')
                if ($rating === 'Poor') {
                    $sentiment = 'negative';
                    $feedback = $negativeImprovementSuggestions[array_rand($negativeImprovementSuggestions)];
                    $score = rand(75, 95) / 100;
                    $emotions = ['frustrated', 'disappointed', 'upset', 'annoyed', 'confused'];
                    shuffle($emotions);

                    $content['improvement_suggestions'] = $feedback;
                    $aiMetadata['improvement_suggestions'] = [
                        'label' => $sentiment,
                        'score' => $score,
                        'emotions' => array_slice($emotions, 0, rand(1, 2)),
                    ];
                }
            }

            // Generate a random date in the last 30 days
            $createdAt = Carbon::now()->subDays(rand(0, 30))->subHours(rand(0, 23))->subMinutes(rand(0, 59));

            // Insert submission bypass events to avoid third-party API or queues
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
}
