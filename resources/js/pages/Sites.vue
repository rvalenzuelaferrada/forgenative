<script setup lang="ts">
import { Form, Head, Link, router, usePoll } from '@inertiajs/vue3';
import { computed } from 'vue';

import DeploymentStatusController from '@/actions/App/Http/Controllers/DeploymentStatusController';
import { index as connectionsIndex } from '@/routes/forge-credentials';
import { index as sitesIndex } from '@/routes/sites';

type Connection = {
    id: number;
    name: string;
    forge_email: string | null;
    last_verified_at: string | null;
};

type Organization = {
    slug: string;
    name: string;
};

type Site = {
    id: number;
    name: string;
    url: string | null;
    status: string | null;
    deployment_status: string | null;
    deployment_health: 'healthy' | 'deploying' | 'failed';
    php_version: string | null;
    organization_slug: string | null;
};

type Overview = {
    active_connection_id: number;
    active_organization_slug: string | null;
    organizations: Organization[];
    sites: Site[];
    capabilities: {
        organizations: boolean;
        sites: boolean;
    };
    has_more_sites: boolean;
    error: 'sites_forbidden' | 'unavailable' | null;
};

type Copy = {
    title: string;
    tagline: string;
    sites: string;
    connection: string;
    organization: string;
    add_connection: string;
    switch_connection: string;
    refresh_now: string;
    refreshing: string;
    no_sites: string;
    no_sites_description: string;
    sites_forbidden: string;
    unavailable: string;
    organizations_hidden: string;
    more_sites: string;
    php: string;
    ready: string;
    unknown_status: string;
};

const props = defineProps<{
    copy: Copy;
    connections: Connection[];
    overview: Overview;
}>();

usePoll(
    60_000,
    {
        only: ['overview'],
        preserveScroll: true,
    },
    {
        keepAlive: true,
        mode: 'rest',
    },
);

const deploymentHealthClasses = {
    healthy: 'bg-emerald-400 shadow-[0_0_8px_rgba(52,211,153,0.55)]',
    deploying: 'bg-[#DDA900] shadow-[0_0_8px_rgba(221,169,0,0.55)]',
    failed: 'bg-[#DD0000] shadow-[0_0_8px_rgba(221,0,0,0.55)]',
} satisfies Record<Site['deployment_health'], string>;

const activeConnection = computed(
    () =>
        props.connections.find(
            (connection) =>
                connection.id === props.overview.active_connection_id,
        ) ?? props.connections[0],
);

const activeOrganization = computed(
    () =>
        props.overview.organizations.find(
            (organization) =>
                organization.slug === props.overview.active_organization_slug,
        ) ?? props.overview.organizations[0],
);

const contextQuery = (
    connectionId: number,
    organizationSlug: string | null,
) => ({
    connection: connectionId,
    ...(organizationSlug ? { organization: organizationSlug } : {}),
});

const switchConnection = (event: Event) => {
    const connectionId = Number((event.target as HTMLSelectElement).value);

    router.get(
        sitesIndex.url({
            query: contextQuery(connectionId, null),
        }),
        {},
        { preserveScroll: true },
    );
};

