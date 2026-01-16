<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Models\Plan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use App\Mail\AccountSuspended;

class UserManager extends Component
{
    use WithPagination;

    public $search = '';
    public $roleFilter = '';
    public $planFilter = '';
    public $statusFilter = '';

    // Modal states
    public $showModal = false;
    public $showDetailModal = false;
    public $isEditing = false;

    // Form data
    public $userId = null;
    public $name = '';
    public $email = '';
    public $password = '';
    public $role = 'user';
    public $planId = null;
    public $status = 'active';

    // Suspend modal
    public $showSuspendModal = false;
    public $suspendReason = '';
    public $suspendUserId = null;

    // Selected user for detail
    public $selectedUser = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'roleFilter' => ['except' => ''],
        'planFilter' => ['except' => ''],
        'statusFilter' => ['except' => ''],
    ];

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $this->userId,
            'password' => $this->isEditing ? 'nullable|min:8' : 'required|min:8',
            'role' => 'required|in:user,admin',
            'planId' => 'nullable|exists:plans,id',
            'status' => 'required|in:active,suspended,banned',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->roleFilter = '';
        $this->planFilter = '';
        $this->statusFilter = '';
        $this->resetPage();
    }

    public function openModal($userId = null)
    {
        $this->resetValidation();

        if ($userId) {
            $user = User::find($userId);
            $this->userId = $user->id;
            $this->name = $user->name;
            $this->email = $user->email;
            $this->password = '';
            $this->role = $user->role;
            $this->planId = $user->plan_id;
            $this->status = $user->status ?? 'active';
            $this->isEditing = true;
        } else {
            $this->userId = null;
            $this->name = '';
            $this->email = '';
            $this->password = '';
            $this->role = 'user';
            $this->planId = null;
            $this->status = 'active';
            $this->isEditing = false;
        }

        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'plan_id' => $this->planId,
            'status' => $this->status,
        ];

        if ($this->password) {
            $data['password'] = Hash::make($this->password);
        }

        if ($this->isEditing) {
            User::find($this->userId)->update($data);
            session()->flash('message', 'User berhasil diupdate.');
        } else {
            User::create($data);
            session()->flash('message', 'User berhasil ditambahkan.');
        }

        $this->closeModal();
    }

    public function viewDetail($userId)
    {
        $this->selectedUser = User::with([
            'plan',
            'widgets',
            'transactions' => function ($q) {
                $q->latest()->take(5);
            }
        ])->find($userId);
        $this->showDetailModal = true;
    }

    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedUser = null;
    }

    public function confirmDelete($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        // Prevent deleting self or last admin
        if ($user->id === auth()->id()) {
            session()->flash('error', 'Tidak bisa menghapus akun sendiri.');
            return;
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            session()->flash('error', 'Tidak bisa menghapus admin terakhir.');
            return;
        }

        $user->delete();
        session()->flash('message', 'User berhasil dihapus.');
    }

    public function openSuspendModal($userId)
    {
        $this->suspendUserId = $userId;
        $this->suspendReason = '';
        $this->showSuspendModal = true;
    }

    public function closeSuspendModal()
    {
        $this->showSuspendModal = false;
        $this->suspendUserId = null;
        $this->suspendReason = '';
    }

    public function suspendUser()
    {
        $user = User::find($this->suspendUserId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        $user->update([
            'status' => 'suspended',
            'suspended_at' => now(),
            'suspended_reason' => $this->suspendReason,
        ]);

        // Send suspended email notification
        try {
            Mail::to($user->email)->send(new AccountSuspended($user, 'suspended', $this->suspendReason));
        } catch (\Exception $e) {
            \Log::error('Failed to send suspend email', ['error' => $e->getMessage()]);
        }

        $this->closeSuspendModal();
        session()->flash('message', 'User berhasil di-suspend.');
    }

    public function banUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        $user->update([
            'status' => 'banned',
            'suspended_at' => now(),
        ]);

        // Send banned email notification
        try {
            Mail::to($user->email)->send(new AccountSuspended($user, 'banned'));
        } catch (\Exception $e) {
            \Log::error('Failed to send ban email', ['error' => $e->getMessage()]);
        }

        session()->flash('message', 'User berhasil di-banned.');
    }

    public function activateUser($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        $user->update([
            'status' => 'active',
            'suspended_at' => null,
            'suspended_reason' => null,
        ]);

        session()->flash('message', 'User berhasil diaktifkan kembali.');
    }

    public function resetQuota($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        $user->update(['monthly_message_used' => 0]);
        session()->flash('message', 'Kuota pesan berhasil direset.');
    }

    public function sendPasswordReset($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        Password::sendResetLink(['email' => $user->email]);
        session()->flash('message', 'Link reset password telah dikirim ke ' . $user->email);
    }

    public function changePlan($userId, $planId)
    {
        $user = User::find($userId);

        if (!$user) {
            session()->flash('error', 'User tidak ditemukan.');
            return;
        }

        $user->update([
            'plan_id' => $planId ?: null,
            'monthly_message_used' => 0,
        ]);

        session()->flash('message', 'Plan user berhasil diubah.');
    }

    public function export()
    {
        $users = $this->getFilteredUsers()->get();

        $filename = 'users_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($users) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['ID', 'Name', 'Email', 'Role', 'Plan', 'Status', 'Messages Used', 'Joined']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->plan->name ?? '-',
                    $user->status ?? 'active',
                    $user->monthly_message_used ?? 0,
                    $user->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getFilteredUsers()
    {
        return User::with('plan')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->roleFilter, function ($query) {
                $query->where('role', $this->roleFilter);
            })
            ->when($this->planFilter, function ($query) {
                $query->where('plan_id', $this->planFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->orderBy('created_at', 'desc');
    }

    public function render()
    {
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->orWhereNull('status')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'admins' => User::where('role', 'admin')->count(),
            'thisMonth' => User::whereMonth('created_at', now()->month)->count(),
        ];

        $plans = Plan::where('is_active', true)->get();
        $users = $this->getFilteredUsers()->paginate(20);

        return view('livewire.admin.user-manager', [
            'users' => $users,
            'plans' => $plans,
            'stats' => $stats,
        ]);
    }
}
