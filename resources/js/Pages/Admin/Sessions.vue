<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { computed } from 'vue';

const games = computed(() => usePage().props.games || []);

const statusLabel = (status) => {
    if (status === 'active') return 'Attiva';
    if (status === 'finished') return 'Conclusa';
    return 'Lobby';
};

const deleteGame = async (game) => {
    if (!confirm(`Eliminare la sessione ${game.code}?`)) return;
    await axios.delete(`/admin/sessions/${game.id}`);
    router.reload({ only: ['games'] });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <Head title="Sessioni" />
    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Backoffice</p>
                    <h1 class="text-2xl font-black text-slate-900">Sessioni</h1>
                    <p class="text-slate-600">Gestisci le partite create. Elimina sessioni concluse o inutilizzate.</p>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        :href="route('admin.categories.index')"
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                    >
                        Categorie
                    </Link>
                    <Link
                        :href="route('admin.challenges.index')"
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                    >
                        Prove
                    </Link>
                    <Link
                        :href="route('admin.sessions.index')"
                        class="rounded-full bg-[#1c2d4f] px-3 py-1 text-sm font-semibold text-white shadow hover:-translate-y-0.5 transition"
                    >
                        Sessioni
                    </Link>
                    <Link
                        :href="route('admin.db.index')"
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                    >
                        DB
                    </Link>
                    <button
                        type="button"
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                        @click="logout"
                    >
                        Logout
                    </button>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-3">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-bold text-slate-900">Elenco sessioni</h2>
                    <span class="text-xs font-semibold rounded-full bg-orange-100 px-3 py-1 text-orange-700">
                        {{ games.length }} totali
                    </span>
                </div>
                <div class="grid gap-3">
                    <div
                        v-for="game in games"
                        :key="game.id"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-3 flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Codice</p>
                            <p class="text-xl font-black text-slate-900 tracking-wide">{{ game.code }}</p>
                            <p class="text-xs text-slate-500">
                                Creato: {{ game.created_at ? new Date(game.created_at).toLocaleString() : '—' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span
                                class="rounded-full px-3 py-1 text-[11px] font-semibold"
                                :class="game.status === 'active' ? 'bg-green-100 text-green-700' : game.status === 'finished' ? 'bg-slate-200 text-slate-800' : 'bg-orange-100 text-orange-700'"
                            >
                                {{ statusLabel(game.status) }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-800">
                                Giocatori: {{ game.players_count }}
                            </span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-[11px] font-semibold text-slate-800">
                                Turni: {{ game.turns_count }}
                            </span>
                        </div>
                        <div class="flex flex-col items-end text-right text-xs text-slate-500">
                            <span>Avvio: {{ game.started_at ? new Date(game.started_at).toLocaleString() : '—' }}</span>
                            <span>Fine: {{ game.ended_at ? new Date(game.ended_at).toLocaleString() : '—' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <a
                                :href="route('games.show', game.code)"
                                class="rounded-full border border-slate-300 px-3 py-1 text-[11px] font-semibold text-slate-800 hover:-translate-y-0.5 transition"
                                target="_blank"
                            >
                                Apri
                            </a>
                            <button
                                class="rounded-full bg-red-500 px-3 py-1 text-[11px] font-semibold text-white hover:-translate-y-0.5 transition"
                                @click="deleteGame(game)"
                            >
                                Elimina
                            </button>
                        </div>
                    </div>
                    <p v-if="!games.length" class="text-sm text-slate-500">Nessuna sessione da mostrare.</p>
                </div>
            </div>
        </div>
    </div>
</template>
