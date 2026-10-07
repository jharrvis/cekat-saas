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
            return redirect()->route('agents.index')
                ->with('plan_limit_error', $this->limits->limitMessage($user, 'total_agents'));
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

        // T-07: the test panel's empty state offers a one-click attach when
        // the owner has a widget that is not linked to any agent yet.
        $unlinkedWidget = auth()->user()->widgets()
            ->whereNull('ai_agent_id')
            ->orderBy('id')
            ->first();

        return view('agents.edit', compact('agent', 'unlinkedWidget'));
    }

    /**
     * T-07: attach the owner's first unlinked widget (usually the default
     * widget created at registration) to this agent so the test panel and
     * the widget go live in one click.
     */
    public function attachDefaultWidget(AiAgent $agent)
    {
        Gate::authorize('update', $agent);

        $widget = auth()->user()->widgets()
            ->whereNull('ai_agent_id')
            ->orderBy('id')
            ->first();

        if (! $widget) {
            return redirect()
                ->route('agents.edit', $agent)
                ->with('error', __('general.s.no_unlinked_widget'));
        }

        $widget->update(['ai_agent_id' => $agent->id]);

        return redirect()
            ->route('agents.edit', $agent)
            ->with('success', __('general.s.widget_linked', ['name' => $widget->display_name ?? $widget->name]));
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
            return back()->with('error', __('agents.delete_blocked_in_use'));
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
