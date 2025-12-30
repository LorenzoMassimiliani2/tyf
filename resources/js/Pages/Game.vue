<script setup>
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';

const props = defineProps({
    code: {
        type: String,
        default: '',
    },
});

const code = computed(() => (props.code || '').toUpperCase());
const state = reactive({
    game: null,
    player: null,
    players: [],
    turn: null,
    leaderboard: [],
    join_requests: [],
});

const activeTab = ref('turn');
const loading = ref(false);
const message = ref('');
const error = ref('');
const poller = ref(null);
const playerToken = ref(null);
const totalTurnsDisplay = computed(() => {
    if (!state.game) return '...';
    if (state.game.total_turns === 0) return '∞';
    const playersCount = state.players?.length || 0;
    return state.game.total_turns * Math.max(1, playersCount);
});

const loadToken = () => {
    const stored = localStorage.getItem(`tyf-token-${code.value}`);
    playerToken.value = stored;
};

const headers = computed(() => ({
    'X-PLAYER-TOKEN': playerToken.value,
}));

const ensureToken = () => {
    loadToken();
    return !!playerToken.value;
};

const fetchState = async () => {
    if (!ensureToken()) {
        error.value = 'Token mancante: torna alla home e rientra nella partita.';
        return;
    }

    try {
        const { data } = await axios.get(`/api/games/${code.value}/state`, { headers: headers.value });
        state.game = data.game;
        state.player = data.player;
        state.players = data.players;
        state.turn = data.turn;
        state.leaderboard = data.leaderboard;
        state.join_requests = data.join_requests || [];
        error.value = '';
    } catch (e) {
        error.value = e.response?.data?.message || 'Errore di sincronizzazione.';
    }
};

const startGame = async () => {
    loading.value = true;
    message.value = '';
    error.value = '';
    try {
        await axios.post(`/api/games/${code.value}/start`, {}, { headers: headers.value });
        await fetchState();
        message.value = '';
    } catch (e) {
        error.value = e.response?.data?.message || 'Impossibile avviare la partita.';
    } finally {
        loading.value = false;
    }
};

const chooseChallenge = async (id) => {
    if (!state.turn?.can_choose) return;
    loading.value = true;
    error.value = '';
    try {
        await axios.post(
            `/api/games/${code.value}/choose`,
            { challenge_id: id },
            { headers: headers.value },
        );
        await fetchState();
    } catch (e) {
        error.value = e.response?.data?.message || 'Non è stato possibile scegliere la prova.';
    } finally {
        loading.value = false;
    }
};

const sendVote = async (success) => {
    if (!state.turn?.can_vote) return;
    loading.value = true;
    error.value = '';
    try {
        await axios.post(
            `/api/games/${code.value}/vote`,
            { success },
            { headers: headers.value },
        );
        await fetchState();
    } catch (e) {
        error.value = e.response?.data?.message || 'Errore nell\'invio del voto.';
    } finally {
        loading.value = false;
    }
};

const approveJoin = async (playerId) => {
    loading.value = true;
    try {
        await axios.post(`/api/games/${code.value}/join/${playerId}/approve`, {}, { headers: headers.value });
        await fetchState();
    } catch (e) {
        error.value = e.response?.data?.message || 'Errore approvazione.';
    } finally {
        loading.value = false;
    }
};

const rejectJoin = async (playerId) => {
    loading.value = true;
    try {
        await axios.post(`/api/games/${code.value}/join/${playerId}/reject`, {}, { headers: headers.value });
        await fetchState();
    } catch (e) {
        error.value = e.response?.data?.message || 'Errore rifiuto.';
    } finally {
        loading.value = false;
    }
};

const removePlayer = async (playerId) => {
    loading.value = true;
    try {
        await axios.post(`/api/games/${code.value}/players/${playerId}/remove`, {}, { headers: headers.value });
        await fetchState();
    } catch (e) {
        error.value = e.response?.data?.message || 'Errore rimozione.';
    } finally {
        loading.value = false;
    }
};

const leaveGame = async () => {
    loading.value = true;
    try {
        await axios.post(`/api/games/${code.value}/leave`, {}, { headers: headers.value });
        localStorage.removeItem(`tyf-token-${code.value}`);
        window.location.href = '/';
    } catch (e) {
        error.value = e.response?.data?.message || 'Errore uscita.';
    } finally {
        loading.value = false;
    }
};

