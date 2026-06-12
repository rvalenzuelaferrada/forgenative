<?php

use App\Models\ForgeCredential;
use App\Services\ForgeOverview;
use App\Services\LocalePreference;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Storage::fake('local');
    app(LocalePreference::class)->set('en');
});

test('the sites dashboard exposes only contextual forge data', function () {
    $credential = ForgeCredential::factory()->create([
        'name' => 'production@example.com',
        'encrypted_token' => 'native:encrypted-secret',
        'token_fingerprint' => hash('sha256', 'plain-secret'),
    ]);

    $overview = Mockery::mock(ForgeOverview::class);
    $overview->shouldReceive('load')
        ->once()
        ->with($credential->id, 'acme')
        ->andReturn([
            'active_connection_id' => $credential->id,
            'active_organization_slug' => 'acme',
            'organizations' => [
                ['slug' => 'acme', 'name' => 'Acme'],
            ],
            'sites' => [
                [
                    'id' => 10,
                    'name' => 'example.com',
                    'url' => 'https://example.com',
                    'status' => 'installed',
                    'deployment_status' => null,
                    'php_version' => '8.4',
                    'organization_slug' => 'acme',
                ],
            ],
            'capabilities' => [
                'organizations' => true,
                'sites' => true,
            ],
            'has_more_sites' => false,
            'error' => null,
        ]);

    app()->instance(ForgeOverview::class, $overview);

    $this->get(route('sites.index', [
        'connection' => $credential->id,
        'organization' => 'acme',
    ]))->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Sites')
            ->where('copy.sites', 'Sites')
            ->has('connections', 1)
            ->where('overview.active_connection_id', $credential->id)
            ->where('overview.active_organization_slug', 'acme')
            ->where('overview.sites.0.name', 'example.com')
            ->missing('connections.0.encrypted_token')
            ->missing('connections.0.token_fingerprint'));
});

test('the onboarding is shown when no connections exist', function () {
    $this->get(route('sites.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('credentials', 0));
});
