<script setup>
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Party Game" />
    <div class="min-h-screen bg-gradient-to-b from-orange-200 via-amber-100 to-yellow-100">
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute left-0 top-20 h-28 w-28 bg-white/30 rounded-full blur-3xl" />
            <div class="absolute right-4 bottom-10 h-36 w-36 bg-orange-400/25 rounded-full blur-3xl" />
        </div>

        <div class="relative mx-auto max-w-xl px-4 py-14 space-y-10">
            <header class="space-y-3 text-center">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-orange-600">Ready to play</p>
                <h1 class="text-3xl font-black text-slate-900 sm:text-4xl">Scegli cosa fare</h1>
            </header>

            <div class="space-y-4">
                <Link
                    :href="route('host')"
                    class="block rounded-2xl bg-[#1c2d4f] px-5 py-4 text-white text-lg font-bold shadow-lg shadow-slate-900/40 transition hover:-translate-y-0.5"
                >
                    Crea partita
                </Link>
                <Link
                    :href="route('join')"
                    class="block rounded-2xl bg-white px-5 py-4 text-slate-900 text-lg font-bold shadow-lg shadow-orange-200/70 ring-1 ring-slate-900/5 transition hover:-translate-y-0.5"
                >
                    Unisciti con codice
                </Link>
            </div>

            <div class="flex justify-center">
                <Link
                    :href="route('admin.challenges.index')"
                    class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-2 text-xs font-semibold text-slate-800 shadow ring-1 ring-white/80"
                >
                    Area admin <span aria-hidden="true">↗</span>
                </Link>
            </div>
        </div>
    </div>
</template>