const confirmLeave = () => {
    if (window.confirm('Sei sicuro di voler uscire?')) {
        leaveGame();
    }
};

const waitingNames = computed(() => state.turn?.waiting_for?.map((w) => w.name).join(', ') || 'Nessuno');
const myVote = computed(() => {
    if (!state.turn?.votes || !state.player?.id) return null;
    return state.turn.votes.find((v) => v.voter_id === state.player.id) || null;
});

onMounted(() => {
    ensureToken();
    fetchState();
    poller.value = setInterval(fetchState, 2000);
});

onBeforeUnmount(() => {
    if (poller.value) {
        clearInterval(poller.value);
    }
});
</script>

<template>
    <Head title="Partita" />
    <div class="relative min-h-screen overflow-hidden bg-gradient-to-b from-[#101b33] via-[#0c162b] to-[#0a1122] text-white">
        <div
            class="pointer-events-none absolute inset-0 opacity-60"
            style="background-image: radial-gradient(circle at 15% 20%, rgba(255,255,255,0.08), transparent 36%), radial-gradient(circle at 82% 12%, rgba(94,234,212,0.12), transparent 34%), radial-gradient(circle at 60% 78%, rgba(255,161,94,0.12), transparent 38%);"
        />
        <div class="mx-auto max-w-xl px-4 py-5 space-y-4 relative">
            <div
                v-if="state.player"
                class="flex items-center justify-between rounded-xl bg-white/5 px-3 py-2 text-sm ring-1 ring-white/10"
            >
                <div class="flex items-center gap-2">
                    <span class="text-2xl">{{ state.player.avatar_url || '🙂' }}</span>
                    <div>
                        <p class="font-semibold leading-tight">{{ state.player.name }}</p>
                        <p class="text-[11px] text-white/60 leading-tight">
                            {{ state.player.status === 'active' ? 'Collegato' : 'In attesa di approvazione' }}
                        </p>
                    </div>
                </div>
            </div>

            <section
                v-if="state.game?.status === 'lobby' && state.player?.status === 'active'"
                class="rounded-2xl bg-white/5 px-4 py-3 space-y-2 ring-1 ring-white/10"
            >
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-[11px] uppercase text-white/60">Lobby</p>
                        <p class="text-2xl font-black tracking-wide">Codice {{ code }}</p>
                    </div>
                    <button
                        class="rounded-xl bg-orange-400 px-4 py-2 text-sm font-semibold text-slate-900 shadow disabled:opacity-50"
                        :disabled="loading"
                        @click="startGame"
                    >
                        Avvia partita
                    </button>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="p in state.players"
                        :key="p.id"
                        class="rounded-xl bg-white/10 px-3 py-2 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ p.avatar_url || '🙂' }}</span>
                            <p class="font-semibold text-sm">{{ p.name }}</p>
                        </div>
                        <span class="text-[11px] text-white/60">Turno #{{ p.turn_order }}</span>
                    </div>
                    <p v-if="!state.players.length" class="text-[12px] text-white/60">In attesa di giocatori...</p>
                </div>
            </section>

            <div v-if="state.game?.status !== 'lobby'">
                <nav class="flex gap-2 mb-3">
                    <button
                        class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold"
                        :class="activeTab === 'turn' ? 'bg-orange-400 text-slate-900' : 'bg-white/5 text-white'"
                        @click="activeTab = 'turn'"
                    >
                        Turno
                    </button>
                    <button
                        class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold"
                        :class="activeTab === 'board' ? 'bg-orange-400 text-slate-900' : 'bg-white/5 text-white'"
                        @click="activeTab = 'board'"
                    >
                        Classifica
                    </button>
                    <button
                        class="flex-1 rounded-xl px-3 py-2 text-sm font-semibold"
                        :class="activeTab === 'info' ? 'bg-orange-400 text-slate-900' : 'bg-white/5 text-white'"
                        @click="activeTab = 'info'"
                    >
                        Info
                    </button>
                </nav>

            <section v-if="state.game?.status !== 'lobby' && activeTab === 'turn'" class="rounded-2xl bg-white text-slate-900 px-4 py-4 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs text-slate-500">
                            Turno {{ state.turn?.number || 1 }}/{{ totalTurnsDisplay }}
                        </p>
                        <p class="text-lg font-bold flex items-center gap-2">
                            <span class="text-xl">{{ state.turn?.player?.avatar_url || '🙂' }}</span>
                            <span>{{ state.turn?.player?.name || '...' }}</span>
                        </p>
                    </div>
                  
                </div>

                <div v-if="state.turn && !state.turn.selected_challenge" class="space-y-3">
                    <article
                        v-for="challenge in state.turn.candidates"
                        :key="challenge.id"
                        class="rounded-2xl border border-orange-100 bg-[#e4e4e4] px-4 py-4 shadow-sm ring-1 ring-white"
                    >
                        <div class="flex justify-between items-start gap-3">
                            <div>
                                <p class="text-[11px] uppercase tracking-wide text-slate-500 font-semibold">{{ (challenge.category || 'Categoria').toUpperCase() }}</p>
                                <p class="text-base font-normal text-slate-900 normal-case">{{ challenge.title }}</p>
                            </div>
                            <span
                                class="rounded-full bg-orange-400 text-slate-900 px-3 py-1 text-[11px] font-black tracking-wide shadow-sm border border-orange-400 cursor-pointer"
                                :title="`Punteggio massimo: ${challenge.max_score} pt`"
                            >
                                LIV. {{ challenge.level }}
                            </span>
                        </div>
                        <p class="text-sm text-slate-600 mt-2 line-clamp-2">{{ challenge.description || 'Prova sorpresa' }}</p>
                        <div class="mt-3 pt-3 border-slate-100">
                            <button
                                v-if="state.turn.can_choose && state.turn.status === 'pending'"
                                class="w-full rounded-lg bg-[#1c2d4f] text-white py-2 text-sm font-semibold disabled:opacity-50"
                                :disabled="loading"
                                @click="chooseChallenge(challenge.id)"
                            >
                                Scegli
                            </button>
                        </div>
                    </article>
                </div>

                <div v-if="state.turn?.selected_challenge" class="rounded-2xl border border-orange-200 bg-orange-50/90 px-4 py-4 space-y-3 shadow-md shadow-orange-200/60">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="text-xl font-black text-slate-900 leading-tight">{{ state.turn.selected_challenge.title }}</p>
                            <p class="text-base text-slate-800 mt-1">{{ state.turn.selected_challenge.description }}</p>
                        </div>
                        <div class="flex flex-col items-end gap-2 text-[11px] font-semibold">
                            <span
                                class="rounded-full bg-orange-400 text-slate-900 px-3 py-1 border border-orange-400 shadow cursor-pointer"
                                :title="`Punteggio massimo: ${state.turn.selected_challenge.max_score} pt`"
                            >
                                LIV. {{ state.turn.selected_challenge.level }}
                            </span>
                            <span class="rounded-full bg-slate-900 text-white px-3 py-1">Max {{ state.turn.selected_challenge.max_score }} pt</span>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 items-stretch pt-2 border-orange-200">
                        <div v-if="myVote" class="rounded-xl bg-white text-slate-900 px-3 py-2 text-sm font-semibold flex items-center gap-2">
                            <span class="text-xs uppercase tracking-wide text-slate-500">Hai votato</span>
                            <span
                                class="rounded-full px-3 py-1 text-[11px] font-bold"
                                :class="myVote.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                            >
                                {{ myVote.success ? 'Superata' : 'Fallita' }}
                            </span>
                        </div>
                        <template v-if="state.turn.can_vote && !myVote">
                            <button
                                class="rounded-2xl bg-green-600 px-4 py-3 text-base font-bold text-white disabled:opacity-50"
                                :disabled="loading"
                                @click="sendVote(true)"
                            >
                                Superata
                            </button>
                            <button
                                class="rounded-2xl bg-red-500 px-4 py-3 text-base font-bold text-white disabled:opacity-50"
                                :disabled="loading"
                                @click="sendVote(false)"
                            >
                                Fallita
                            </button>
                        </template>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="w in state.turn.waiting_for"
                                :key="w.id"
                                class="rounded-full bg-white px-3 py-1 text-[11px] font-semibold text-slate-900"
                            >
                                In attesa: {{ w.name }}
                            </span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span
                            v-for="vote in state.turn.votes"
                            :key="vote.voter_id"
                            class="rounded-full px-3 py-1 text-[11px] font-semibold"
                            :class="vote.success ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
                        >
                            {{ vote.voter_name }} • {{ vote.success ? 'OK' : 'KO' }}
                        </span>
                    </div>
                </div>

                <div v-if="state.game?.status === 'finished'" class="rounded-xl bg-[#1c2d4f] text-white px-3 py-2 text-sm">
                    Partita conclusa! 🎉
                </div>
            </section>

            <section v-if="state.game?.status !== 'lobby' && activeTab === 'board'" class="rounded-2xl bg-white text-slate-900 px-4 py-4 space-y-2">
                <h3 class="text-base font-bold">Classifica</h3>
                <div class="space-y-2">
                    <div
                        v-for="(row, i) in state.leaderboard"
                        :key="row.id"
                        class="rounded-xl border border-slate-100 px-3 py-2 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-[11px] font-bold text-orange-600 w-6 text-center">#{{ i + 1 }}</span>
                            <span class="text-lg">{{ row.avatar_url || '🙂' }}</span>
                            <div>
                                <p class="font-semibold text-slate-900">{{ row.name }}</p>
                                <p class="text-[11px] text-slate-500">{{ row.turns }} turni</p>
                            </div>
                        </div>
                        <p class="text-sm font-black text-slate-900">{{ row.score }} pt</p>
                    </div>
                    <p v-if="!state.leaderboard?.length" class="text-sm text-slate-500">Ancora nessun turno completato.</p>
                </div>
            </section>

            <section v-if="state.game?.status !== 'lobby' && activeTab === 'info'" class="rounded-2xl bg-white/5 px-4 py-4 space-y-3 ring-1 ring-white/10">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold">Codice: {{ code }}</p>
                        <p class="text-[11px] text-white/60">Stato: {{ state.game?.status || 'lobby' }}</p>
                    </div>
                    <p class="text-[11px] text-white/60">Turni tot: {{ state.game?.total_turns === 0 ? '∞' : state.game?.total_turns }}</p>
                </div>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-semibold">Giocatori attivi ({{ state.players.length }})</p>
                    </div>
                </div>
                <div class="space-y-2">
                    <div
                        v-for="p in state.players"
                        :key="p.id"
                        class="rounded-xl bg-white/10 px-3 py-2 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ p.avatar_url || '🙂' }}</span>
                            <div>
                                <p class="font-semibold">{{ p.name }}</p>
                                <p class="text-[11px] text-white/60">Score: {{ p.score }} | Turni: {{ p.turns_played ?? 0 }}</p>
                            </div>
                        </div>
                        <button
                            class="text-[11px] font-semibold text-red-300"
                            @click="removePlayer(p.id)"
                        >
                            Rimuovi
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-semibold">Richieste di ingresso</p>
                        <p class="text-[11px] text-white/60">{{ state.join_requests.length }}</p>
                    </div>
                    <div v-if="!state.join_requests.length" class="text-[12px] text-white/60">Nessuna richiesta.</div>
                    <div
                        v-for="p in state.join_requests"
                        :key="p.id"
                        class="rounded-xl bg-white/10 px-3 py-2 flex items-center justify-between"
                    >
                        <div class="flex items-center gap-2">
                            <span class="text-xl">{{ p.avatar_url || '🙂' }}</span>
                            <div>
                                <p class="font-semibold">Nuovo collegamento per {{ p.name }}</p>
                                <p class="text-[11px] text-white/60">In attesa</p>
                            </div>
                        </div>
                        <div class="flex gap-2" v-if="p.id !== state.player?.id">
                            <button class="rounded-lg bg-green-500 px-3 py-1 text-[11px] font-semibold text-white" @click="approveJoin(p.id)">Accetta</button>
                            <button class="rounded-lg bg-red-500 px-3 py-1 text-[11px] font-semibold text-white" @click="rejectJoin(p.id)">Rifiuta</button>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-white/10">
                    <button
                        class="w-full rounded-xl bg-red-500 text-white py-2 text-sm font-semibold disabled:opacity-50"
                        :disabled="loading"
                        @click="confirmLeave"
                    >
                        Esci dalla partita
                    </button>
                </div>
            </section>

            <div v-if="error" class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">
                {{ error }}
            </div>
            </div>
        </div>
    </div>
</template>
