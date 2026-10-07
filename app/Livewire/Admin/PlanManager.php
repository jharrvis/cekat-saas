<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\Plan;

class PlanManager extends Component
{
    public $plans = [];
    public $editingPlan = null;
    public $showForm = false;

    // Form fields
    public $plan_id;
    public $name = '';
    public $slug = '';
    public $description = '';
    public $price = 0;
    public $billing_period = 'monthly';
    public $max_widgets = 1;
    public $max_agents = 1;
    public $max_messages_per_month = 100;
    public $max_documents = 3;
    public $max_file_size_mb = 5;
    public $max_faqs = 10;
    public $chat_history_days = 7;
    public $max_whatsapp_devices = 1;
    public $ai_tier = 'basic';
    public $features = [];
    public $is_active = true;
    public $sort_order = 0;

    public $availableFeatures = [
        'leads' => 'Lead Collection',
        'whatsapp' => 'WhatsApp Gateway',
        'custom_branding' => 'Custom Branding',
        'analytics' => 'Analytics',
        'priority_support' => 'Priority Support',
        'api_access' => 'API Access',
        'white_label' => 'White Label',
    ];

    public function mount()
    {
        $this->loadPlans();
    }

    public function loadPlans()
    {
        $this->plans = Plan::orderBy('sort_order')->get()->toArray();
    }

    public function createPlan()
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editPlan($planId)
    {
        $plan = Plan::find($planId);
        $this->plan_id = $plan->id;
        $this->name = $plan->name;
        $this->slug = $plan->slug;
        $this->description = $plan->description;
        $this->price = $plan->price;
        $this->billing_period = $plan->billing_period;
        $this->max_widgets = $plan->max_widgets;
        $this->max_agents = $plan->max_agents ?? 1;
        $this->max_messages_per_month = $plan->max_messages_per_month;
        $this->max_documents = $plan->max_documents;
        $this->max_file_size_mb = $plan->max_file_size_mb;
        $this->max_faqs = $plan->max_faqs;
        $this->chat_history_days = $plan->chat_history_days;
        $this->max_whatsapp_devices = $plan->max_whatsapp_devices ?? 1;
        $this->ai_tier = $plan->ai_tier ?? 'basic';
        $this->features = $plan->features ?? [];
        $this->features['leads'] = (bool) $plan->can_export_leads;
        $this->features['whatsapp'] = (bool) $plan->can_use_whatsapp;
        $this->is_active = $plan->is_active;
        $this->sort_order = $plan->sort_order;
        $this->showForm = true;
    }

    public function savePlan()
    {
        $this->validate([
            'name' => 'required|max:255',
            'slug' => 'required|max:255|unique:plans,slug,' . ($this->plan_id ?? 'NULL'),
            'price' => 'required|numeric|min:0',
            'max_widgets' => 'required|integer|min:1',
            'max_agents' => 'required|integer|min:1',
            'max_messages_per_month' => 'required|integer|min:1',
            'max_documents' => 'required|integer|min:0',
            'max_file_size_mb' => 'required|integer|min:1',
            'max_faqs' => 'required|integer|min:0',
            'chat_history_days' => 'required|integer|min:0',
            'max_whatsapp_devices' => 'required|integer|min:0',
            'features' => 'array',
        ]);

        $features = is_array($this->features) ? $this->features : [];
        $canExportLeads = (bool) ($features['leads'] ?? false);
        $canUseWhatsApp = (bool) ($features['whatsapp'] ?? false);
        unset($features['leads'], $features['whatsapp']);

        $data = [
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'billing_period' => $this->billing_period,
            'max_widgets' => $this->max_widgets,
            'max_agents' => $this->max_agents,
            'max_messages_per_month' => $this->max_messages_per_month,
            'max_documents' => $this->max_documents,
            'max_file_size_mb' => $this->max_file_size_mb,
            'max_faqs' => $this->max_faqs,
            'chat_history_days' => $this->chat_history_days,
            'max_whatsapp_devices' => $this->max_whatsapp_devices,
            'can_export_leads' => $canExportLeads,
            'can_use_whatsapp' => $canUseWhatsApp,
            'ai_tier' => $this->ai_tier,
            'features' => $features,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,
        ];

        if ($this->plan_id) {
            Plan::find($this->plan_id)->update($data);
            session()->flash('message', 'Plan updated successfully!');
        } else {
            Plan::create($data);
            session()->flash('message', 'Plan created successfully!');
        }

        $this->resetForm();
        $this->showForm = false;
        $this->loadPlans();
    }

    public function deletePlan($planId)
    {
        Plan::find($planId)->delete();
        session()->flash('message', 'Plan deleted successfully!');
        $this->loadPlans();
    }

    public function toggleActive($planId)
    {
        $plan = Plan::find($planId);
        $plan->update(['is_active' => !$plan->is_active]);
        $this->loadPlans();
    }

    public function cancelEdit()
    {
        $this->resetForm();
        $this->showForm = false;
    }

    private function resetForm()
    {
        $this->plan_id = null;
        $this->name = '';
        $this->slug = '';
        $this->description = '';
        $this->price = 0;
        $this->billing_period = 'monthly';
        $this->max_widgets = 1;
        $this->max_agents = 1;
        $this->max_messages_per_month = 100;
        $this->max_documents = 3;
        $this->max_file_size_mb = 5;
        $this->max_faqs = 10;
        $this->chat_history_days = 7;
        $this->max_whatsapp_devices = 1;
        $this->ai_tier = 'basic';
        $this->features = [];
        $this->is_active = true;
        $this->sort_order = 0;
    }

    public function updatedName()
    {
        $this->slug = \Str::slug($this->name);
    }

    public function render()
    {
        return view('livewire.admin.plan-manager');
    }
}
