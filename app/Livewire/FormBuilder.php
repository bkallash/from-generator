<?php

namespace App\Livewire;

use App\Models\Form;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\On;

class FormBuilder extends Component
{
    public ?int $formId = null;
    public string $title = 'Untitled Form';
    public string $description = '';
    
    // AI Chat States
    public array $aiChatHistory = [];
    public bool $showAiChat = true;
    
    // Multi-page states
    public array $pages = [];
    public int $currentPageIndex = 0;
    public string $pageTitle = '';
    public string $pageDescription = '';

    // Active page's fields (kept in sync for canvas compatibility)
    public array $fields = [];
    
    public ?int $activeFieldIndex = null;
    public bool $showSaveNotification = false;
    public bool $isDirty = false;

    // Active field editing properties
    public string $editLabel = '';
    public string $editPlaceholder = '';
    public bool $editRequired = false;
    public string $editOptions = '';
    public bool $editAnalyzeSentiment = false;

    // Active field conditional logic properties
    public bool $editConditionalEnabled = false;
    public string $editConditionalAction = 'show';
    public string $editConditionalTriggerField = '';
    public string $editConditionalTriggerValue = '';

    // Page-level conditional logic properties
    public bool $editPageConditionalEnabled = false;
    public string $editPageConditionalAction = 'show';
    public string $editPageConditionalTriggerField = '';
    public string $editPageConditionalTriggerValue = '';

    protected $listeners = [
        'reorderFields',
        'fieldDropped',
    ];

    public function mount(?int $formId = null): void
    {
        if ($formId) {
            $form = Form::where('user_id', auth()->id())->findOrFail($formId);
            $this->formId = $form->id;
            $this->title = $form->title;
            $this->description = $form->description ?? '';
            $this->aiChatHistory = $form->settings['ai_chat_history'] ?? [];
            
            $schema = $form->schema;
            if (isset($schema['pages'])) {
                $this->pages = $schema['pages'];
            } elseif (isset($schema['fields'])) {
                $this->pages = [
                    [
                        'id' => 'page_' . Str::random(8),
                        'title' => 'Page 1',
                        'description' => '',
                        'fields' => $schema['fields'],
                        'conditionalLogic' => null,
                    ]
                ];
            } else {
                $this->pages = [
                    [
                        'id' => 'page_' . Str::random(8),
                        'title' => 'Page 1',
                        'description' => '',
                        'fields' => [],
                        'conditionalLogic' => null,
                    ]
                ];
            }
        } else {
            $this->pages = [
                [
                    'id' => 'page_' . Str::random(8),
                    'title' => 'Page 1',
                    'description' => '',
                    'fields' => [],
                    'conditionalLogic' => null,
                ]
            ];
        }

        $this->switchPage(0);
    }

    /**
     * Synchronize the public $fields array to the active page in $pages.
     */
    private function syncFieldsToPage(): void
    {
        if (isset($this->pages[$this->currentPageIndex])) {
            $this->pages[$this->currentPageIndex]['fields'] = $this->fields;
        }
    }

    /**
     * Synchronize the active page fields into the public $fields array.
     */
    private function syncFieldsFromPage(): void
    {
        $this->fields = $this->pages[$this->currentPageIndex]['fields'] ?? [];
    }

    /**
     * Available field types for the palette.
     */
    public function getFieldTypesProperty(): array
    {
        return [
            ['type' => 'text', 'label' => 'Text Input', 'icon' => 'text', 'group' => 'basic'],
            ['type' => 'email', 'label' => 'Email', 'icon' => 'email', 'group' => 'basic'],
            ['type' => 'number', 'label' => 'Number', 'icon' => 'number', 'group' => 'basic'],
            ['type' => 'phone', 'label' => 'Phone', 'icon' => 'phone', 'group' => 'basic'],
            ['type' => 'url', 'label' => 'URL', 'icon' => 'url', 'group' => 'basic'],
            ['type' => 'textarea', 'label' => 'Text Area', 'icon' => 'textarea', 'group' => 'basic'],
            ['type' => 'select', 'label' => 'Dropdown', 'icon' => 'select', 'group' => 'choice'],
            ['type' => 'radio', 'label' => 'Radio Group', 'icon' => 'radio', 'group' => 'choice'],
            ['type' => 'checkbox', 'label' => 'Checkboxes', 'icon' => 'checkbox', 'group' => 'choice'],
            ['type' => 'date', 'label' => 'Date Picker', 'icon' => 'date', 'group' => 'advanced'],
            ['type' => 'image', 'label' => 'Image Upload', 'icon' => 'image', 'group' => 'advanced'],
            ['type' => 'file', 'label' => 'File Upload', 'icon' => 'file', 'group' => 'advanced'],
            ['type' => 'heading', 'label' => 'Heading', 'icon' => 'heading', 'group' => 'layout'],
            ['type' => 'paragraph', 'label' => 'Paragraph', 'icon' => 'paragraph', 'group' => 'layout'],
            ['type' => 'divider', 'label' => 'Divider', 'icon' => 'divider', 'group' => 'layout'],
        ];
    }

