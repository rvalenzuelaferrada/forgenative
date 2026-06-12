<?php

namespace App\Services;

use App\Models\ForgeCredential;
use Laravel\Forge\Exceptions\ForbiddenException;
use Laravel\Forge\Forge;
use Laravel\Forge\Resources\Organization;
use Laravel\Forge\Resources\Site;
use Throwable;

class ForgeOverview
{
    public function __construct(
        private ForgeClientFactory $clients,
        private ForgeTokenVault $vault,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function load(
        ?int $requestedConnectionId = null,
        ?string $requestedOrganizationSlug = null,
    ): array {
        $credential = $this->resolveCredential($requestedConnectionId);
        $forge = $this->clients->make(
            $this->vault->decrypt($credential->encrypted_token),
        );

        $organizations = [];
        $canViewOrganizations = true;

        try {
            $organizations = array_map(
                fn (Organization $organization): array => [
                    'slug' => (string) $organization->slug,
                    'name' => (string) ($organization->name ?? $organization->slug),
                ],
                $forge->organizations(['page' => ['size' => 100]])->items(),
            );
        } catch (ForbiddenException) {
            $canViewOrganizations = false;
        } catch (Throwable) {
            return $this->failedOverview($credential, 'unavailable');
        }

        $activeOrganizationSlug = $this->resolveOrganizationSlug(
            $organizations,
            $requestedOrganizationSlug,
        );

        try {
            $sites = $this->loadSites($forge, $activeOrganizationSlug);
        } catch (ForbiddenException) {
            return [
                ...$this->emptyOverview($credential),
                'active_organization_slug' => $activeOrganizationSlug,
                'organizations' => $organizations,
                'capabilities' => [
                    'organizations' => $canViewOrganizations,
                    'sites' => false,
                ],
                'error' => 'sites_forbidden',
            ];
        } catch (Throwable) {
            return [
                ...$this->emptyOverview($credential),
                'active_organization_slug' => $activeOrganizationSlug,
                'organizations' => $organizations,
                'capabilities' => [
                    'organizations' => $canViewOrganizations,
                    'sites' => false,
                ],
                'error' => 'unavailable',
            ];
        }

        return [
            'active_connection_id' => $credential->id,
            'active_organization_slug' => $activeOrganizationSlug,
            'organizations' => $organizations,
            'sites' => array_map(
                fn (Site $site): array => $this->siteData($site),
                $sites['items'],
            ),
            'capabilities' => [
                'organizations' => $canViewOrganizations,
                'sites' => true,
            ],
            'has_more_sites' => $sites['has_more'],
            'error' => null,
        ];
    }

    private function resolveCredential(?int $requestedConnectionId): ForgeCredential
    {
        if ($requestedConnectionId !== null) {
            $requestedCredential = ForgeCredential::query()->find($requestedConnectionId);

            if ($requestedCredential !== null) {
                return $requestedCredential;
            }
        }

        return ForgeCredential::query()->latest()->firstOrFail();
    }

    /**
     * @param  array<int, array{slug: string, name: string}>  $organizations
     */
    private function resolveOrganizationSlug(
        array $organizations,
        ?string $requestedOrganizationSlug,
    ): ?string {
        $slugs = array_column($organizations, 'slug');

        if (
            $requestedOrganizationSlug !== null
            && in_array($requestedOrganizationSlug, $slugs, true)
        ) {
            return $requestedOrganizationSlug;
        }

        return $organizations[0]['slug'] ?? null;
    }

    /**
     * @return array{items: array<int, Site>, has_more: bool}
     */
    private function loadSites(Forge $forge, ?string $organizationSlug): array
    {
        $sites = $organizationSlug === null
            ? $forge->sites(['page' => ['size' => 100]])
            : $forge->organizationSites($organizationSlug, ['page' => ['size' => 100]]);

        return [
            'items' => $sites->items(),
            'has_more' => $sites->hasMorePages(),
        ];
    }

    /**
     * @return array<string, int|string|null>
     */
    private function siteData(Site $site): array
    {
        return [
            'id' => (int) $site->id,
            'name' => (string) ($site->name ?? 'Unnamed site'),
            'url' => $site->url,
            'status' => $site->status,
            'deployment_status' => $site->deploymentStatus,
            'php_version' => $site->phpVersion,
            'organization_slug' => isset($site->organizationSlug)
                ? $site->organizationSlug
                : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyOverview(ForgeCredential $credential): array
    {
        return [
            'active_connection_id' => $credential->id,
            'active_organization_slug' => null,
            'organizations' => [],
            'sites' => [],
            'capabilities' => [
                'organizations' => false,
                'sites' => false,
            ],
            'has_more_sites' => false,
            'error' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function failedOverview(
        ForgeCredential $credential,
        string $error,
    ): array {
        return [
            ...$this->emptyOverview($credential),
            'error' => $error,
        ];
    }
}
