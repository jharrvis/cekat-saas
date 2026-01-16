<?php

namespace App\Services;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class WebsiteCrawler
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'timeout' => 30,
            'verify' => false, // For development, enable in production
        ]);
    }

    public function crawl(string $url): string
    {
        try {
            $response = $this->client->get($url);
            $html = $response->getBody()->getContents();

            return $this->extractText($html);
        } catch (\Exception $e) {
            throw new \Exception('Failed to crawl website: ' . $e->getMessage());
        }
    }

    private function extractText(string $html): string
    {
        $crawler = new Crawler($html);

        // Remove unwanted elements
        $crawler->filter('script, style, nav, header, footer, iframe, noscript')->each(function (Crawler $node) {
            foreach ($node as $child) {
                $child->parentNode->removeChild($child);
            }
        });

        // Extract text from body
        try {
            $text = $crawler->filter('body')->text();
        } catch (\Exception $e) {
            // Fallback to full HTML text if body not found
            $text = $crawler->text();
        }

        // Clean up text
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);

        return $text;
    }
}
