<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Quick language switch (header switcher). Signed-in users get the choice
 * persisted to users.locale; guests get it in the session — SetLocale
 * already honors both, so the very next page load renders in the new
 * language.
 */
class LocaleController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', Rule::in(SetLocale::availableLocales())],
        ]);

        $locale = $validated['locale'];

        if ($user = $request->user()) {
            $user->forceFill(['locale' => $locale])->save();
        }

        $request->session()->put('locale', $locale);

        return back();
    }
}
