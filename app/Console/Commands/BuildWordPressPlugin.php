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

        $this->writeUpdateInfo($sourceDir);

        return 0;
    }

    /**
     * Write the update-info JSON consumed by the plugin's GitHub-based
     * auto-update checker. The file is committed next to the ZIP; both
     * are served straight from the public repo's raw URLs, so pushing
     * a build IS publishing the update - no separate release step.
     */
    private function writeUpdateInfo(string $sourceDir): void
    {
        $branch = 'integration/cek-remediation-20261007';
        $rawBase = "https://raw.githubusercontent.com/jharrvis/cekat-saas/{$branch}/public/downloads";

        $mainFile = file_get_contents($sourceDir . '/cekat-ai-chatbot.php');
        preg_match('/^\s*\*\s*Version:\s*(\S+)/m', $mainFile, $vm);
        $version = $vm[1] ?? '0.0.0';

        $readme = file_get_contents($sourceDir . '/readme.txt');
        $field = function (string $label) use ($readme): ?string {
            return preg_match('/^' . preg_quote($label, '/') . ':\s*(.+)$/m', $readme, $m) ? trim($m[1]) : null;
        };

        $changelog = '';
        if (preg_match('/== Changelog ==\s+=\s*[^=]+=\s*(.*?)(?=\n=\s*[\d.]+\s*=|\z)/s', $readme, $cm)) {
            $changelog = trim($cm[1]);
        }

        $info = [
            'version' => $version,
            'download_url' => $rawBase . '/cekat-ai-chatbot.zip',
            'requires' => $field('Requires at least'),
            'tested' => $field('Tested up to'),
            'requires_php' => $field('Requires PHP'),
            'last_updated' => now()->toDateString(),
            'changelog' => $changelog,
        ];

        $jsonPath = public_path('downloads/cekat-ai-chatbot-update.json');
        file_put_contents($jsonPath, json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
        $this->info("✓ Plugin update info written: {$jsonPath} (v{$version})");
    }
}
