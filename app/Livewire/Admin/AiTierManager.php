<?php

namespace App\Livewire\Admin;

use App\Models\LlmModel;
use App\Models\Setting;
use Livewire\Component;

class AiTierManager extends Component
{
    public $tierMapping = [
        'basic' => '',
        'standard' => '',
        'advanced' => '',
        'premium' => '',
    ];

    public $tiers = [
        'basic' => ['name' => 'Basic', 'description' => 'Free tier - Simple FAQ responses', 'color' => 'gray'],
        'standard' => ['name' => 'Standard', 'description' => 'Starter plan - Natural conversations', 'color' => 'blue'],
        'advanced' => ['name' => 'Advanced', 'description' => 'Pro plan - Complex reasoning', 'color' => 'purple'],
        'premium' => ['name' => 'Premium', 'description' => 'Business plan - Best AI available', 'color' => 'amber'],
    ];

    public function mount()
    {
        $mappingData = Setting::get('ai_tier_mapping');
        if ($mappingData) {
            // Handle both array (if type=json already decoded) and string
            $mapping = is_array($mappingData) ? $mappingData : json_decode($mappingData, true);
            if (is_array($mapping)) {
                $this->tierMapping = array_merge($this->tierMapping, $mapping);
            }
        }
    }

    public function save()
    {
        // Use Setting::set which handles json encoding properly
        Setting::set('ai_tier_mapping', $this->tierMapping, 'json', 'api');

        session()->flash('message', 'AI Tier mapping saved successfully!');
    }

    public function render()
    {
        $models = LlmModel::where('is_active', true)
            ->orderBy('popularity', 'desc')
            ->get();

        return view('livewire.admin.ai-tier-manager', [
            'models' => $models,
        ]);
    }
}
