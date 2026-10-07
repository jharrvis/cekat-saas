<?php

namespace App\Livewire\Admin;

use App\Mail\CampaignEmail;
use App\Models\EmailCampaign;
use App\Models\EmailTemplate;
use App\Models\Plan;
use App\Services\Email\CampaignSender;
use App\Services\Email\EmailSender;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Email Center tabs 2 & 3: compose newsletters / announcements, preview
 * the recipient count of a segment, test-send to the admin, then start
 * the chunked send (driven by the campaigns:send scheduler).
 */
class EmailCampaignManager extends Component
{
    use WithPagination;

    /** 'newsletter' | 'announcement' */
    public string $type = 'newsletter';

    public $search = '';

    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    public $name = '';
    public $subject = '';
    public $body = '';

    public $segmentVerified = true;
    public $segmentStatus = 'active';
    public $segmentPlanId = '';
    public $segmentRole = 'user';

    public $previewShow = false;
    public $previewHtml = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function mount(string $type = 'newsletter'): void
    {
        $this->type = in_array($type, EmailCampaign::TYPES, true) ? $type : 'newsletter';
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function typeLabel(): string
    {
        return EmailCampaign::TYPE_LABELS[$this->type] ?? 'Kampanye';
    }

    public function segment(): array
    {
        return [
            'verified' => (bool) $this->segmentVerified,
            'status' => $this->segmentStatus,
            'plan_id' => $this->segmentPlanId !== '' ? (int) $this->segmentPlanId : '',
            'role' => $this->segmentRole,
        ];
    }

    public function resetForm(): void
    {
        $this->reset([
            'showModal', 'isEditing', 'editingId', 'name', 'subject', 'body',
            'previewShow', 'previewHtml',
        ]);
        $this->segmentVerified = true;
        $this->segmentStatus = 'active';
        $this->segmentPlanId = '';
        $this->segmentRole = 'user';
        $this->resetValidation();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $campaign = EmailCampaign::find($id);

        if (! $campaign || $campaign->type !== $this->type) {
            session()->flash('error', __('admin.s.campaign_not_found'));
            return;
        }

        if ($campaign->status !== 'draft') {
            session()->flash('error', __('admin.s.only_drafts_can_be_edited'));
            return;
        }

        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $campaign->id;
        $this->name = $campaign->name;
        $this->subject = $campaign->subject;
        $this->body = $campaign->body;

        $segment = (array) $campaign->segment;
        $this->segmentVerified = (bool) ($segment['verified'] ?? true);
        $this->segmentStatus = $segment['status'] ?? 'active';
        $this->segmentPlanId = $segment['plan_id'] ?? '';
        $this->segmentRole = $segment['role'] ?? 'user';

        $this->showModal = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'segmentStatus' => ['required', 'in:active,all,suspended,banned'],
            'segmentRole' => ['nullable', 'in:,user,admin'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        $payload = [
            'type' => $this->type,
            'name' => $this->name,
            'subject' => $this->subject,
            'body' => $this->body,
            'segment' => $this->segment(),
        ];

        if ($this->isEditing) {
            EmailCampaign::whereKey($this->editingId)->update($payload);
            $message = __('admin.s.draft_updated');
        } else {
            $payload['created_by'] = auth()->id();
            EmailCampaign::create($payload);
            $message = __('admin.s.draft_created');
        }

        $this->resetForm();
        session()->flash('message', $message);
    }

    public function loadTemplate(int $templateId): void
    {
        $template = EmailTemplate::find($templateId);

        if (! $template) {
            return;
        }

        $this->subject = $template->subject;
        $this->body = $template->body;
    }

    public function start(int $id): void
    {
        $campaign = EmailCampaign::find($id);

        if (! $campaign || $campaign->type !== $this->type) {
            session()->flash('error', __('admin.s.campaign_not_found'));
            return;
        }

        if (! in_array($campaign->status, ['draft', 'stopped'], true)) {
            session()->flash('error', __('admin.s.campaign_status_cannot_be_sent'));
            return;
        }

        if (! app(CampaignSender::class)->start($campaign)) {
            session()->flash('error', __('admin.s.segment_matches_no_users'));
            return;
        }

        session()->flash('message', __('admin.s.sending_started', ['type' => $this->typeLabel(), 'count' => $campaign->total_recipients]));
    }

    public function stop(int $id): void
    {
        $campaign = EmailCampaign::find($id);

        if ($campaign && $campaign->status === 'sending') {
            $campaign->update(['status' => 'stopped']);
            session()->flash('message', __('admin.s.sending_stopped'));
        }
    }

    public function delete(int $id): void
    {
        $campaign = EmailCampaign::find($id);

        if ($campaign && $campaign->type === $this->type) {
            $campaign->delete();
            session()->flash('message', __('admin.s.campaign_deleted'));
        }
    }

    public function preview(): void
    {
        [$subject, $body] = EmailTemplate::renderTokens($this->subject, $this->body, [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'plan' => 'Business',
        ]);

        $this->previewHtml = view('emails.campaign', [
            'body' => $body,
            'title' => $subject,
            'category' => $this->typeLabel(),
        ])->render();
        $this->previewShow = true;
    }

    public function closePreview(): void
    {
        $this->previewShow = false;
        $this->previewHtml = '';
    }

    public function sendTest(int $id): void
    {
        $campaign = EmailCampaign::find($id);

        if (! $campaign || $campaign->type !== $this->type) {
            session()->flash('error', __('admin.s.campaign_not_found'));
            return;
        }

        $user = auth()->user();

        [$subject, $body] = EmailTemplate::renderTokens($campaign->subject, $campaign->body, CampaignSender::valuesFor($user));

        try {
            EmailSender::send($user->email, new CampaignEmail($subject, $body, $this->typeLabel(), $campaign->name), 'test', [
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
            ]);
            session()->flash('message', __('admin.s.test_email_sent', ['email' => $user->email]));
        } catch (\Throwable $e) {
            session()->flash('error', __('admin.s.test_email_failed', ['error' => $e->getMessage()]));
        }
    }

    public function render()
    {
        $query = EmailCampaign::where('type', $this->type)->orderByDesc('id');

        if (trim((string) $this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $campaigns = $query->paginate(10);
        $hasSending = EmailCampaign::where('type', $this->type)->where('status', 'sending')->exists();

        return view('livewire.admin.email-campaign-manager', [
            'campaigns' => $campaigns,
            'hasSending' => $hasSending,
            'previewCount' => CampaignSender::resolveSegment($this->segment()),
            'plans' => Plan::orderBy('name')->get(['id', 'name']),
            'templates' => EmailTemplate::where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'typeLabel' => $this->typeLabel(),
        ]);
    }
}
