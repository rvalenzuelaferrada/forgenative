<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';

import { store } from '@/actions/App/Http/Controllers/LanguagePreferenceController';
</script>

<template>
    <Head title="Choose your language" />

    <main
        class="flex min-h-screen items-center justify-center bg-zinc-950 px-5 py-8 text-zinc-100"
    >
        <section
            class="w-full max-w-xl overflow-hidden rounded-3xl border border-zinc-800 bg-zinc-900/70 shadow-2xl shadow-black/30"
        >
            <div class="flex flex-col items-center gap-4 px-7 pt-8 text-center">
                <div
                    class="flex size-12 items-center justify-center rounded-2xl bg-emerald-400 text-sm font-bold text-zinc-950 shadow-[0_0_32px_rgba(52,211,153,0.2)]"
                >
                    FN
                </div>

                <div class="flex flex-col gap-2">
                    <h1
                        class="text-2xl font-semibold tracking-tight text-white"
                    >
                        Choose your language
                    </h1>
                    <p class="text-sm text-zinc-400">
                        Elige el idioma de la aplicación
                    </p>
                </div>
            </div>

            <div class="grid gap-3 p-7 sm:grid-cols-2">
                <Form
                    :action="store()"
                    #default="{ processing }"
                    class="contents"
                >
                    <input type="hidden" name="locale" value="en" />
                    <button
                        type="submit"
                        :disabled="processing"
                        class="flex min-h-32 flex-col items-start justify-between rounded-2xl border border-emerald-400 bg-emerald-400/10 p-5 text-left transition hover:bg-emerald-400/15 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span
                            class="rounded-full bg-emerald-400 px-2.5 py-1 text-[11px] font-semibold text-zinc-950"
                        >
                            Default
                        </span>
                        <span>
                            <span
                                class="block text-lg font-semibold text-white"
                            >
                                English
                            </span>
                            <span class="mt-1 block text-xs text-zinc-400">
                                Continue in English
                            </span>
                        </span>
                    </button>
                </Form>

                <Form
                    :action="store()"
                    #default="{ processing }"
                    class="contents"
                >
                    <input type="hidden" name="locale" value="es" />
                    <button
                        type="submit"
                        :disabled="processing"
                        class="flex min-h-32 flex-col items-start justify-end rounded-2xl border border-zinc-700 bg-zinc-950/50 p-5 text-left transition hover:border-zinc-600 hover:bg-zinc-800/60 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        <span>
                            <span
                                class="block text-lg font-semibold text-white"
                            >
                                Español
                            </span>
                            <span class="mt-1 block text-xs text-zinc-400">
                                Continuar en español
                            </span>
                        </span>
                    </button>
                </Form>
            </div>

            <p
                class="border-t border-zinc-800 px-7 py-4 text-center text-xs text-zinc-500"
            >
                Your preference is stored only on this device.
                <span class="text-zinc-600">·</span>
                Tu preferencia se guarda solo en este dispositivo.
            </p>
        </section>
    </main>
</template>
