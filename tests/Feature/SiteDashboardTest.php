<?php

use App\Models\ForgeCredential;
use App\Services\ForgeClientFactory;
use App\Services\ForgeOverview;
use App\Services\ForgeTokenVault;
use App\Services\LocalePreference;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Forge\CursorPaginator;
use Laravel\Forge\Exceptions\ForbiddenException;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Organization;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;

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
                    'deployment_health' => 'failed',
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
            ->where('overview.sites.0.deployment_health', 'failed')
            ->missing('connections.0.encrypted_token')
            ->missing('connections.0.token_fingerprint'));
});

test('a site still renders when the token cannot read its deployments', function () {
    $credential = ForgeCredential::factory()->create([
        'encrypted_token' => 'native:encrypted-secret',
    ]);

    $page = function (array $items): CursorPaginator {
        $paginator = Mockery::mock(CursorPaginator::class);
        $paginator->shouldReceive('items')->andReturn($items);
        $paginator->shouldReceive('hasMorePages')->andReturn(false);

        return $paginator;
    };

    $forge = Mockery::mock(Forge::class);
    $forge->shouldReceive('organizations')
        ->once()
        ->andReturn($page([new Organization(['slug' => 'acme', 'name' => 'Acme'])]));
    $forge->shouldReceive('servers')
        ->once()
        ->with('acme', Mockery::type('array'))
        ->andReturn($page([new Server(['id' => 1])]));
    $forge->shouldReceive('serverSites')
        ->once()
        ->with('acme', 1, Mockery::type('array'))
        ->andReturn($page([new Site([
            'id' => 10,
            'name' => 'example.com',
            'status' => 'installed',
            'deployment_status' => 'finished',
            'php_version' => '8.4',
            'organization_slug' => 'acme',
            'url' => 'https://example.com',
        ])]));
    $forge->shouldReceive('deployments')
        ->once()
        ->with('acme', 1, 10, Mockery::type('array'))
        ->andThrow(new ForbiddenException('Forbidden'));

    $factory = Mockery::mock(ForgeClientFactory::class);
    $factory->shouldReceive('make')->andReturn($forge);

    $vault = Mockery::mock(ForgeTokenVault::class);
    $vault->shouldReceive('decrypt')->andReturn('plain-token');

    $overview = new ForgeOverview($factory, $vault);

    $result = $overview->load($credential->id, 'acme');

    expect($result['error'])->toBeNull()
        ->and($result['capabilities']['sites'])->toBeTrue()
        ->and($result['sites'])->toHaveCount(1)
        ->and($result['sites'][0]['name'])->toBe('example.com')
        ->and($result['sites'][0]['deployment_status'])->toBe('finished');
});

test('the onboarding is shown when no connections exist', function () {
    $this->get(route('sites.index'))
        ->assertSuccessful()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome')
            ->has('credentials', 0));
});
