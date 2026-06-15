<?php

namespace App;

enum DeploymentHealth: string
{
    case Healthy = 'healthy';
    case Deploying = 'deploying';
    case Failed = 'failed';

    public static function fromForgeStatuses(
        ?string $deploymentStatus,
        ?string $siteStatus = null,
        ?string $latestDeploymentStatus = null,
    ): self {
        $statuses = array_filter([
            strtolower((string) $deploymentStatus),
            strtolower((string) $siteStatus),
            strtolower((string) $latestDeploymentStatus),
        ]);

        if (array_intersect($statuses, ['failed', 'failed-build'])) {
            return self::Failed;
        }

        if (array_intersect($statuses, ['deploying', 'pending', 'queued'])) {
            return self::Deploying;
        }

        return self::Healthy;
    }
}