    /**
     * Add a new field to the active page.
     */
    public function addField(string $type, ?int $position = null): void
    {
        $defaults = $this->getFieldDefaults($type);

        $field = [
            'id' => 'field_' . Str::random(8),
            'type' => $type,
            'label' => $defaults['label'],
            'placeholder' => $defaults['placeholder'],
            'required' => false,
            'options' => $defaults['options'],
            'analyze_sentiment' => false,
            'conditionalLogic' => null,
        ];

        if ($position !== null && $position >= 0 && $position <= count($this->fields)) {
            array_splice($this->fields, $position, 0, [$field]);
            $this->activeFieldIndex = $position;
        } else {
            $this->fields[] = $field;
            $this->activeFieldIndex = count($this->fields) - 1;
        }

        $this->syncActiveField();
        $this->syncFieldsToPage();
        $this->isDirty = true;
    }

    /**
     * Handle field drop from palette at a specific position.
     */
    public function fieldDropped(string $type, int $position): void
    {
        $this->addField($type, $position);
    }

    /**
     * Remove a field by index.
     */
    public function removeField(int $index): void
    {
        if (isset($this->fields[$index])) {
            array_splice($this->fields, $index, 1);
            $this->fields = array_values($this->fields);
            $this->syncFieldsToPage();
            $this->isDirty = true;

            if ($this->activeFieldIndex === $index) {
                $this->activeFieldIndex = null;
                $this->resetEditProperties();
            } elseif ($this->activeFieldIndex !== null && $this->activeFieldIndex > $index) {
                $this->activeFieldIndex--;
            }
        }
    }

    /**
     * Duplicate a field.
     */
    public function duplicateField(int $index): void
    {
        if (isset($this->fields[$index])) {
            $newField = $this->fields[$index];
            $newField['id'] = 'field_' . Str::random(8);
            $newField['label'] = $newField['label'] . ' (copy)';
            array_splice($this->fields, $index + 1, 0, [$newField]);
            $this->fields = array_values($this->fields);
            $this->syncFieldsToPage();
            $this->activeFieldIndex = $index + 1;
            $this->syncActiveField();
            $this->isDirty = true;
        }
    }

    /**
     * Set the active field for editing.
     */
    public function selectField(int $index): void
    {
        $this->activeFieldIndex = $index;
        $this->syncActiveField();
    }

    /**
     * Deselect the active field.
     */
    public function deselectField(): void
    {
        $this->activeFieldIndex = null;
        $this->resetEditProperties();
    }

    /**
     * Update field order after drag-and-drop reordering.
     */
    public function reorderFields(array $orderedIds): void
    {
        $reordered = [];
        foreach ($orderedIds as $id) {
            foreach ($this->fields as $field) {
                if ($field['id'] === $id) {
                    $reordered[] = $field;
                    break;
                }
            }
        }

        $this->fields = $reordered;
        $this->syncFieldsToPage();
        $this->isDirty = true;

        // Update active field index after reorder
        if ($this->activeFieldIndex !== null && isset($this->fields[$this->activeFieldIndex])) {
            $activeId = $this->fields[$this->activeFieldIndex]['id'] ?? null;
            if ($activeId) {
                foreach ($this->fields as $i => $field) {
                    if ($field['id'] === $activeId) {
                        $this->activeFieldIndex = $i;
                        break;
                    }
                }
            }
        }
    }

    /**
     * Page navigation / actions
     */
    public function addPage(): void
    {
        $this->syncFieldsToPage();
        
        $newPageId = 'page_' . Str::random(8);
        $pageNum = count($this->pages) + 1;
        $this->pages[] = [
            'id' => $newPageId,
            'title' => "Page $pageNum",
            'description' => '',
            'fields' => [],
            'conditionalLogic' => null,
        ];
        
        $this->isDirty = true;
        $this->switchPage(count($this->pages) - 1);
    }

