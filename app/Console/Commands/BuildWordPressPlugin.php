<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ZipArchive;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class BuildWordPressPlugin extends Command
{
    protected $signature = 'plugin:build-wp';
    protected $description = 'Build WordPress plugin ZIP file for download';

    public function handle()
    {
        $sourceDir = public_path('cekat-ai-chatbot');
        $zipPath = public_path('downloads/cekat-ai-chatbot.zip');

        // Ensure downloads directory exists
        if (!file_exists(public_path('downloads'))) {
            mkdir(public_path('downloads'), 0755, true);
        }

        // Delete old ZIP if exists
        if (file_exists($zipPath)) {
            unlink($zipPath);
        }

        // Create ZIP
        $zip = new ZipArchive();

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            $this->error('Failed to create ZIP file');
            return 1;
        }

        // Add files recursively
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($sourceDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'cekat-ai-chatbot/' . substr($filePath, strlen($sourceDir) + 1);

                // Replace backslashes with forward slashes for ZIP compatibility
                $relativePath = str_replace('\\', '/', $relativePath);

                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        $size = round(filesize($zipPath) / 1024, 2);
        $this->info("✓ WordPress plugin ZIP created: {$zipPath}");
        $this->info("  Size: {$size} KB");

        return 0;
    }
}
