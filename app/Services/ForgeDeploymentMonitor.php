<?php

namespace App\Services;

use App\DeploymentHealth;
use App\Models\ForgeCredential;
use Laravel\Forge\CursorPaginator;
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

                $page = $forge->sites(['page' => ['size' => 100]]);

                while ($page !== null) {
                    foreach ($page->items() as $site) {
                        $siteHealth = $this->siteHealth($site);

                        if ($siteHealth === DeploymentHealth::Failed) {
                            return DeploymentHealth::Failed;
                        }

                        if ($siteHealth === DeploymentHealth::Deploying) {
                            $health = DeploymentHealth::Deploying;
                        }
                    }

                    $page = $this->nextPage($page);
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

    private function siteHealth(Site $site): DeploymentHealth
    {
        return DeploymentHealth::fromForgeStatuses(
            $site->deploymentStatus,
            $site->status,
        );
    }

    private function nextPage(CursorPaginator $page): ?CursorPaginator
    {
        return $page->hasMorePages() ? $page->nextPage() : null;
    }
}
