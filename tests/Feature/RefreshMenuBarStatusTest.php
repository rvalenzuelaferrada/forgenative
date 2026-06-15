<?php

use App\DeploymentHealth;
use App\Jobs\RefreshMenuBarStatus;
use App\Models\ForgeCredential;
use App\Services\ForgeClientFactory;
use App\Services\ForgeDeploymentMonitor;
use App\Services\ForgeTokenVault;
use App\Services\LocalePreference;
use App\Services\MenuBarStatusIndicator;
use Illuminate\Support\Facades\Storage;
use Laravel\Forge\CursorPaginator;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Deployment;
use Laravel\Forge\Resources\Organization;
use Laravel\Forge\Resources\Server;
use Laravel\Forge\Resources\Site;

beforeEach(function () {
    Storage::fake('local');
    app(LocalePreference::class)->set('en');
});

test('the refresh job updates the menu bar with the aggregate health', function () {
    $monitor = Mockery::mock(ForgeDeploymentMonitor::class);
    $monitor->shouldReceive('scan')
        ->once()
        ->andReturn(DeploymentHealth::Failed);

    $indicator = Mockery::mock(MenuBarStatusIndicator::class);
    $indicator->shouldReceive('update')
        ->once()
        ->with(DeploymentHealth::Failed);

    (new RefreshMenuBarStatus)->handle($monitor, $indicator);
});

test('an incomplete healthy scan preserves the previous menu bar state', function () {
    $monitor = Mockery::mock(ForgeDeploymentMonitor::class);
    $monitor->shouldReceive('scan')
        ->once()
        ->andReturnNull();

    $indicator = Mockery::mock(MenuBarStatusIndicator::class);
    $indicator->shouldNotReceive('update');

    (new RefreshMenuBarStatus)->handle($monitor, $indicator);
});

test('the deployment status can be refreshed manually', function () {
    $monitor = Mockery::mock(ForgeDeploymentMonitor::class);
    $monitor->shouldReceive('scan')
        ->once()
        ->andReturn(DeploymentHealth::Deploying);

    $indicator = Mockery::mock(MenuBarStatusIndicator::class);
    $indicator->shouldReceive('update')
        ->once()
        ->with(DeploymentHealth::Deploying);

    app()->instance(ForgeDeploymentMonitor::class, $monitor);
    app()->instance(MenuBarStatusIndicator::class, $indicator);

    $this->from(route('sites.index'))
        ->post(route('deployment-status.refresh'))
        ->assertRedirect(route('sites.index'))
        ->assertSessionHas('status', 'Deployment status refreshed.');
});

test('the status indicator stores the latest known state', function () {
    $indicator = app(MenuBarStatusIndicator::class);

    expect($indicator->current())->toBe(DeploymentHealth::Healthy);

    $indicator->update(DeploymentHealth::Deploying);

    expect($indicator->current())->toBe(DeploymentHealth::Deploying)
        ->and(Storage::disk('local')->get('preferences/menu-bar-status'))
        ->toBe('deploying');
});

test('only the healthy menu bar icon uses a macOS template image', function () {
    $indicator = app(MenuBarStatusIndicator::class);

    expect($indicator->iconPath(DeploymentHealth::Healthy))
        ->toEndWith('menu-bar/status-healthyTemplate.png')
        ->and($indicator->iconPath(DeploymentHealth::Deploying))
        ->toEndWith('menu-bar/status-deploying.png')
        ->and($indicator->iconPath(DeploymentHealth::Failed))
        ->toEndWith('menu-bar/status-failed.png');
});

test('a failed deployment takes priority over an active deployment', function () {
    ForgeCredential::factory()->create();

    $vault = Mockery::mock(ForgeTokenVault::class);
    $vault->shouldReceive('decrypt')
        ->once()
        ->andReturn('token');

    $organizationPage = Mockery::mock(CursorPaginator::class);
    $organizationPage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Organization([
                'slug' => 'example',
            ]),
        ]);
    $serverPage = Mockery::mock(CursorPaginator::class);
    $serverPage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Server([
                'id' => 10,
            ]),
        ]);
    $sitePage = Mockery::mock(CursorPaginator::class);
    $sitePage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Site([
                'id' => 1,
                'name' => 'deploying.example.com',
                'deployment_status' => 'deploying',
            ]),
            new Site([
                'id' => 2,
                'name' => 'failed.example.com',
                'deployment_status' => null,
                'status' => 'installed',
            ]),
        ]);

    $deployingDeploymentPage = Mockery::mock(CursorPaginator::class);
    $deployingDeploymentPage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Deployment(['status' => 'deploying']),
        ]);

    $failedDeploymentPage = Mockery::mock(CursorPaginator::class);
    $failedDeploymentPage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Deployment(['status' => 'failed']),
        ]);

    $forge = Mockery::mock(Forge::class);
    $forge->shouldReceive('organizations')
        ->once()
        ->with(['page' => ['size' => 100]])
        ->andReturn($organizationPage);
    $forge->shouldReceive('servers')
        ->once()
        ->with('example', ['page' => ['size' => 100]])
        ->andReturn($serverPage);
    $forge->shouldReceive('serverSites')
        ->once()
        ->with('example', 10, ['page' => ['size' => 100]])
        ->andReturn($sitePage);
    $forge->shouldReceive('deployments')
        ->once()
        ->with('example', 10, 1, [
            'page' => ['size' => 1],
            'sort' => '-created_at',
        ])
        ->andReturn($deployingDeploymentPage);
    $forge->shouldReceive('deployments')
        ->once()
        ->with('example', 10, 2, [
            'page' => ['size' => 1],
            'sort' => '-created_at',
        ])
        ->andReturn($failedDeploymentPage);

    $clients = Mockery::mock(ForgeClientFactory::class);
    $clients->shouldReceive('make')
        ->once()
        ->andReturn($forge);

    $monitor = new ForgeDeploymentMonitor($clients, $vault);

    expect($monitor->scan())->toBe(DeploymentHealth::Failed);
});

test('an unavailable connection does not replace the latest healthy state', function () {
    ForgeCredential::factory()->create();

    $vault = Mockery::mock(ForgeTokenVault::class);
    $vault->shouldReceive('decrypt')
        ->once()
        ->andThrow(new RuntimeException('Secure storage unavailable.'));

    $clients = Mockery::mock(ForgeClientFactory::class);
    $clients->shouldNotReceive('make');

    $monitor = new ForgeDeploymentMonitor($clients, $vault);

    expect($monitor->scan())->toBeNull();
});
