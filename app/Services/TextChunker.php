<?php

namespace App\Services;

class TextChunker
{
    private int $chunkSize;
    private int $overlap;

    public function __construct(int $chunkSize = 800, int $overlap = 100)
    {
        $this->chunkSize = $chunkSize;
        $this->overlap = $overlap;
    }

    public function chunk(string $text): array
    {
        // Clean up text
        $text = $this->cleanText($text);

        // Split by paragraphs first
        $paragraphs = preg_split('/\n\s*\n/', $text);

        $chunks = [];
        $currentChunk = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);

            if (empty($paragraph)) {
                continue;
            }

            // If adding this paragraph exceeds chunk size
            if (strlen($currentChunk) + strlen($paragraph) > $this->chunkSize) {
                // Save current chunk if not empty
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                }

                // If paragraph itself is larger than chunk size, split it
                if (strlen($paragraph) > $this->chunkSize) {
                    $subChunks = $this->splitLongParagraph($paragraph);
                    $chunks = array_merge($chunks, $subChunks);
                    $currentChunk = '';
                } else {
                    $currentChunk = $paragraph;
                }
            } else {
                // Add paragraph to current chunk
                $currentChunk .= ($currentChunk ? "\n\n" : '') . $paragraph;
            }
        }

        // Add remaining chunk
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return array_filter($chunks);
    }

    private function cleanText(string $text): string
    {
        // Remove excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);
        // Remove excessive newlines
        $text = preg_replace('/\n{3,}/', "\n\n", $text);
        // Trim
        return trim($text);
    }

    private function splitLongParagraph(string $paragraph): array
    {
        $chunks = [];
        $sentences = preg_split('/(?<=[.!?])\s+/', $paragraph);
        $currentChunk = '';

        foreach ($sentences as $sentence) {
            if (strlen($currentChunk) + strlen($sentence) > $this->chunkSize) {
                if (!empty($currentChunk)) {
                    $chunks[] = trim($currentChunk);
                }
                $currentChunk = $sentence;
            } else {
                $currentChunk .= ($currentChunk ? ' ' : '') . $sentence;
            }
        }

        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }

        return $chunks;
    }
}
