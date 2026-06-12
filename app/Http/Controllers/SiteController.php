<?php

namespace App\Http\Controllers;

use App\Models\ForgeCredential;
use App\Services\ForgeOverview;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SiteController extends Controller
{
    public function index(Request $request, ForgeOverview $overview): Response
    {
        $credentials = ForgeCredential::query()
            ->latest()
            ->get([
                'id',
                'name',
                'forge_email',
                'last_verified_at',
            ]);

        if ($credentials->isEmpty()) {
            return Inertia::render('Welcome', [
                'copy' => trans('forge.interface'),
                'credentials' => [],
            ]);
        }

        return Inertia::render('Sites', [
            'copy' => trans('forge.sites'),
            'connections' => $credentials,
            'overview' => $overview->load(
                $request->integer('connection') ?: null,
                $request->string('organization')->toString() ?: null,
            ),
        ]);
    }
}
