<?php

namespace App\Services;

use App\DeploymentHealth;
use App\Models\ForgeCredential;
use Laravel\Forge\CursorPaginator;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Deployment;
use Laravel\Forge\Resources\Site;
use Throwable;

class ForgeDeploymentMonitor
{
    public function __construct(
        private ForgeClientFactory $clients,
        private ForgeTokenVault $vault,
    ) {}

    public function scan(): ?DeploymentHealth
    {
        $health = DeploymentHealth::Healthy;
        $scanWasIncomplete = false;

        foreach (
            ForgeCredential::query()
                ->select(['id', 'encrypted_token'])
                ->orderBy('id')
                ->cursor() as $credential
        ) {
            try {
                $forge = $this->clients->make(
                    $this->vault->decrypt($credential->encrypted_token),
                );

                $organizationPage = $forge->organizations([
                    'page' => ['size' => 100],
                ]);

                while ($organizationPage !== null) {
                    foreach ($organizationPage->items() as $organization) {
                        if ($organization->slug === null) {
                            $scanWasIncomplete = true;

                            continue;
                        }

                        $serverPage = $forge->servers(
                            $organization->slug,
                            ['page' => ['size' => 100]],
                        );

                        while ($serverPage !== null) {
                            foreach ($serverPage->items() as $server) {
                                if ($server->id === null) {
                                    $scanWasIncomplete = true;

                                    continue;
                                }

                                $sitePage = $forge->serverSites(
                                    $organization->slug,
                                    $server->id,
                                    ['page' => ['size' => 100]],
                                );

                                while ($sitePage !== null) {
                                    foreach ($sitePage->items() as $site) {
                                        if ($site->id === null) {
                                            $scanWasIncomplete = true;

                                            continue;
                                        }

                                        $siteHealth = $this->siteHealth(
                                            $forge,
                                            $organization->slug,
                                            $server->id,
                                            $site,
                                        );

                                        if ($siteHealth === DeploymentHealth::Failed) {
                                            return DeploymentHealth::Failed;
                                        }

                                        if ($siteHealth === DeploymentHealth::Deploying) {
                                            $health = DeploymentHealth::Deploying;
                                        }
                                    }

                                    $sitePage = $this->nextPage($sitePage);
                                }
                            }

                            $serverPage = $this->nextPage($serverPage);
                        }
                    }

                    $organizationPage = $this->nextPage($organizationPage);
                }
            } catch (Throwable) {
                $scanWasIncomplete = true;
            }
        }

        if ($scanWasIncomplete && $health === DeploymentHealth::Healthy) {
            return null;
        }

        return $health;
    }

    private function siteHealth(
        Forge $forge,
        string $organizationSlug,
        int $serverId,
        Site $site,
    ): DeploymentHealth {
        $latestDeployment = $this->latestDeployment(
            $forge,
            $organizationSlug,
            $serverId,
            $site->id,
        );

        return DeploymentHealth::fromForgeStatuses(
            $site->deploymentStatus,
            $site->status,
            $latestDeployment?->status,
        );
    }

    private function latestDeployment(
        Forge $forge,
        string $organizationSlug,
        int $serverId,
        int $siteId,
    ): ?Deployment {
        return $forge->deployments(
            $organizationSlug,
            $serverId,
            $siteId,
            [
                'page' => ['size' => 1],
                'sort' => '-created_at',
            ],
        )->items()[0] ?? null;
    }

    private function nextPage(CursorPaginator $page): ?CursorPaginator
    {
        return $page->hasMorePages() ? $page->nextPage() : null;
    }
}
