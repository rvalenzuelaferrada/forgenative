<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';

import { store } from '@/actions/App/Http/Controllers/ForgeCredentialController';
import { update as updateLanguage } from '@/actions/App/Http/Controllers/LanguagePreferenceController';
import { index as sitesIndex } from '@/routes/sites';

type ForgeCredential = {
    id: number;
    name: string;
    forge_email: string | null;
    last_verified_at: string | null;
};

type InterfaceCopy = {
    title: string;
    tagline: string;
    connection: string;
    connections: string;
    setup_badge: string;
    heading: string;
    description: string;
    security_note: string;
    name_label: string;
    name_placeholder: string;
    name_help: string;
    token_label: string;
    token_placeholder: string;
    validating: string;
    submit: string;
    created: string;
    connected_accounts: string;
    tokens_hidden: string;
    verified_account: string;
    verified_connection: string;
    current_connection: string;
    select_connection: string;
    language: string;
    language_description: string;
    language_device_note: string;
    selected: string;
};

defineProps<{
    copy: InterfaceCopy;
    credentials: ForgeCredential[];
    activeConnectionId: number | null;
    currentLocale: 'en' | 'es';
}>();
</script>

<template>
    <Head :title="copy.title" />

    <main
        class="min-h-screen bg-zinc-50 px-5 py-8 text-zinc-900 dark:bg-zinc-950 dark:text-zinc-100"
    >
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-8">
            <header class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <img
                        src="/icon-source.png"
                        alt="ForgeNative"
                        class="h-10 w-12 object-contain"
                    />
                    <div>
                        <p class="font-semibold tracking-tight">ForgeNative</p>
                        <p class="text-xs text-zinc-500">
                            {{ copy.tagline }}
                        </p>
                    </div>
                </div>

                <span
                    class="rounded-full border border-zinc-200 bg-white px-3 py-1 text-xs text-zinc-600 dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-400"
                >
                    {{ credentials.length }}
                    {{
                        credentials.length === 1
                            ? copy.connection
                            : copy.connections
                    }}
                </span>
            </header>

            <section
                class="grid overflow-hidden rounded-3xl border border-zinc-200 bg-white/80 shadow-2xl shadow-zinc-300/40 lg:grid-cols-[0.85fr_1.15fr] dark:border-zinc-800 dark:bg-zinc-900/70 dark:shadow-black/30"
            >
                <div
                    class="flex flex-col justify-between gap-8 border-b border-zinc-200 bg-zinc-100/80 p-7 lg:border-r lg:border-b-0 dark:border-zinc-800 dark:bg-zinc-900"
                >
                    <div class="flex flex-col gap-4">
                        <span
                            class="w-fit rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300"
                        >
                            {{ copy.setup_badge }}
                        </span>
                        <div class="flex flex-col gap-2">
                            <h1
                                class="text-2xl font-semibold tracking-tight text-zinc-950 dark:text-white"
                            >
                                {{ copy.heading }}
                            </h1>
                            <p
                                class="text-sm leading-6 text-zinc-600 dark:text-zinc-400"
                            >
                                {{ copy.description }}
                            </p>
                        </div>
                    </div>

                    <div
                        class="flex gap-3 text-xs leading-5 text-zinc-600 dark:text-zinc-500"
                    >
                        <svg
                            class="mt-0.5 size-4 shrink-0 text-emerald-400"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path
                                d="M12 3 5 6v5c0 4.8 2.9 8.1 7 10 4.1-1.9 7-5.2 7-10V6l-7-3Z"
                            />
                            <path d="m9 12 2 2 4-4" />
                        </svg>
                        <p>{{ copy.security_note }}</p>
                    </div>
                </div>

                <Form
                    :action="store()"
                    reset-on-success
                    #default="{ errors, processing, recentlySuccessful }"
                    class="flex flex-col gap-5 p-7"
                >
                    <div class="flex flex-col gap-2">
                        <label
                            for="name"
                            class="text-sm font-medium text-zinc-700 dark:text-zinc-200"
                        >
                            {{ copy.name_label }}
                        </label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            autocomplete="off"
                            :placeholder="copy.name_placeholder"
                            class="h-11 rounded-xl border border-zinc-300 bg-white px-3.5 text-sm text-zinc-900 transition outline-none placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-3 focus:ring-emerald-400/10 dark:border-zinc-700 dark:bg-zinc-950/70 dark:text-white dark:placeholder:text-zinc-600 dark:focus:border-emerald-400"
                        />
                        <p class="text-xs text-zinc-500">
                            {{ copy.name_help }}
                        </p>
                        <p v-if="errors.name" class="text-xs text-red-400">
                            {{ errors.name }}
                        </p>
                    </div>

                    <div class="flex flex-col gap-2">
                        <label
                            for="token"
                            class="text-sm font-medium text-zinc-700 dark:text-zinc-200"
                        >
                            {{ copy.token_label }}
                        </label>
                        <textarea
                            id="token"
                            name="token"
                            rows="5"
                            autocomplete="off"
                            autocapitalize="off"
                            spellcheck="false"
                            :placeholder="copy.token_placeholder"
                            class="min-h-32 resize-y rounded-xl border border-zinc-300 bg-white px-3.5 py-3 font-mono text-sm break-all text-zinc-900 transition outline-none placeholder:font-sans placeholder:text-zinc-400 focus:border-emerald-500 focus:ring-3 focus:ring-emerald-400/10 dark:border-zinc-700 dark:bg-zinc-950/70 dark:text-white dark:placeholder:text-zinc-600 dark:focus:border-emerald-400"
                        />
                        <p v-if="errors.token" class="text-xs text-red-400">
                            {{ errors.token }}
                        </p>
                    </div>

                    <button
                        type="submit"
                        :disabled="processing"
                        class="mt-1 flex h-11 items-center justify-center rounded-xl bg-emerald-400 px-4 text-sm font-semibold text-zinc-950 transition hover:bg-emerald-300 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        {{ processing ? copy.validating : copy.submit }}
                    </button>

                    <p
                        v-if="recentlySuccessful"
                        class="text-center text-xs text-emerald-400"
                    >
                        {{ copy.created }}
                    </p>
                </Form>
            </section>

            <section v-if="credentials.length" class="flex flex-col gap-3">
                <div class="flex items-end justify-between gap-4">
                    <div>
                        <h2
                            class="text-sm font-semibold text-zinc-800 dark:text-zinc-200"
                        >
                            {{ copy.connected_accounts }}
                        </h2>
                        <p class="mt-1 text-xs text-zinc-500">
                            {{ copy.tokens_hidden }}
                        </p>
                    </div>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <Link
                        v-for="credential in credentials"
                        :key="credential.id"
                        :href="
                            sitesIndex({
                                query: {
                                    connection: credential.id,
                                },
                            })
                        "
                        :aria-label="`${copy.select_connection}: ${credential.name}`"
                        class="flex items-center gap-3 rounded-2xl border bg-white p-4 transition data-loading:opacity-60 dark:bg-zinc-900/60"
                        :class="
                            credential.id === activeConnectionId
                                ? 'border-emerald-400/50 ring-2 ring-emerald-400/10'
                                : 'border-zinc-200 hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:border-zinc-700 dark:hover:bg-zinc-900'
                        "
                    >
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-zinc-100 text-sm font-semibold text-emerald-700 dark:bg-zinc-800 dark:text-emerald-300"
                        >
                            {{ credential.name.slice(0, 1).toUpperCase() }}
                        </span>
                        <div class="min-w-0">
                            <p
                                class="truncate text-sm font-medium text-zinc-800 dark:text-zinc-200"
                            >
                                {{ credential.name }}
                            </p>
                            <p class="truncate text-xs text-zinc-500">
                                {{
                                    credential.forge_email ??
                                    copy.verified_account
                                }}
                            </p>
                        </div>
                        <span
                            v-if="credential.id === activeConnectionId"
                            class="ml-auto rounded-full bg-emerald-500/10 px-2 py-1 text-[10px] font-medium text-emerald-700 dark:bg-emerald-400/10 dark:text-emerald-300"
                        >
                            {{ copy.current_connection }}
                        </span>
                        <svg
                            v-else
                            class="ml-auto size-4 shrink-0 text-zinc-400 dark:text-zinc-600"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="1.8"
                            aria-hidden="true"
                        >
                            <path d="m9 18 6-6-6-6" />
                        </svg>
                    </Link>
                </div>
            </section>

            <section class="flex flex-col gap-3">
                <div>
                    <h2
                        class="text-sm font-semibold text-zinc-800 dark:text-zinc-200"
                    >
                        {{ copy.language }}
                    </h2>
                    <p class="mt-1 text-xs text-zinc-500">
                        {{ copy.language_description }}
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <Form
                        v-for="language in [
                            { locale: 'en', label: 'English' },
                            { locale: 'es', label: 'Español' },
                        ]"
                        :key="language.locale"
                        :action="updateLanguage()"
                        #default="{ processing }"
                    >
                        <input
                            type="hidden"
                            name="locale"
                            :value="language.locale"
                        />
                        <button
                            type="submit"
                            :disabled="
                                processing || currentLocale === language.locale
                            "
                            class="flex w-full items-center justify-between gap-3 rounded-xl border px-4 py-3 text-left text-sm font-medium transition disabled:cursor-default"
                            :class="
                                currentLocale === language.locale
                                    ? 'border-emerald-500/50 bg-emerald-500/10 text-emerald-800 dark:border-emerald-400/50 dark:bg-emerald-400/10 dark:text-emerald-200'
                                    : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-300 hover:bg-zinc-50 disabled:opacity-60 dark:border-zinc-800 dark:bg-zinc-900/60 dark:text-zinc-300 dark:hover:border-zinc-700 dark:hover:bg-zinc-900'
                            "
                        >
                            <span>{{ language.label }}</span>
                            <span
                                v-if="currentLocale === language.locale"
                                class="text-[10px] font-semibold tracking-wide text-emerald-400 uppercase"
                            >
                                {{ copy.selected }}
                            </span>
                        </button>
                    </Form>
                </div>

                <p
                    class="text-[11px] leading-4 text-zinc-500 dark:text-zinc-600"
                >
                    {{ copy.language_device_note }}
                </p>
            </section>
        </div>
    </main>
</template>
