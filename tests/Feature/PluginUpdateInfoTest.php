<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use ZipArchive;

/**
 * plugin:build-wp also publishes the update-info JSON that installed
 * plugins poll on GitHub (raw URL) for auto-updates. The JSON must
 * always agree with the shipped plugin: same version as the plugin
 * header, a GitHub download URL for the ZIP, and the ZIP must really
 * contain the updater class that consumes the JSON.
 */
class PluginUpdateInfoTest extends TestCase
{
    public function test_build_publishes_matching_update_info_and_zip(): void
    {
        $this->assertSame(0, Artisan::call('plugin:build-wp'));

        $pluginMain = file_get_contents(public_path('cekat-ai-chatbot/cekat-ai-chatbot.php'));
        preg_match('/^\s*\*\s*Version:\s*(\S+)/m', $pluginMain, $m);
        $headerVersion = $m[1];

        $jsonPath = public_path('downloads/cekat-ai-chatbot-update.json');
        $this->assertFileExists($jsonPath);

        $info = json_decode(file_get_contents($jsonPath), true);
        $this->assertSame($headerVersion, $info['version']);
        $this->assertStringStartsWith('https://raw.githubusercontent.com/jharrvis/cekat-saas/', $info['download_url']);
        $this->assertStringEndsWith('/public/downloads/cekat-ai-chatbot.zip', $info['download_url']);
        $this->assertSame('5.0', $info['requires']);
        $this->assertSame('7.4', $info['requires_php']);
        $this->assertNotEmpty($info['changelog']);

        $zip = new ZipArchive();
        $this->assertTrue($zip->open(public_path('downloads/cekat-ai-chatbot.zip')));
        $this->assertNotFalse($zip->locateName('cekat-ai-chatbot/includes/class-cekat-updater.php'));
        $zip->close();
    }
}