    public function removePage(int $index): void
    {
        if (count($this->pages) <= 1) {
            return;
        }

        array_splice($this->pages, $index, 1);
        $this->pages = array_values($this->pages);
        $this->isDirty = true;

        if ($this->currentPageIndex >= count($this->pages)) {
            $this->currentPageIndex = count($this->pages) - 1;
        }

        $this->switchPage($this->currentPageIndex);
    }

    public function switchPage(int $index): void
    {
        if ($this->currentPageIndex !== $index && isset($this->pages[$this->currentPageIndex])) {
            $this->syncFieldsToPage();
        }

        $this->currentPageIndex = $index;
        $this->syncFieldsFromPage();
        $this->pageTitle = $this->pages[$index]['title'] ?? '';
        $this->pageDescription = $this->pages[$index]['description'] ?? '';
        $this->activeFieldIndex = null;
        $this->resetEditProperties();
        $this->syncPageConditionalProperties();
    }

    public function updatedPageTitle(): void
    {
        if (isset($this->pages[$this->currentPageIndex])) {
            $this->pages[$this->currentPageIndex]['title'] = $this->pageTitle;
            $this->isDirty = true;
        }
    }

    public function updatedPageDescription(): void
    {
        if (isset($this->pages[$this->currentPageIndex])) {
            $this->pages[$this->currentPageIndex]['description'] = $this->pageDescription;
            $this->isDirty = true;
        }
    }

    /**
     * Apply page-level conditional logic edits
     */
    public function applyPageConditionalEdits(): void
    {
        if (!isset($this->pages[$this->currentPageIndex])) {
            return;
        }

        if ($this->editPageConditionalEnabled) {
            $this->pages[$this->currentPageIndex]['conditionalLogic'] = [
                'action' => $this->editPageConditionalAction ?: 'show',
                'triggerFieldId' => $this->editPageConditionalTriggerField,
                'triggerValue' => $this->editPageConditionalTriggerValue,
            ];
        } else {
            $this->pages[$this->currentPageIndex]['conditionalLogic'] = null;
        }
        $this->isDirty = true;
    }

    public function updatedEditPageConditionalEnabled(): void
    {
        $this->applyPageConditionalEdits();
    }

    public function updatedEditPageConditionalAction(): void
    {
        $this->applyPageConditionalEdits();
    }

    public function updatedEditPageConditionalTriggerField(): void
    {
        $this->editPageConditionalTriggerValue = '';
        $this->applyPageConditionalEdits();
    }

    public function updatedEditPageConditionalTriggerValue(): void
    {
        $this->applyPageConditionalEdits();
    }

    /**
     * Apply edits from the properties panel to the active field.
     */
    public function applyFieldEdits(): void
    {
        if ($this->activeFieldIndex === null || !isset($this->fields[$this->activeFieldIndex])) {
            return;
        }

        $this->fields[$this->activeFieldIndex]['label'] = $this->editLabel;
        $this->fields[$this->activeFieldIndex]['placeholder'] = $this->editPlaceholder;
        $this->fields[$this->activeFieldIndex]['required'] = $this->editRequired;
        $this->fields[$this->activeFieldIndex]['analyze_sentiment'] = $this->editAnalyzeSentiment;
        
        if ($this->editConditionalEnabled) {
            $this->fields[$this->activeFieldIndex]['conditionalLogic'] = [
                'action' => $this->editConditionalAction ?: 'show',
                'triggerFieldId' => $this->editConditionalTriggerField,
                'triggerValue' => $this->editConditionalTriggerValue,
            ];
        } else {
            $this->fields[$this->activeFieldIndex]['conditionalLogic'] = null;
        }

        $this->isDirty = true;
        
        // Parse options for select/radio/checkbox
        $type = $this->fields[$this->activeFieldIndex]['type'];
        if (in_array($type, ['select', 'radio', 'checkbox'])) {
            $this->fields[$this->activeFieldIndex]['options'] = array_filter(
                array_map('trim', explode("\n", $this->editOptions))
            );
        }

        $this->syncFieldsToPage();
    }

