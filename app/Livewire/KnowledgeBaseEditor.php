<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Widget;
use App\Models\KnowledgeBase;
use App\Models\KnowledgeFaq;
use App\Models\KnowledgeDocument;
use App\Services\DocumentParser;
use App\Services\TextChunker;
use App\Services\WebsiteCrawler;

class KnowledgeBaseEditor extends Component
{
    use WithFileUploads;

    public $widget;
    public $knowledgeBase;

    // Company Info
    public $company_name = '';
    public $company_description = '';
    public $persona_name = '';
    public $persona_tone = 'friendly';
    public $customer_greeting = 'Kak';
    public $custom_instructions = '';

    // FAQs
    public $faqs = [];
    public $newFaqQuestion = '';
    public $newFaqAnswer = '';
    public $editingFaqId = null;
    public $editFaqQuestion = '';
    public $editFaqAnswer = '';

    // Documents
    public $documents = [];
    public $uploadedFile;
    public $websiteUrl = '';

    public $widgetId = null;

    public function mount($widgetId = null)
    {
        $this->widgetId = $widgetId;

        // If widgetId is provided, use that widget (for admin/specific chatbot editing)
        if ($widgetId) {
            $this->widget = Widget::find($widgetId);
        } else {
            // Fallback to user's first widget
            $this->widget = auth()->user()->widgets()->first();
        }

        if (!$this->widget) {
            $this->widget = auth()->user()->widgets()->create([
                'name' => 'My Widget',
                'slug' => 'widget-' . auth()->id(),
                'is_active' => true,
            ]);
        }

        // Get or create knowledge base
        $this->knowledgeBase = $this->widget->knowledgeBase;

        if (!$this->knowledgeBase) {
            $this->knowledgeBase = $this->widget->knowledgeBase()->create([
                'company_name' => '',
                'persona_name' => 'AI Assistant',
                'persona_tone' => 'friendly',
            ]);
        }

        // Load data
        $this->loadKnowledgeBase();
    }

    public function loadKnowledgeBase()
    {
        $this->company_name = $this->knowledgeBase->company_name ?? '';
        $this->company_description = $this->knowledgeBase->company_description ?? '';
        $this->persona_name = $this->knowledgeBase->persona_name ?? '';
        $this->persona_tone = $this->knowledgeBase->persona_tone ?? 'friendly';
        $this->customer_greeting = $this->knowledgeBase->customer_greeting ?? 'Kak';
        $this->custom_instructions = $this->knowledgeBase->custom_instructions ?? '';

        $this->faqs = $this->knowledgeBase->faqs()->orderBy('sort_order')->get()->toArray();
        $this->documents = $this->knowledgeBase->documents()->orderBy('created_at', 'desc')->get()->toArray();
    }

    public function saveCompanyInfo()
    {
        $this->validate([
            'company_name' => 'required|max:255',
            'company_description' => 'nullable|max:1000',
            'persona_name' => 'required|max:100',
            'persona_tone' => 'required',
            'customer_greeting' => 'nullable|max:50',
            'custom_instructions' => 'nullable|max:2000',
        ]);

        $this->knowledgeBase->update([
            'company_name' => $this->company_name,
            'company_description' => $this->company_description,
            'persona_name' => $this->persona_name,
            'persona_tone' => $this->persona_tone,
            'customer_greeting' => $this->customer_greeting,
            'custom_instructions' => $this->custom_instructions,
        ]);

        session()->flash('message', 'Company info saved successfully!');
    }

    // FAQ Methods
    public function addFaq()
    {
        $this->validate([
            'newFaqQuestion' => 'required|max:500',
            'newFaqAnswer' => 'required|max:2000',
        ]);

        $maxOrder = $this->knowledgeBase->faqs()->max('sort_order') ?? 0;

        $this->knowledgeBase->faqs()->create([
            'question' => $this->newFaqQuestion,
            'answer' => $this->newFaqAnswer,
            'sort_order' => $maxOrder + 1,
        ]);

        $this->newFaqQuestion = '';
        $this->newFaqAnswer = '';
        $this->loadKnowledgeBase();

        session()->flash('message', 'FAQ added successfully!');
    }

    public function editFaq($faqId)
    {
        $faq = KnowledgeFaq::find($faqId);
        $this->editingFaqId = $faqId;
        $this->editFaqQuestion = $faq->question;
        $this->editFaqAnswer = $faq->answer;
    }

    public function updateFaq()
    {
        $this->validate([
            'editFaqQuestion' => 'required|max:500',
            'editFaqAnswer' => 'required|max:2000',
        ]);

        $faq = KnowledgeFaq::find($this->editingFaqId);
        $faq->update([
            'question' => $this->editFaqQuestion,
            'answer' => $this->editFaqAnswer,
        ]);

        $this->editingFaqId = null;
        $this->editFaqQuestion = '';
        $this->editFaqAnswer = '';
        $this->loadKnowledgeBase();

        session()->flash('message', 'FAQ updated successfully!');
    }

    public function cancelEdit()
    {
        $this->editingFaqId = null;
        $this->editFaqQuestion = '';
        $this->editFaqAnswer = '';
    }

