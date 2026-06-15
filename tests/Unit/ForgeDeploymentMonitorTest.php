<?php

use App\DeploymentHealth;

test('forge deployment statuses map to menu bar health', function (
    ?string $deploymentStatus,
    ?string $siteStatus,
    ?string $latestDeploymentStatus,
    DeploymentHealth $expected,
) {
    expect(DeploymentHealth::fromForgeStatuses(
        $deploymentStatus,
        $siteStatus,
        $latestDeploymentStatus,
    ))->toBe($expected);
})->with([
    'finished' => ['finished', 'deployed', null, DeploymentHealth::Healthy],
    'cancelled' => ['cancelled', 'deployed', null, DeploymentHealth::Healthy],
    'deploying' => ['deploying', 'deployed', null, DeploymentHealth::Deploying],
    'pending' => ['pending', 'deployed', null, DeploymentHealth::Deploying],
    'queued' => ['queued', 'deployed', null, DeploymentHealth::Deploying],
    'failed' => ['failed', 'deployed', null, DeploymentHealth::Failed],
    'failed build' => ['failed-build', 'deployed', null, DeploymentHealth::Failed],
    'site deploying fallback' => [null, 'deploying', null, DeploymentHealth::Deploying],
    'site failed fallback' => [null, 'failed', null, DeploymentHealth::Failed],
    'latest deployment failed' => [null, 'installed', 'failed', DeploymentHealth::Failed],
]);
