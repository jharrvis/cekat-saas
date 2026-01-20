<?php

namespace App\Jobs;

use App\Models\KnowledgeDocument;
use App\Services\DocumentParser;
use App\Services\TextChunker;
use App\Services\WebsiteCrawler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300; // 5 minutes

    protected KnowledgeDocument $document;

    /**
     * Create a new job instance.
     */
    public function __construct(KnowledgeDocument $document)
    {
        $this->document = $document;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('Processing document', ['id' => $this->document->id, 'type' => $this->document->type]);

        try {
            $text = '';

            if ($this->document->type === 'url') {
                // Web scraping
                $crawler = new WebsiteCrawler();
                $text = $crawler->crawl($this->document->url);
            } else {
                // File parsing (PDF, DOCX, TXT)
                $filePath = storage_path('app/public/' . $this->document->file_path);

                if (!file_exists($filePath)) {
                    throw new \Exception('File not found: ' . $filePath);
                }

                $parser = new DocumentParser();
                $text = $parser->parse($filePath, $this->document->type);
            }

            // Sanitize UTF-8
            $text = $this->sanitizeUtf8($text);

            // Chunk the text
            $chunker = new TextChunker();
            $chunks = $chunker->chunk($text);

            // Update document with processed content
            $this->document->update([
                'content' => $text,
                'chunks' => $chunks,
                'status' => 'completed',
            ]);

            Log::info('Document processed successfully', [
                'id' => $this->document->id,
                'chunks' => count($chunks),
                'text_length' => strlen($text),
            ]);

        } catch (\Exception $e) {
            Log::error('Document processing failed', [
                'id' => $this->document->id,
                'error' => $e->getMessage(),
            ]);

            $this->document->update([
                'status' => 'failed',
                'content' => 'Error: ' . substr($e->getMessage(), 0, 500),
            ]);

            throw $e; // Re-throw to trigger retry
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Document processing job failed permanently', [
            'id' => $this->document->id,
            'error' => $exception->getMessage(),
        ]);

        $this->document->update([
            'status' => 'failed',
            'content' => 'Error: ' . substr($exception->getMessage(), 0, 500),
        ]);
    }

    /**
     * Sanitize UTF-8 string
     */
    private function sanitizeUtf8(string $string): string
    {
        // Remove BOM
        $string = preg_replace('/^\xEF\xBB\xBF/', '', $string);
        $string = preg_replace('/^\xFF\xFE/', '', $string);
        $string = preg_replace('/^\xFE\xFF/', '', $string);

        // Convert to UTF-8 if needed
        if (!mb_check_encoding($string, 'UTF-8')) {
            $string = mb_convert_encoding($string, 'UTF-8', 'auto');
        }

        // Remove invalid characters
        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $string);

        // Final cleanup
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

        return $string;
    }
}