    /**
     * Save the form to the database.
     */
    public function saveForm(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
        ]);

        $this->syncFieldsToPage();

        $schema = ['pages' => $this->pages];

        if ($this->formId) {
            $form = Form::where('user_id', auth()->id())->findOrFail($this->formId);
            $settings = $form->settings ?? [];
            $settings['ai_chat_history'] = $this->aiChatHistory;
            $form->update([
                'title' => $this->title,
                'description' => $this->description,
                'schema' => $schema,
                'settings' => $settings,
            ]);
        } else {
            $form = Form::create([
                'user_id' => auth()->id(),
                'title' => $this->title,
                'description' => $this->description,
                'slug' => Str::lower(Str::random(10)),
                'schema' => $schema,
                'settings' => ['ai_chat_history' => $this->aiChatHistory],
            ]);
            $this->formId = $form->id;
        }

        $this->isDirty = false;
        $this->dispatch('form-saved');
        $this->redirectRoute('dashboard', ['view' => 'forms']);
    }

    public function updatedEditLabel(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditPlaceholder(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditRequired(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditAnalyzeSentiment(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditOptions(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditConditionalEnabled(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditConditionalAction(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedEditConditionalTriggerField(): void
    {
        $this->editConditionalTriggerValue = '';
        $this->applyFieldEdits();
    }

    public function updatedEditConditionalTriggerValue(): void
    {
        $this->applyFieldEdits();
    }

    public function updatedTitle(): void
    {
        $this->isDirty = true;
    }

    public function updatedDescription(): void
    {
        $this->isDirty = true;
    }

    /**
     * Sync page-level conditional logic config
     */
    private function syncPageConditionalProperties(): void
    {
        $page = $this->pages[$this->currentPageIndex] ?? null;
        if ($page && isset($page['conditionalLogic']) && $page['conditionalLogic']) {
            $this->editPageConditionalEnabled = true;
            $this->editPageConditionalAction = $page['conditionalLogic']['action'] ?? 'show';
            $this->editPageConditionalTriggerField = $page['conditionalLogic']['triggerFieldId'] ?? '';
            $this->editPageConditionalTriggerValue = $page['conditionalLogic']['triggerValue'] ?? '';
        } else {
            $this->editPageConditionalEnabled = false;
            $this->editPageConditionalAction = 'show';
            $this->editPageConditionalTriggerField = '';
            $this->editPageConditionalTriggerValue = '';
        }
    }

    /**
     * Sync edit properties from the currently active field.
     */
    private function syncActiveField(): void
    {
        if ($this->activeFieldIndex !== null && isset($this->fields[$this->activeFieldIndex])) {
            $field = $this->fields[$this->activeFieldIndex];
            $this->editLabel = $field['label'] ?? '';
            $this->editPlaceholder = $field['placeholder'] ?? '';
            $this->editRequired = $field['required'] ?? false;
            $this->editOptions = implode("\n", $field['options'] ?? []);
            $this->editAnalyzeSentiment = $field['analyze_sentiment'] ?? false;
            
            if (isset($field['conditionalLogic']) && $field['conditionalLogic']) {
                $this->editConditionalEnabled = true;
                $this->editConditionalAction = $field['conditionalLogic']['action'] ?? 'show';
                $this->editConditionalTriggerField = $field['conditionalLogic']['triggerFieldId'] ?? '';
                $this->editConditionalTriggerValue = $field['conditionalLogic']['triggerValue'] ?? '';
            } else {
                $this->editConditionalEnabled = false;
                $this->editConditionalAction = 'show';
                $this->editConditionalTriggerField = '';
                $this->editConditionalTriggerValue = '';
            }
        }
    }

    private function resetEditProperties(): void
    {
        $this->editLabel = '';
        $this->editPlaceholder = '';
        $this->editRequired = false;
        $this->editOptions = '';
        $this->editAnalyzeSentiment = false;
        
        $this->editConditionalEnabled = false;
        $this->editConditionalAction = 'show';
        $this->editConditionalTriggerField = '';
        $this->editConditionalTriggerValue = '';
    }

    /**
     * Computed trigger options for field conditional logic
     */
    public function getConditionalTriggerOptionsProperty(): array
    {
        if (!$this->editConditionalTriggerField) {
            return [];
        }
        foreach ($this->pages as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                if ($field['id'] === $this->editConditionalTriggerField) {
                    return $field['options'] ?? [];
                }
            }
        }
        return [];
    }

    /**
     * Computed trigger options for page-level conditional logic
     */
    public function getPageConditionalTriggerOptionsProperty(): array
    {
        if (!$this->editPageConditionalTriggerField) {
            return [];
        }
        foreach ($this->pages as $page) {
            foreach ($page['fields'] ?? [] as $field) {
                if ($field['id'] === $this->editPageConditionalTriggerField) {
                    return $field['options'] ?? [];
                }
            }
        }
        return [];
    }

    public function getConditionalTriggerFieldsProperty(): array
    {
        $triggers = [];
        $activeFieldId = null;
        if ($this->activeFieldIndex !== null && isset($this->fields[$this->activeFieldIndex])) {
            $activeFieldId = $this->fields[$this->activeFieldIndex]['id'] ?? null;
        }

        // If editing field, triggers can be from this page or previous pages.
        // If editing page settings, triggers can only be from previous pages.
        $maxPageIdx = ($activeFieldId !== null) ? $this->currentPageIndex : ($this->currentPageIndex - 1);

        foreach ($this->pages as $pageIdx => $page) {
            if ($pageIdx > $maxPageIdx) {
                continue;
            }
            $pageName = $page['title'] ?: 'Page ' . ($pageIdx + 1);
            foreach ($page['fields'] ?? [] as $field) {
                if ($activeFieldId && $field['id'] === $activeFieldId) {
                    continue;
                }
                if (in_array($field['type'], ['select', 'radio', 'checkbox'])) {
                    $triggers[] = [
                        'id' => $field['id'],
                        'label' => $field['label'] . ' (' . $pageName . ')',
                    ];
                }
            }
        }
        return $triggers;
    }

    /**
     * Get sensible defaults for each field type.
     */
    private function getFieldDefaults(string $type): array
    {
        return match ($type) {
            'text' => ['label' => 'Text Field', 'placeholder' => 'Enter text...', 'options' => []],
            'email' => ['label' => 'Email Address', 'placeholder' => 'you@example.com', 'options' => []],
            'number' => ['label' => 'Number', 'placeholder' => '0', 'options' => []],
            'phone' => ['label' => 'Phone Number', 'placeholder' => '+1 (555) 000-0000', 'options' => []],
            'url' => ['label' => 'Website URL', 'placeholder' => 'https://', 'options' => []],
            'textarea' => ['label' => 'Message', 'placeholder' => 'Type your message...', 'options' => []],
            'select' => ['label' => 'Select Option', 'placeholder' => 'Choose...', 'options' => ['Option 1', 'Option 2', 'Option 3']],
            'radio' => ['label' => 'Choose One', 'placeholder' => '', 'options' => ['Option 1', 'Option 2', 'Option 3']],
            'checkbox' => ['label' => 'Select Multiple', 'placeholder' => '', 'options' => ['Option 1', 'Option 2', 'Option 3']],
            'date' => ['label' => 'Date', 'placeholder' => 'Select a date', 'options' => []],
            'image' => ['label' => 'Upload Image', 'placeholder' => 'Choose image...', 'options' => []],
            'file' => ['label' => 'Upload File', 'placeholder' => 'Choose file...', 'options' => []],
            'heading' => ['label' => 'Section Heading', 'placeholder' => '', 'options' => []],
            'paragraph' => ['label' => 'Add some descriptive text here.', 'placeholder' => '', 'options' => []],
            'divider' => ['label' => '', 'placeholder' => '', 'options' => []],
            default => ['label' => 'Field', 'placeholder' => '', 'options' => []],
        };
    }

    public function toggleAiPanel(): void
    {
        $this->showAiChat = !$this->showAiChat;
    }

    #[On('updateChatHistory')]
    public function updateChatHistory(array $history): void
    {
        $this->aiChatHistory = $history;
        $this->isDirty = true;
    }

    #[On('aiSchemaGenerated')]
    public function handleAiSchemaGenerated(array $data): void
    {
        $this->pages = $data['pages'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        
        if ($this->currentPageIndex >= count($this->pages)) {
            $this->currentPageIndex = 0;
        }
        
        $this->syncFieldsFromPage();
        
        $this->activeFieldIndex = null;
        $this->resetEditProperties();
        $this->syncPageConditionalProperties();
        
        $this->isDirty = true;
    }

    public function render()
    {
        return view('livewire.form-builder');
    }
}
