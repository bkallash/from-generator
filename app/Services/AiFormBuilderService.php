<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class AiFormBuilderService
{
    private string $apiKey;
    private string $model;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('ai.gemini.api_key', '');
        $this->model = config('ai.gemini.model', 'gemini-2.5-flash');
        $this->baseUrl = config('ai.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta');
    }

    /**
     * Check if the form builder AI features are enabled and key is configured.
     */
    public function isEnabled(): bool
    {
        return !empty($this->apiKey) && config('ai.features.form_builder', true);
    }

    /**
     * Call Gemini to modify or generate a form schema based on the chat history and the current schema.
     *
     * @param array $currentSchema The current pages and fields structure.
     * @param array $history Chat history array of ['role' => 'user'|'model', 'parts' => [['text' => '...']]].
     * @param string $userPrompt The latest user message.
     * @return array|null The resulting array containing 'schema', 'title', 'description', and 'message' (AI response text).
     */
    public function generateFormSchema(array $currentSchema, array $history, string $userPrompt): ?array
    {
        if (!$this->isEnabled()) {
            return null;
        }

        try {
            $systemInstruction = $this->buildSystemInstruction();

            // Construct the API contents payload.
            // Gemini API expects:
            // contents: [ { role: 'user'|'model', parts: [ { text: '...' } ] }, ... ]
            $contents = [];

            // Add history. We map the roles ('ai' => 'model') and format them correctly.
            foreach ($history as $msg) {
                $role = $msg['role'] === 'ai' ? 'model' : 'user';
                $contents[] = [
                    'role' => $role,
                    'parts' => [
                        ['text' => $msg['content']]
                    ]
                ];
            }

            // Append current state representation as a context injection just before user prompt,
            // or merge it into the user prompt so the model always knows the CURRENT state.
            $currentStateJson = json_encode($currentSchema, JSON_PRETTY_PRINT);
            
            $promptWithContext = "CURRENT FORM SCHEMA:\n```json\n{$currentStateJson}\n```\n\nUSER COMMAND:\n{$userPrompt}\n\nApply the changes described in the user command to the current form schema. Return the new updated schema and a brief user-facing message explaining what you did.";

            $contents[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => $promptWithContext]
                ]
            ];

            $response = Http::withoutVerifying()->post("{$this->baseUrl}/models/{$this->model}:generateContent?key={$this->apiKey}", [
                'systemInstruction' => [
                    'parts' => [
                        ['text' => $systemInstruction]
                    ]
                ],
                'contents' => $contents,
                'generationConfig' => [
                    'responseMimeType' => 'application/json',
                ]
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error (AI Form Builder): ' . $response->body());
                return null;
            }

            $data = $response->json();
            $responseText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

            $responseText = trim($responseText);
            if (str_starts_with($responseText, '```')) {
                $responseText = preg_replace('/^```(?:json)?\s+|\s+```$/', '', $responseText);
            }

            $result = json_decode($responseText, true);
            if (!is_array($result)) {
                Log::error('AI Form Builder failed to return valid JSON', ['raw_response' => $responseText]);
                return null;
            }

            return $this->sanitizeOutput($result);

        } catch (\Throwable $e) {
            Log::error('Failed to generate form with AI: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sanitize and validate the output schema to prevent any malicious/malformed inputs.
     */
    private function sanitizeOutput(array $result): array
    {
        $sanitized = [
            'schema' => ['pages' => []],
            'title' => is_string($result['title'] ?? null) ? $result['title'] : 'Untitled Form',
            'description' => is_string($result['description'] ?? null) ? $result['description'] : '',
            'message' => is_string($result['message'] ?? null) ? $result['message'] : 'I updated the form based on your request.',
        ];

        $pages = $result['schema']['pages'] ?? [];
        if (!is_array($pages)) {
            $pages = [];
        }

        $validTypes = [
            'text', 'email', 'number', 'phone', 'url', 'textarea', 
            'select', 'radio', 'checkbox', 'date', 'image', 'file', 
            'heading', 'paragraph', 'divider'
        ];

        foreach ($pages as $p) {
            if (!is_array($p)) {
                continue;
            }

            $sanitizedPage = [
                'id' => is_string($p['id'] ?? null) ? $p['id'] : 'page_' . bin2hex(random_bytes(4)),
                'title' => is_string($p['title'] ?? null) ? $p['title'] : 'Page',
                'description' => is_string($p['description'] ?? null) ? $p['description'] : '',
                'fields' => [],
                'conditionalLogic' => $this->sanitizeConditionalLogic($p['conditionalLogic'] ?? null),
            ];

            $fields = $p['fields'] ?? [];
            if (is_array($fields)) {
                foreach ($fields as $f) {
                    if (!is_array($f) || !in_array($f['type'] ?? '', $validTypes)) {
                        continue;
                    }

                    $sanitizedField = [
                        'id' => is_string($f['id'] ?? null) ? $f['id'] : 'field_' . bin2hex(random_bytes(4)),
                        'type' => $f['type'],
                        'label' => is_string($f['label'] ?? null) ? $f['label'] : 'Field',
                        'placeholder' => is_string($f['placeholder'] ?? null) ? $f['placeholder'] : '',
                        'required' => (bool) ($f['required'] ?? false),
                        'options' => is_array($f['options'] ?? null) ? array_values($f['options']) : [],
                        'analyze_sentiment' => (bool) ($f['analyze_sentiment'] ?? false),
                        'conditionalLogic' => $this->sanitizeConditionalLogic($f['conditionalLogic'] ?? null),
                    ];

                    $sanitizedPage['fields'][] = $sanitizedField;
                }
            }

            $sanitized['schema']['pages'][] = $sanitizedPage;
        }

        // If no pages were returned, add a fallback empty page
        if (empty($sanitized['schema']['pages'])) {
            $sanitized['schema']['pages'][] = [
                'id' => 'page_' . bin2hex(random_bytes(4)),
                'title' => 'Page 1',
                'description' => '',
                'fields' => [],
                'conditionalLogic' => null,
            ];
        }

        return $sanitized;
    }

    /**
     * Sanitize conditional logic block.
     */
    private function sanitizeConditionalLogic(?array $logic): ?array
    {
        if (empty($logic) || !is_array($logic)) {
            return null;
        }

        $action = $logic['action'] ?? 'show';
        if (!in_array($action, ['show', 'hide'])) {
            $action = 'show';
        }

        $triggerFieldId = $logic['triggerFieldId'] ?? null;
        if (!is_string($triggerFieldId)) {
            return null;
        }

        return [
            'action' => $action,
            'triggerFieldId' => $triggerFieldId,
            'triggerValue' => is_string($logic['triggerValue'] ?? null) ? $logic['triggerValue'] : '',
        ];
    }

    /**
     * Build the detailed system instruction for the Gemini API.
     */
    private function buildSystemInstruction(): string
    {
        return <<<'PROMPT'
You are an expert form builder assistant that generates and updates multi-page form schemas based on user instructions in natural language.
You MUST output ONLY a valid JSON object matching the exact specification below. Do not include markdown code block syntax (like ```json) in the response; return pure raw JSON.

Output format:
{
  "title": "A short, descriptive title of the form",
  "description": "A brief description of what this form is for",
  "message": "A polite, friendly description of the changes you made in 1-2 sentences. Address the user directly.",
  "schema": {
    "pages": [
      {
        "id": "page_unique8char",
        "title": "Page Title",
        "description": "Page description",
        "conditionalLogic": null,
        "fields": [
          {
            "id": "field_unique8char",
            "type": "text",
            "label": "First Name",
            "placeholder": "Enter your first name",
            "required": true,
            "options": [],
            "analyze_sentiment": false,
            "conditionalLogic": null
          }
        ]
      }
    ]
  }
}

CRITICAL RULES FOR THOROUGHNESS & QUALITY:
1. **BE COMPREHENSIVE BY DEFAULT**: When generating a new form, do NOT return a minimalist, barebones form of only 3 or 4 fields. Build a complete, production-ready, thorough form containing all standard and advanced fields logical for that request (typically 8 to 15+ fields divided across pages/sections). For example:
   - *Job Application*: Contact info, physical address, work history (multiple fields), education details, skills checklist, availability date, resume upload, portfolio URL, references.
   - *Event Registration*: Contact info, ticket type selection, dietary restrictions, emergency contact details, how they heard, custom questions.
   - *Customer Feedback*: Contact details, rating scales, specific satisfaction categories, open-ended textarea feedback with sentiment analysis, recommendation likelihood.
2. **USE PROPER LAYOUT & STRUCTURE**: Use "heading", "paragraph", and "divider" fields to group inputs logically into sections (e.g., "Personal Information", "Experience History", "Additional Details").
3. **AUTOMATIC MULTI-PAGE DESIGNS**: If a form has more than 7 fields, automatically split it into multiple logically grouped pages (e.g., page 1 for identity, page 2 for core details/questions, page 3 for files & wrap-up).
4. **INTEGRATE SENSATIONAL FIELD SETTINGS**:
   - Apply specific types: `email` for email, `phone` for phone, `date` for dates, `url` for links, `number` for values.
   - For choice types (`select`, `radio`, `checkbox`), provide robust, clear, and comprehensive option values (do not just use generic placeholder options like 'Option 1').
   - Turn on `analyze_sentiment` = true for open-ended textareas where sentiment insights are valuable.
   - Make fields required = true where it is logically mandatory (e.g. name, email, primary options), but leave optional inputs as required = false.
5. **APPLY CONDITIONAL LOGIC AUTOMATICALLY**: Look for opportunities to apply conditional rules. If you include an option like "Other" or "Yes/No", automatically add conditional fields (e.g., a text input "Please specify other" that is shown only when "Other" is chosen).

Rules for Page structure:
- "id": A unique string, typically formatted as "page_" followed by 8 random alphanumeric characters (e.g. "page_d8a4f1bc"). IMPORTANT: Preserve existing page IDs unless deleting a page.
- "title": Page title (e.g. "Personal Details", "Feedback").
- "description": Optional page subtitle or description.
- "conditionalLogic": Null, or an object if the entire page is conditionally shown.
  Format: { "action": "show"|"hide", "triggerFieldId": "field_id", "triggerValue": "value" }
  Page conditional logic triggerFieldId must reference a field from a PREVIOUS page.

Rules for Field types and properties:
- Valid "type" values are:
  - "text" (Standard text input)
  - "email" (Email address)
  - "number" (Numeric inputs)
  - "phone" (Telephone)
  - "url" (Website address)
  - "textarea" (Paragraph text box)
  - "select" (Dropdown box)
  - "radio" (Single select options)
  - "checkbox" (Multiple select options)
  - "date" (Date picker)
  - "image" (Image upload)
  - "file" (Generic file upload)
  - "heading" (Layout Section Heading text)
  - "paragraph" (Layout descriptive text block)
  - "divider" (Layout visual separator line)

- "id": A unique string formatted as "field_" followed by 8 random alphanumeric characters (e.g. "field_j912kd9a").
  CRITICAL: When updating a form, you MUST preserve all existing field IDs of fields that are NOT being deleted. Do not regenerate IDs for unmodified fields.
- "label": The label shown to the user (or heading text/paragraph content).
- "placeholder": Ghost/placeholder text inside inputs.
- "required": Boolean (true/false) indicating if field validation is required.
- "options": Array of string options. Required ONLY for "select", "radio", and "checkbox" types. For all other types, it must be empty [].
- "analyze_sentiment": Boolean (true/false) indicating if AI text sentiment analysis is enabled. Can only be true for "text" or "textarea" types.
- "conditionalLogic": Null, or conditional logic rules object:
  Format: { "action": "show"|"hide", "triggerFieldId": "field_id", "triggerValue": "value" }
  The triggerFieldId must reference a select, radio, or checkbox field that exists on either the same page or a previous page.

How to edit forms:
- You will receive the CURRENT FORM SCHEMA as part of the prompt.
- Evaluate the user's instructions and apply them incrementally.
- If the user says "Add an address field", insert it at the end of the current page.
- If the user says "Make the email required", find the email field and set required = true.
- If the user says "Move the phone field after name", reorder the fields array.
- If the user says "Create a new page for survey questions", append a new page to the pages array.
- If the user asks to add conditional logic, construct the conditionalLogic object correctly. Make sure the triggerFieldId exists!
- Keep your changes minimal and precise. Do not destroy existing fields unless explicitly requested.
- Always output the complete updated form schema.
PROMPT;
    }
}
