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

test('a failed deployment takes priority over an active deployment', function () {
    ForgeCredential::factory()->count(2)->create();

    $vault = Mockery::mock(ForgeTokenVault::class);
    $vault->shouldReceive('decrypt')
        ->twice()
        ->andReturn('first-token', 'second-token');

    $deployingPage = Mockery::mock(CursorPaginator::class);
    $deployingPage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Site([
                'id' => 1,
                'name' => 'deploying.example.com',
                'deployment_status' => 'deploying',
            ]),
        ]);
    $deployingPage->shouldReceive('hasMorePages')
        ->once()
        ->andReturnFalse();

    $failedPage = Mockery::mock(CursorPaginator::class);
    $failedPage->shouldReceive('items')
        ->once()
        ->andReturn([
            new Site([
                'id' => 2,
                'name' => 'failed.example.com',
                'deployment_status' => 'failed',
            ]),
        ]);

    $deployingForge = Mockery::mock(Forge::class);
    $deployingForge->shouldReceive('sites')
        ->once()
        ->with(['page' => ['size' => 100]])
        ->andReturn($deployingPage);

    $failedForge = Mockery::mock(Forge::class);
    $failedForge->shouldReceive('sites')
        ->once()
        ->with(['page' => ['size' => 100]])
        ->andReturn($failedPage);

    $clients = Mockery::mock(ForgeClientFactory::class);
    $clients->shouldReceive('make')
        ->twice()
        ->andReturn($deployingForge, $failedForge);

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
