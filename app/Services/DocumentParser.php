<?php

namespace App\Services;

use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory;

class DocumentParser
{
    public function parse(string $filePath, string $type): string
    {
        return match (strtolower($type)) {
            'pdf' => $this->parsePdf($filePath),
            'docx' => $this->parseDocx($filePath),
            'txt' => file_get_contents($filePath),
            default => throw new \Exception('Unsupported file type: ' . $type),
        };
    }

    private function parsePdf(string $filePath): string
    {
        try {
            $parser = new PdfParser();
            $pdf = $parser->parseFile($filePath);
            return $pdf->getText();
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse PDF: ' . $e->getMessage());
        }
    }

    private function parseDocx(string $filePath): string
    {
        try {
            $phpWord = IOFactory::load($filePath);
            $text = '';

            foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . "\n";
                    } elseif (method_exists($element, 'getElements')) {
                        // Handle nested elements (like tables)
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $text .= $childElement->getText() . " ";
                            }
                        }
                        $text .= "\n";
                    }
                }
            }

            return $text;
        } catch (\Exception $e) {
            throw new \Exception('Failed to parse DOCX: ' . $e->getMessage());
        }
    }
}
