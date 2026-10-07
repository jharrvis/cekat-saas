<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAiAgentRequest;
use App\Http\Requests\UpdateAiAgentRequest;
use App\Models\AiAgent;
use App\Services\Billing\PlanLimitService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AiAgentController extends Controller
{
    private PlanLimitService $limits;

    public function __construct()
    {
        $this->limits = app(PlanLimitService::class);
    }

    /**
     * Display a listing of the user's AI agents.
     */
    public function index()
    {
        $agents = Auth::user()->aiAgents()
            ->withCount('widgets')
            ->with(['knowledgeBase' => fn ($q) => $q->withCount(['faqs', 'documents'])])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('agents.index', compact('agents'));
    }

    /**
     * Show the form for creating a new agent.
     */
    public function create()
    {
        return view('agents.create');
    }

    /**
     * Store a newly created agent in storage.
     */
    public function store(StoreAiAgentRequest $request)
    {
        // Plan limit: an account may not own more agents than its plan allows (T-02).
        $user = Auth::user();
        $check = $this->limits->check($user, 'total_agents');
        if (! $check['allowed']) {
            $plan = $this->limits->planFor($user);

            return redirect()->route('agents.index')
                ->with('error', "Paket {$plan->name} Anda terbatas {$check['limit']} agen. Tingkatkan paket untuk menambah.");
        }

        $agent = Auth::user()->aiAgents()->create($request->validated());

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
        Gate::authorize('view', $agent);

        $agent->load(['widgets', 'knowledgeBase.faqs']);

        return view('agents.edit', compact('agent'));
    }

    /**
     * Show the knowledge base editor for the agent.
     */
    public function knowledge(AiAgent $agent)
    {
        Gate::authorize('view', $agent);

        return view('agents.knowledge', compact('agent'));
    }

    /**
     * Update the specified agent in storage.
     */
    public function update(UpdateAiAgentRequest $request, AiAgent $agent)
    {
        $agent->update($request->validated());

        return back()->with('message', 'AI Agent berhasil diupdate!');
    }

    /**
     * Remove the specified agent from storage.
     */
    public function destroy(AiAgent $agent)
    {
        Gate::authorize('delete', $agent);

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
        Gate::authorize('update', $agent);

        $agent->update(['is_active' => !$agent->is_active]);

        $status = $agent->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('message', "AI Agent berhasil {$status}!");
    }
}