    public function deleteFaq($faqId)
    {
        KnowledgeFaq::find($faqId)->delete();
        $this->loadKnowledgeBase();

        session()->flash('message', 'FAQ deleted successfully!');
    }

    public function moveFaqUp($faqId)
    {
        $faq = KnowledgeFaq::find($faqId);
        $previousFaq = KnowledgeFaq::where('knowledge_base_id', $this->knowledgeBase->id)
            ->where('sort_order', '<', $faq->sort_order)
            ->orderBy('sort_order', 'desc')
            ->first();

        if ($previousFaq) {
            $tempOrder = $faq->sort_order;
            $faq->sort_order = $previousFaq->sort_order;
            $previousFaq->sort_order = $tempOrder;
            $faq->save();
            $previousFaq->save();
        }

        $this->loadKnowledgeBase();
    }

    public function moveFaqDown($faqId)
    {
        $faq = KnowledgeFaq::find($faqId);
        $nextFaq = KnowledgeFaq::where('knowledge_base_id', $this->knowledgeBase->id)
            ->where('sort_order', '>', $faq->sort_order)
            ->orderBy('sort_order', 'asc')
            ->first();

        if ($nextFaq) {
            $tempOrder = $faq->sort_order;
            $faq->sort_order = $nextFaq->sort_order;
            $nextFaq->sort_order = $tempOrder;
            $faq->save();
            $nextFaq->save();
        }

        $this->loadKnowledgeBase();
    }

    // Document Upload Methods
    public function uploadFile()
    {
        $this->validate([
            'uploadedFile' => 'required|file|mimes:pdf,docx,txt|max:10240', // 10MB max
        ]);

        try {
            $file = $this->uploadedFile;
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('documents', $filename, 'public');

            // Create document record
            $document = $this->knowledgeBase->documents()->create([
                'name' => $file->getClientOriginalName(),
                'type' => $extension,
                'file_path' => $path,
                'status' => 'processing',
            ]);

            // Parse and process file
            $this->processDocument($document);

            $this->uploadedFile = null;
            $this->loadKnowledgeBase();

            session()->flash('message', 'File uploaded and processed successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to upload file: ' . $e->getMessage());
        }
    }

    public function crawlWebsite()
    {
        $this->validate([
            'websiteUrl' => 'required|url',
        ]);

        try {
            // Create document record
            $document = $this->knowledgeBase->documents()->create([
                'name' => $this->websiteUrl,
                'type' => 'url',
                'url' => $this->websiteUrl,
                'status' => 'processing',
            ]);

            // Crawl and process website
            $crawler = new WebsiteCrawler();
            $text = $crawler->crawl($this->websiteUrl);

            // Chunk text
            $chunker = new TextChunker();
            $chunks = $chunker->chunk($text);

            // Update document
            $document->update([
                'content' => $text,
                'chunks' => json_encode($chunks),
                'status' => 'completed',
            ]);

            $this->websiteUrl = '';
            $this->loadKnowledgeBase();

            session()->flash('message', 'Website crawled successfully!');
        } catch (\Exception $e) {
            if (isset($document)) {
                $document->update(['status' => 'failed']);
            }
            session()->flash('error', 'Failed to crawl website: ' . $e->getMessage());
        }
    }

    public function deleteDocument($documentId)
    {
        $document = KnowledgeDocument::find($documentId);

        // Delete file if exists
        if ($document->file_path && \Storage::disk('public')->exists($document->file_path)) {
            \Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();
        $this->loadKnowledgeBase();

        session()->flash('message', 'Document deleted successfully!');
    }

    private function processDocument($document)
    {
        try {
            $filePath = storage_path('app/public/' . $document->file_path);

            // Parse document
            $parser = new DocumentParser();
            $text = $parser->parse($filePath, $document->type);

            // Sanitize text - remove BOM and invalid UTF-8 characters
            $text = $this->sanitizeUtf8($text);

            // Chunk text
            $chunker = new TextChunker();
            $chunks = $chunker->chunk($text);

            // Update document
            $document->update([
                'content' => $text,
                'chunks' => json_encode($chunks),
                'status' => 'completed',
            ]);
        } catch (\Exception $e) {
            $document->update(['status' => 'failed', 'content' => substr($e->getMessage(), 0, 500)]);
            throw $e;
        }
    }

    /**
     * Sanitize string to valid UTF-8
     */
    private function sanitizeUtf8($string)
    {
        // Remove BOM (Byte Order Mark)
        $string = preg_replace('/^\xEF\xBB\xBF/', '', $string);
        $string = preg_replace('/^\xFF\xFE/', '', $string); // UTF-16 LE BOM
        $string = preg_replace('/^\xFE\xFF/', '', $string); // UTF-16 BE BOM

        // Convert to UTF-8 if needed
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }

        // Remove any remaining invalid UTF-8 characters
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Final cleanup - ensure valid UTF-8
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

        return $string;
    }

    public function render()
    {
        return view('livewire.knowledge-base-editor');
    }
}
