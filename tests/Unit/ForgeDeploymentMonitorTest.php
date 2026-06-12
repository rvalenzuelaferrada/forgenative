<?php

use App\DeploymentHealth;

test('forge deployment statuses map to menu bar health', function (
    ?string $deploymentStatus,
    ?string $siteStatus,
    DeploymentHealth $expected,
) {
    expect(DeploymentHealth::fromForgeStatuses(
        $deploymentStatus,
        $siteStatus,
    ))->toBe($expected);
})->with([
    'finished' => ['finished', 'deployed', DeploymentHealth::Healthy],
    'cancelled' => ['cancelled', 'deployed', DeploymentHealth::Healthy],
    'deploying' => ['deploying', 'deployed', DeploymentHealth::Deploying],
    'pending' => ['pending', 'deployed', DeploymentHealth::Deploying],
    'queued' => ['queued', 'deployed', DeploymentHealth::Deploying],
    'failed' => ['failed', 'deployed', DeploymentHealth::Failed],
    'failed build' => ['failed-build', 'deployed', DeploymentHealth::Failed],
    'site deploying fallback' => [null, 'deploying', DeploymentHealth::Deploying],
    'site failed fallback' => [null, 'failed', DeploymentHealth::Failed],
]);
