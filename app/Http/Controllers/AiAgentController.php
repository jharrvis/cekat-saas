<?php

namespace App\Http\Controllers;

use App\Models\AiAgent;
use App\Models\LlmModel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class AiAgentController extends Controller
{
    /**
     * Display a listing of the user's AI agents.
     */
    public function index()
    {
        $agents = Auth::user()->aiAgents()
            ->withCount('widgets')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new agent.
     */
    public function create()
    {
        $llmModels = LlmModel::where('is_active', true)
            ->orderBy('tier')
            ->orderBy('name')
            ->get();

        return view('agents.create', compact('llmModels'));
    }

    /**
     * Store a newly created agent in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ai_model' => 'required|string|max:100',
            'personality' => 'required|in:professional,friendly,casual,formal',
            'ai_temperature' => 'required|numeric|min:0|max:2',
            'greeting_message' => 'nullable|string|max:500',
            'system_prompt' => 'nullable|string|max:2000',
            'fallback_message' => 'nullable|string|max:500',
        ]);

        $agent = Auth::user()->aiAgents()->create($validated);

        // Create knowledge base for the agent
        $agent->knowledgeBase()->create([
            'company_name' => Auth::user()->name,
        ]);

        return redirect()->route('agents.edit', $agent)
            ->with('message', 'AI Agent berhasil dibuat!');
    }

    /**
     * Show the form for editing the specified agent.
     */
    public function edit(AiAgent $agent)
    {
        // Ensure user owns this agent
        if ($agent->user_id !== Auth::id()) {
            abort(403);
        }

        $llmModels = LlmModel::where('is_active', true)
            ->orderBy('tier')
            ->orderBy('name')
            ->get();

        $agent->load(['widgets', 'knowledgeBase.faqs']);

        return view('agents.edit', compact('agent', 'llmModels'));
    }

    /**
     * Update the specified agent in storage.
     */
    public function update(Request $request, AiAgent $agent)
    {
        // Ensure user owns this agent
        if ($agent->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'ai_model' => 'required|string|max:100',
            'personality' => 'required|in:professional,friendly,casual,formal',
            'ai_temperature' => 'required|numeric|min:0|max:2',
            'greeting_message' => 'nullable|string|max:500',
            'system_prompt' => 'nullable|string|max:2000',
            'fallback_message' => 'nullable|string|max:500',
            'is_active' => 'boolean',
        ]);

        $agent->update($validated);

        return back()->with('message', 'AI Agent berhasil diupdate!');
    }

    /**
     * Remove the specified agent from storage.
     */
    public function destroy(AiAgent $agent)
    {
        // Ensure user owns this agent
        if ($agent->user_id !== Auth::id()) {
            abort(403);
        }

        // Check if agent has widgets
        if ($agent->widgets()->count() > 0) {
            return back()->with('error', 'Tidak bisa menghapus agent yang masih digunakan oleh widget!');
        }

        $agent->delete();

        return redirect()->route('agents.index')
            ->with('message', 'AI Agent berhasil dihapus!');
    }

    /**
     * Toggle agent active status.
     */
    public function toggleStatus(AiAgent $agent)
    {
        // Ensure user owns this agent
        if ($agent->user_id !== Auth::id()) {
            abort(403);
        }

        $agent->update(['is_active' => !$agent->is_active]);

        $status = $agent->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('message', "AI Agent berhasil {$status}!");
    }
}
