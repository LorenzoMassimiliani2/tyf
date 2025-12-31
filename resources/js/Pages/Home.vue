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
                    class="block rounded-3xl bg-gradient-to-r from-[#1c2d4f] via-[#1f2f54] to-[#1c2d4f] px-6 py-6 text-white text-lg font-black shadow-xl shadow-slate-900/50 transition hover:-translate-y-0.5"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🎲</span>
                            <div class="text-left">
                                <p class="text-base font-semibold leading-tight text-white/70">Host</p>
                                <p class="text-2xl font-black leading-tight">Crea partita</p>
                            </div>
                        </div>
                        <span class="text-2xl">→</span>
                    </div>
                </Link>
                <Link
                    :href="route('join')"
                    class="block rounded-3xl bg-gradient-to-r from-orange-500 to-orange-400 px-6 py-6 text-white text-lg font-black shadow-xl shadow-orange-400/60 transition hover:-translate-y-0.5"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <span class="text-2xl">🚀</span>
                            <div class="text-left">
                                <p class="text-base font-semibold leading-tight text-white/80">Join</p>
                                <p class="text-2xl font-black leading-tight">Unisciti a una partita</p>
                            </div>
                        </div>
                        <span class="text-xl text-white">→</span>
                    </div>
                </Link>
            </div>

            <div class="flex justify-center">
                <Link
                    :href="route('admin.challenges.index')"
                    class="inline-flex items-center gap-2 rounded-full bg-white/70 px-3.5 py-2 text-[11px] font-semibold text-slate-700 shadow-sm ring-1 ring-slate-200 hover:-translate-y-0.5 transition"
                >
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-orange-700 text-sm">⚙️</span>
                    <span>Area admin</span>
                </Link>
            </div>
        </div>
    </div>
</template>
