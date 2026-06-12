<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLanguagePreferenceRequest;
use App\Services\LocalePreference;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class LanguagePreferenceController extends Controller
{
    public function create(LocalePreference $localePreference): Response|RedirectResponse
    {
        if ($localePreference->get() !== null) {
            return to_route('sites.index');
        }

        return Inertia::render('LanguagePreference');
    }

    public function store(
        StoreLanguagePreferenceRequest $request,
        LocalePreference $localePreference,
    ): RedirectResponse {
        $localePreference->set($request->validated('locale'));

        return to_route('sites.index');
    }

    public function update(
        StoreLanguagePreferenceRequest $request,
        LocalePreference $localePreference,
    ): RedirectResponse {
        $localePreference->set($request->validated('locale'));

        return back();
    }
}
