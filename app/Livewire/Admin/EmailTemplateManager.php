<?php

namespace App\Livewire\Admin;

use App\Mail\CampaignEmail;
use App\Models\EmailTemplate;
use App\Services\Email\EmailSender;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Email Center tab 4: CRUD admin-authored email templates with token
 * preview and test send. Bodies are stored as plain HTML - the {{token}}
 * replacement never compiles stored content as Blade.
 */
class EmailTemplateManager extends Component
{
    use WithPagination;

    public $search = '';

    public $showModal = false;
    public $isEditing = false;
    public $editingId = null;

    public $name = '';
    public $subject = '';
    public $body = '';
    public $category = 'general';

    public $previewShow = false;
    public $previewHtml = '';

    protected $queryString = [
        'search' => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function resetForm(): void
    {
        $this->reset(['showModal', 'isEditing', 'editingId', 'name', 'subject', 'body', 'previewShow', 'previewHtml']);
        $this->category = 'general';
        $this->resetValidation();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $template = EmailTemplate::find($id);

        if (! $template) {
            session()->flash('error', __('admin.s.template_not_found'));
            return;
        }

        $this->resetForm();
        $this->isEditing = true;
        $this->editingId = $template->id;
        $this->name = $template->name;
        $this->subject = $template->subject;
        $this->body = $template->body;
        $this->category = $template->category;
        $this->showModal = true;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'subject' => ['required', 'string', 'max:191'],
            'body' => ['required', 'string'],
            'category' => ['required', 'in:general,newsletter,announcement'],
        ];
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing) {
            EmailTemplate::whereKey($this->editingId)->update([
                'name' => $this->name,
                'subject' => $this->subject,
                'body' => $this->body,
                'category' => $this->category,
            ]);
            $message = __('admin.s.template_updated');
        } else {
            $slug = Str::slug($this->name);
            $base = $slug ?: 'template';
            $slug = $base;
            $i = 2;
            while (EmailTemplate::where('slug', $slug)->exists()) {
                $slug = $base . '-' . $i++;
            }

            EmailTemplate::create([
                'name' => $this->name,
                'slug' => $slug,
                'subject' => $this->subject,
                'body' => $this->body,
                'category' => $this->category,
                'created_by' => auth()->id(),
            ]);
            $message = __('admin.s.template_created');
        }

        $this->resetForm();
        session()->flash('message', $message);
    }

    public function delete(int $id): void
    {
        $template = EmailTemplate::find($id);

        if (! $template) {
            session()->flash('error', __('admin.s.template_not_found'));
            return;
        }

        $template->delete();
        session()->flash('message', __('admin.s.template_deleted'));
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
            'category' => 'Pratinjau Template',
        ])->render();
        $this->previewShow = true;
    }

    public function closePreview(): void
    {
        $this->previewShow = false;
        $this->previewHtml = '';
    }

    public function sendTest(): void
    {
        $user = auth()->user();

        [$subject, $body] = EmailTemplate::renderTokens($this->subject, $this->body, [
            'name' => $user->name,
            'email' => $user->email,
        ]);

        try {
            EmailSender::send($user->email, new CampaignEmail($subject, $body, 'Email Uji', $this->name), 'test', [
                'template_id' => $this->editingId,
            ]);
            session()->flash('message', __('admin.s.test_email_sent', ['email' => $user->email]));
        } catch (\Throwable $e) {
            session()->flash('error', __('admin.s.test_email_failed', ['error' => $e->getMessage()]));
        }
    }

    public function render()
    {
        $query = EmailTemplate::query()->orderByDesc('id');

        if (trim((string) $this->search) !== '') {
            $search = trim($this->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        return view('livewire.admin.email-template-manager', [
            'templates' => $query->paginate(10),
            'tokens' => EmailTemplate::tokens(),
        ]);
    }
}
