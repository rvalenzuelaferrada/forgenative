<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreForgeCredentialRequest;
use App\Models\ForgeCredential;
use App\Services\ForgeTokenValidator;
use App\Services\ForgeTokenVault;
use App\Services\LocalePreference;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class ForgeCredentialController extends Controller
{
    public function index(
        Request $request,
        LocalePreference $localePreference,
    ): Response {
        $activeConnectionId = $request->integer('connection') ?: null;

        return Inertia::render('Welcome', [
            'copy' => trans('forge.interface'),
            'currentLocale' => $localePreference->get(),
            'activeConnectionId' => ForgeCredential::query()
                ->whereKey($activeConnectionId)
                ->value('id'),
            'credentials' => ForgeCredential::query()
                ->latest()
                ->get([
                    'id',
                    'name',
                    'forge_email',
                    'last_verified_at',
                ]),
        ]);
    }

    public function store(
        StoreForgeCredentialRequest $request,
        ForgeTokenValidator $validator,
        ForgeTokenVault $vault,
    ): RedirectResponse {
        $validated = $request->validated();
        $fingerprint = hash('sha256', $validated['token']);

        if (ForgeCredential::query()->where('token_fingerprint', $fingerprint)->exists()) {
            throw ValidationException::withMessages([
                'token' => trans('forge.validation.token_duplicate'),
            ]);
        }

        try {
            $forgeUser = $validator->validate($validated['token']);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'token' => trans('forge.validation.token_invalid'),
            ]);
        }

        try {
            $encryptedToken = $vault->encrypt($validated['token']);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'token' => trans('forge.validation.token_storage_failed'),
            ]);
        }

        $credential = ForgeCredential::query()->create([
            'name' => $validated['name'],
            'encrypted_token' => $encryptedToken,
            'token_fingerprint' => $fingerprint,
            'forge_user_id' => $forgeUser['id'],
            'forge_email' => $forgeUser['email'],
            'last_verified_at' => now(),
        ]);

        return to_route('sites.index', [
            'connection' => $credential->id,
        ])
            ->with('status', trans('forge.status.created'));
    }
}