const switchOrganization = (event: Event) => {
    const organizationSlug = (event.target as HTMLSelectElement).value;

    router.get(
        sitesIndex.url({
            query: contextQuery(
                props.overview.active_connection_id,
                organizationSlug,
            ),
        }),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="copy.title" />

    <main
        class="min-h-screen bg-zinc-50 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100"
    >
        <header
            class="sticky top-0 z-10 border-b border-zinc-200 bg-zinc-50/95 px-4 pt-4 pb-3 backdrop-blur dark:border-zinc-800 dark:bg-zinc-950/95"
        >
            <div class="flex items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-2.5">
                    <img
                        src="/icon-source.png"
                        alt="ForgeNative"
                        class="h-8 w-10 shrink-0 object-contain"
                    />
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">
                            ForgeNative
                        </p>
                        <p
                            class="truncate text-[11px] text-zinc-500 dark:text-zinc-500"
                        >
                            {{ copy.tagline }}
                        </p>
                    </div>
                </div>

                <Link
                    :href="
                        connectionsIndex({
                            query: {
                                connection: overview.active_connection_id,
                            },
                        })
                    "
                    class="rounded-lg border border-zinc-300 bg-white px-2.5 py-1.5 text-[11px] font-medium text-zinc-700 transition hover:border-zinc-400 hover:text-zinc-950 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:text-white"
                >
                    {{ copy.switch_connection }}
                </Link>
            </div>

            <div class="mt-4 grid gap-2">
                <label
                    v-if="connections.length > 1"
                    class="grid grid-cols-[92px_1fr] items-center gap-3"
                >
                    <span class="text-xs text-zinc-500">
                        {{ copy.connection }}
                    </span>
                    <select
                        :value="overview.active_connection_id"
                        class="h-9 min-w-0 rounded-lg border border-zinc-300 bg-white px-2.5 text-xs text-zinc-800 outline-none focus:border-emerald-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:focus:border-emerald-400"
                        @change="switchConnection"
                    >
                        <option
                            v-for="connection in connections"
                            :key="connection.id"
                            :value="connection.id"
                        >
                            {{ connection.name }}
                        </option>
                    </select>
                </label>

                <div
                    v-else
                    class="flex items-center justify-between gap-3 rounded-lg bg-zinc-100 px-3 py-2 dark:bg-zinc-900/70"
                >
                    <span class="text-[11px] text-zinc-500">
                        {{ copy.connection }}
                    </span>
                    <span
                        class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-200"
                    >
                        {{ activeConnection?.name }}
                    </span>
                </div>

                <label
                    v-if="overview.organizations.length > 1"
                    class="grid grid-cols-[92px_1fr] items-center gap-3"
                >
                    <span class="text-xs text-zinc-500">
                        {{ copy.organization }}
                    </span>
                    <select
                        :value="overview.active_organization_slug ?? ''"
                        class="h-9 min-w-0 rounded-lg border border-zinc-300 bg-white px-2.5 text-xs text-zinc-800 outline-none focus:border-emerald-500 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-200 dark:focus:border-emerald-400"
                        @change="switchOrganization"
                    >
                        <option
                            v-for="organization in overview.organizations"
                            :key="organization.slug"
                            :value="organization.slug"
                        >
                            {{ organization.name }}
                        </option>
                    </select>
                </label>

                <div
                    v-else-if="activeOrganization"
                    class="flex items-center justify-between gap-3 rounded-lg bg-zinc-100 px-3 py-2 dark:bg-zinc-900/70"
                >
                    <span class="text-[11px] text-zinc-500">
                        {{ copy.organization }}
                    </span>
                    <span
                        class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-200"
                    >
                        {{ activeOrganization.name }}
                    </span>
                </div>
            </div>
        </header>

        <section class="px-4 py-4">
            <div class="mb-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span
                        class="flex size-7 items-center justify-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300"
                    >
                        <svg
                            class="size-4"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path d="M4 7h16M6 3h12l2 4v13H4V7l2-4Z" />
                            <path d="M8 11h8M8 15h5" />
                        </svg>
                    </span>
                    <h1 class="text-sm font-semibold">{{ copy.sites }}</h1>
                </div>
                <div class="flex items-center gap-2">
                    <span
                        v-if="overview.capabilities.sites"
                        class="text-[11px] text-zinc-500 tabular-nums"
                    >
                        {{ overview.sites.length }}
                    </span>
                    <Form
                        :action="DeploymentStatusController()"
                        #default="{ processing }"
                    >
                        <button
                            type="submit"
                            :disabled="processing"
                            class="rounded-md border border-zinc-300 bg-white px-2 py-1 text-[10px] font-medium text-zinc-600 transition hover:border-zinc-400 hover:text-zinc-900 disabled:cursor-wait disabled:opacity-60 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400 dark:hover:border-zinc-700 dark:hover:text-zinc-200"
                        >
                            {{
                                processing ? copy.refreshing : copy.refresh_now
                            }}
                        </button>
                    </Form>
                </div>
            </div>

            <div
                v-if="
                    !overview.capabilities.organizations &&
                    overview.capabilities.sites
                "
                class="mb-3 rounded-xl border border-amber-500/25 bg-amber-50 px-3 py-2 text-[11px] leading-4 text-amber-800 dark:border-amber-400/15 dark:bg-amber-400/5 dark:text-amber-200/80"
            >
                {{ copy.organizations_hidden }}
            </div>

            <div
                v-if="overview.error"
                class="rounded-2xl border border-red-500/20 bg-red-50 p-4 text-sm leading-5 text-red-700 dark:border-red-400/15 dark:bg-red-400/5 dark:text-red-200"
            >
                {{
                    overview.error === 'sites_forbidden'
                        ? copy.sites_forbidden
                        : copy.unavailable
                }}
            </div>

            <div
                v-else-if="overview.sites.length === 0"
                class="flex min-h-52 flex-col items-center justify-center rounded-2xl border border-dashed border-zinc-300 px-8 text-center dark:border-zinc-800"
            >
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ copy.no_sites }}
                </p>
                <p class="mt-1 text-xs leading-5 text-zinc-500">
                    {{ copy.no_sites_description }}
                </p>
            </div>

            <div v-else class="grid gap-2">
                <article
                    v-for="site in overview.sites"
                    :key="site.id"
                    class="group rounded-xl border border-zinc-200 bg-white p-3 shadow-sm transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-900/65 dark:shadow-none dark:hover:border-zinc-700 dark:hover:bg-zinc-900"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-1 size-2 shrink-0 rounded-full"
                            :class="
                                deploymentHealthClasses[site.deployment_health]
                            "
                        />
                        <div class="min-w-0 flex-1">
                            <p
                                class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200"
                            >
                                {{ site.name }}
                            </p>
                            <p
                                v-if="site.url"
                                class="mt-0.5 truncate text-[11px] text-zinc-500"
                            >
                                {{ site.url }}
                            </p>
                        </div>
                        <span
                            class="rounded-md bg-zinc-100 px-2 py-1 text-[10px] text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                        >
                            {{
                                site.php_version
                                    ? `${copy.php} ${site.php_version}`
                                    : (site.status ?? copy.unknown_status)
                            }}
                        </span>
                    </div>
                </article>
            </div>

            <p
                v-if="overview.has_more_sites"
                class="mt-3 text-center text-[11px] text-zinc-500"
            >
                {{ copy.more_sites }}
            </p>
        </section>
    </main>
</template>
