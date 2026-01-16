<?php

namespace App\Livewire\Admin;

use App\Models\Transaction;
use App\Models\Plan;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;

class TransactionMonitor extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $planFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    public $selectedTransaction = null;
    public $showDetailModal = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'planFilter' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function updatingPlanFilter()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->search = '';
        $this->statusFilter = '';
        $this->planFilter = '';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function viewDetail($transactionId)
    {
        $this->selectedTransaction = Transaction::with(['user', 'plan'])->find($transactionId);
        $this->showDetailModal = true;
    }

    public function closeDetail()
    {
        $this->showDetailModal = false;
        $this->selectedTransaction = null;
    }

    public function refreshStatus($transactionId)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            session()->flash('error', 'Transaction not found');
            return;
        }

        try {
            \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
            \Midtrans\Config::$isProduction = config('services.midtrans.is_production');

            $status = \Midtrans\Transaction::status($transaction->order_id);

            $transactionStatus = $status->transaction_status ?? null;
            $fraudStatus = $status->fraud_status ?? null;
            $paymentType = $status->payment_type ?? null;

            $newStatus = $transaction->status;

            if ($transactionStatus == 'capture' || $transactionStatus == 'settlement') {
                if ($fraudStatus == 'accept' || $fraudStatus === null) {
                    $newStatus = 'success';
                } elseif ($fraudStatus == 'challenge') {
                    $newStatus = 'challenge';
                }
            } elseif ($transactionStatus == 'pending') {
                $newStatus = 'pending';
            } elseif (in_array($transactionStatus, ['deny', 'cancel'])) {
                $newStatus = 'failed';
            } elseif ($transactionStatus == 'expire') {
                $newStatus = 'expired';
            }

            $transaction->update([
                'status' => $newStatus,
                'payment_type' => $paymentType ?? $transaction->payment_type,
                'midtrans_response' => (array) $status,
            ]);

            // If success, activate plan
            if ($newStatus === 'success' && $transaction->status !== 'success') {
                $this->activatePlan($transaction);
            }

            session()->flash('message', 'Status updated: ' . $newStatus);

        } catch (\Exception $e) {
            // Handle 404 - transaction doesn't exist in Midtrans (user didn't complete payment)
            if (str_contains($e->getMessage(), '404') || str_contains($e->getMessage(), "doesn't exist")) {
                session()->flash('error', 'Transaksi belum diproses di Midtrans. User mungkin belum menyelesaikan pembayaran.');
            } else {
                session()->flash('error', 'Failed to refresh: ' . $e->getMessage());
            }
        }
    }

    public function markAsSuccess($transactionId)
    {
        $transaction = Transaction::find($transactionId);

        if (!$transaction) {
            session()->flash('error', 'Transaction not found');
            return;
        }

        $transaction->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        $this->activatePlan($transaction);

        session()->flash('message', 'Transaction marked as success and plan activated.');
    }

    private function activatePlan(Transaction $transaction)
    {
        $user = $transaction->user;
        $plan = $transaction->plan;

        if ($user && $plan) {
            $user->update([
                'plan_id' => $plan->id,
                'plan_expires_at' => now()->addMonth(),
                'monthly_message_used' => 0,
            ]);

            $transaction->update(['paid_at' => now()]);
        }
    }

    public function export()
    {
        $transactions = $this->getFilteredTransactions()->get();

        $filename = 'transactions_' . date('Y-m-d_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($transactions) {
            $file = fopen('php://output', 'w');

            // Header
            fputcsv($file, ['Order ID', 'User', 'Email', 'Plan', 'Amount', 'Payment Type', 'Status', 'Created', 'Paid At']);

            foreach ($transactions as $tx) {
                fputcsv($file, [
                    $tx->order_id,
                    $tx->user->name ?? '-',
                    $tx->user->email ?? '-',
                    $tx->plan->name ?? '-',
                    $tx->amount,
                    $tx->payment_type ?? '-',
                    $tx->status,
                    $tx->created_at->format('Y-m-d H:i:s'),
                    $tx->paid_at?->format('Y-m-d H:i:s') ?? '-',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function getFilteredTransactions()
    {
        return Transaction::with(['user', 'plan'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_id', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($uq) {
                            $uq->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->planFilter, function ($query) {
                $query->where('plan_id', $this->planFilter);
            })
            ->when($this->dateFrom, function ($query) {
                $query->whereDate('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function ($query) {
                $query->whereDate('created_at', '<=', $this->dateTo);
            })
            ->orderBy('created_at', 'desc');
    }

    public function render()
    {
        // Stats
        $todayRevenue = Transaction::where('status', 'success')
            ->whereDate('created_at', today())
            ->sum('amount');

        $monthRevenue = Transaction::where('status', 'success')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $totalRevenue = Transaction::where('status', 'success')->sum('amount');

        $statusCounts = Transaction::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $plans = Plan::where('is_active', true)->get();

        $transactions = $this->getFilteredTransactions()->paginate(20);

        return view('livewire.admin.transaction-monitor', [
            'transactions' => $transactions,
            'plans' => $plans,
            'todayRevenue' => $todayRevenue,
            'monthRevenue' => $monthRevenue,
            'totalRevenue' => $totalRevenue,
            'statusCounts' => $statusCounts,
        ]);
    }
}
