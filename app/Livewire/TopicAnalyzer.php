<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\TopicAnalyzerService;
use Illuminate\Support\Facades\Cache;

class TopicAnalyzer extends Component
{
    public $topics = [];
    public $isLoading = false;
    public $lastUpdated = null;
    public $canUseSummarize = false;
    public $isPaidUser = false;

    public function mount()
    {
        $user = auth()->user();
        $plan = $user->plan;

        // Check if user can use AI summarize (paid plans only)
        $this->isPaidUser = $plan && $plan->price > 0;
        $this->canUseSummarize = $this->isPaidUser;

        // Load cached data or word frequency
        $this->loadTopics(false);
    }

    public function loadTopics($forceAI = false)
    {
        $user = auth()->user();
        $widgetIds = $user->widgets()->pluck('id');

        $cacheKey = 'user_topics_' . $user->id;
        $cacheTimeKey = 'user_topics_time_' . $user->id;

        // Check if we have cached data
        if (!$forceAI && Cache::has($cacheKey)) {
            $this->topics = Cache::get($cacheKey);
            $this->lastUpdated = Cache::get($cacheTimeKey);
            return;
        }

        $this->isLoading = true;

        $analyzer = new TopicAnalyzerService();

        if ($forceAI && $this->canUseSummarize) {
            // Force AI analysis for paid users
            $this->topics = $analyzer->analyzeTopics($user, $widgetIds, true);
        } else {
            // Use word frequency for free users or initial load
            $this->topics = $analyzer->analyzeTopicsBasic($user, $widgetIds);
        }

        // Store cache time
        $this->lastUpdated = now();
        Cache::put($cacheTimeKey, $this->lastUpdated, now()->addHours(6));

        $this->isLoading = false;
    }

    public function summarize()
    {
        if (!$this->canUseSummarize) {
            return;
        }

        $this->isLoading = true;
        $this->loadTopics(true);
    }

    public function render()
    {
        return view('livewire.topic-analyzer');
    }
}
