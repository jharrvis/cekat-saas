<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use App\Services\Chat\LlmProviderChain;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

/**
 * AI provider configuration (Admin > AI Models & Tiers).
 *
 * OpenRouter stays configurable through .env for its API key; this panel
 * controls which providers the chat chain may use and SumoPod's
 * connection details. Whatever the toggles say, openrouter/free is
 * always the chain's final attempt (see LlmProviderChain).
 */
class AiProviderSettings extends Component
{
    public bool $openrouterEnabled = true;

    public bool $sumopodEnabled = false;

    public string $sumopodApiKey = '';

    public bool $hasSumopodKey = false;

    public string $sumopodBaseUrl = LlmProviderChain::DEFAULT_SUMOPOD_BASE_URL;

    public string $sumopodModel = '';

    public ?string $testResult = null;

    public bool $testOk = false;

    public function mount(): void
    {
        $this->openrouterEnabled = filter_var(Setting::get('ai_openrouter_enabled', true), FILTER_VALIDATE_BOOLEAN);
        $this->sumopodEnabled = filter_var(Setting::get('ai_sumopod_enabled', false), FILTER_VALIDATE_BOOLEAN);
        $this->hasSumopodKey = filled(Setting::get('ai_sumopod_api_key'));
        $this->sumopodBaseUrl = (string) Setting::get('ai_sumopod_base_url', LlmProviderChain::DEFAULT_SUMOPOD_BASE_URL);
        $this->sumopodModel = (string) Setting::get('ai_sumopod_model', '');
    }

    protected function rules(): array
    {
        return [
            'openrouterEnabled' => 'boolean',
            'sumopodEnabled' => 'boolean',
            'sumopodBaseUrl' => 'required|url|max:255',
            'sumopodModel' => 'nullable|string|max:191',
            'sumopodApiKey' => 'nullable|string|max:255',
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->sumopodEnabled && ! $this->hasSumopodKey && ! filled($this->sumopodApiKey)) {
            $this->addError('sumopodApiKey', __('admin.s.sumopod_key_required'));

            return;
        }

        if ($this->sumopodEnabled && ! filled($this->sumopodModel)) {
            $this->addError('sumopodModel', __('admin.s.sumopod_model_required'));

            return;
        }

        Setting::set('ai_openrouter_enabled', $this->openrouterEnabled, 'boolean', 'ai');
        Setting::set('ai_sumopod_enabled', $this->sumopodEnabled, 'boolean', 'ai');
        Setting::set('ai_sumopod_base_url', rtrim($this->sumopodBaseUrl, '/'), 'string', 'ai');
        Setting::set('ai_sumopod_model', trim($this->sumopodModel), 'string', 'ai');

        // An empty key field means "keep the stored key" - the stored
        // value is never rendered back into the page.
        if (filled($this->sumopodApiKey)) {
            Setting::set('ai_sumopod_api_key', trim($this->sumopodApiKey), 'string', 'ai');
            $this->hasSumopodKey = true;
        }
        $this->sumopodApiKey = '';

        session()->flash('provider_message', __('admin.s.ai_provider_settings_saved'));
    }

    /** Probe SumoPod's model catalogue with the entered or stored key. */
    public function testConnection(): void
    {
        $this->testResult = null;
        $key = filled($this->sumopodApiKey) ? trim($this->sumopodApiKey) : (string) Setting::get('ai_sumopod_api_key');
        $base = rtrim(trim($this->sumopodBaseUrl), '/');

        if (! filled($key)) {
            $this->testOk = false;
            $this->testResult = __('admin.s.sumopod_key_required');

            return;
        }

        try {
            $response = Http::withHeaders(['Authorization' => 'Bearer '.$key])
                ->timeout(20)
                ->get($base.'/v1/models');

            if ($response->successful()) {
                $count = count($response->json('data') ?? []);
                $this->testOk = true;
                $this->testResult = __('admin.s.sumopod_test_ok', ['count' => $count]);
            } else {
                $this->testOk = false;
                $this->testResult = __('admin.s.sumopod_test_failed', ['status' => $response->status()]);
            }
        } catch (\Throwable $e) {
            $this->testOk = false;
            $this->testResult = __('admin.s.sumopod_test_failed', ['status' => '—']);
        }
    }

    public function render()
    {
        return view('livewire.admin.ai-provider-settings');
    }
}
