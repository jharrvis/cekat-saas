<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;

/**
 * Dashboard UI for personal API keys (create / list / revoke).
 * The plan gate lives in route middleware (plan.feature:api_access);
 * this controller re-checks on write to keep the rule in one place.
 */
class ApiKeyController extends Controller
{
    public function index(Request $request)
    {
        $keys = $request->user()->apiKeys()->orderByDesc('id')->get();

        return view('user.api-keys', [
            'keys' => $keys,
            // Shown exactly once right after creation.
            'plainKey' => session('plain_key'),
            'newKeyName' => session('new_key_name'),
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        abort_unless($user->canUseApi(), 403, 'API access tidak tersedia pada paket Anda.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:60'],
        ]);

        [, $secret] = ApiKey::generate($user, $data['name']);

        return redirect()
            ->route('api-keys.index')
            ->with('plain_key', $secret)
            ->with('new_key_name', $data['name'])
            ->with('success', __('api.s.key_created'));
    }

    public function destroy(Request $request, ApiKey $key)
    {
        abort_unless($key->user_id === $request->user()->id, 404);

        $key->forceFill(['revoked_at' => now()])->save();

        return back()->with('success', __('api.s.key_revoked', ['name' => $key->name]));
    }
}
