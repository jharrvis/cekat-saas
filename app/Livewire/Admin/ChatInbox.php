<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use App\Models\ChatSession;
use App\Models\ChatMessage;
use Livewire\WithPagination;

class ChatInbox extends Component
{
    use WithPagination;

    public $selectedSession = null;
    public $messages = [];
    public $searchTerm = '';
    public $statusFilter = 'all';

    public function selectSession($sessionId)
    {
        $this->selectedSession = ChatSession::with(['widget', 'messages'])->find($sessionId);
        $this->messages = $this->selectedSession ? $this->selectedSession->messages()->orderBy('created_at', 'asc')->get()->toArray() : [];
    }

    public function closeSession($sessionId)
    {
        $session = ChatSession::find($sessionId);
        if ($session) {
            $session->update([
                'ended_at' => now(),
                'status' => 'ended',
            ]);

            // Dispatch summary job
            \App\Jobs\GenerateChatSummary::dispatch($sessionId);
        }

        if ($this->selectedSession && $this->selectedSession->id === $sessionId) {
            $this->selectedSession = null;
            $this->messages = [];
        }
    }

    public function generateSummary($sessionId)
    {
        \App\Jobs\GenerateChatSummary::dispatchSync($sessionId);

        // Refresh selected session data
        $this->selectedSession = ChatSession::with(['widget', 'messages'])->find($sessionId);
        $this->messages = $this->selectedSession ? $this->selectedSession->messages()->orderBy('created_at', 'asc')->get()->toArray() : [];

        session()->flash('message', 'Summary generated successfully!');
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = ChatSession::with([
            'widget',
            'messages' => function ($q) {
                $q->latest()->limit(1);
            }
        ])
            ->withCount('messages')
            ->latest('updated_at');

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('visitor_uuid', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('visitor_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('visitor_email', 'like', '%' . $this->searchTerm . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        $sessions = $query->paginate(15);

        return view('livewire.admin.chat-inbox', [
            'sessions' => $sessions,
        ])->extends('layouts.dashboard')->section('content');
    }
}
