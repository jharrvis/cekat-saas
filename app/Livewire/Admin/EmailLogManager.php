<?php

namespace App\Livewire\Admin;

use App\Models\EmailLog;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Email Center tab 1: monitor every outbound email in the app
 * (notifications, OTP, announcements...) with filters and a detail preview.
 */
class EmailLogManager extends Component
{
    use WithPagination;

    public $search = '';
    public $category = '';
    public $status = '';
    public $campaignId = null;

    public $showDetail = false;
    public $detailId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'category' => ['except' => ''],
        'status' => ['except' => ''],
        'campaignId' => ['except' => '', 'as' => 'campaign'],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCampaignId(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['search', 'category', 'status', 'campaignId']);
        $this->resetPage();
    }

    public function openDetail(int $id): void
    {
        $this->detailId = $id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->detailId = null;
    }

    public function render()
    {
        $query = EmailLog::query()->orderByDesc('id');

        if (trim((string) $this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('recipient', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        if ($this->category !== '') {
            $query->where('category', $this->category);
        }

        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        if ($this->campaignId) {
            $query->where('campaign_id', (int) $this->campaignId);
        }

        $today = now()->toDateString();
        $base = EmailLog::query();
        $stats = [
            'today' => (clone $base)->whereDate('created_at', $today)->count(),
            'sentToday' => (clone $base)->whereDate('created_at', $today)->where('status', 'sent')->count(),
            'failedToday' => (clone $base)->whereDate('created_at', $today)->where('status', 'failed')->count(),
            'total' => (clone $base)->count(),
        ];

        return view('livewire.admin.email-log-manager', [
            'logs' => $query->paginate(20),
            'categories' => EmailLog::CATEGORIES,
            'stats' => $stats,
            'detail' => $this->detailId ? EmailLog::find($this->detailId) : null,
        ]);
    }
}
