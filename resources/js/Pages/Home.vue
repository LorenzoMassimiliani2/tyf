<script setup>
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref, computed } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const categories = computed(() => props.categories ?? []);

const avatars = ['🦊', '🐼', '🤖', '👾', '🌟', '⚡', '🎲', '🚀', '🎨', '🍕', '🏓', '🎧', '🧊', '🛸'];

const hostForm = reactive({
    host_name: '',
    avatar_url: avatars[0],
    total_turns: 12,
    starting_difficulty: 1,
    difficulty_step_turns: 3,
    candidate_count: 3,
    category_ids: props.categories.map((c) => c.id),
});

const joinForm = reactive({
    code: '',
    name: '',
    avatar_url: avatars[1],
});

const loadingHost = ref(false);
const loadingJoin = ref(false);
const feedback = ref('');
const error = ref('');

const saveToken = (code, token) => {
    if (code && token) {
        localStorage.setItem(`tyf-token-${code.toUpperCase()}`, token);
    }
};

const goToGame = (code) => {
    window.location.href = `/games/${code.toUpperCase()}`;
};

const handleError = (e) => {
    if (e.response?.data?.message) {
        error.value = e.response.data.message;
    } else {
        error.value = 'Ops, qualcosa è andato storto. Riprova.';
    }
};

const hostGame = async () => {
    loadingHost.value = true;
    feedback.value = '';
    error.value = '';
    try {
        const { data } = await axios.post('/api/games', hostForm);
        saveToken(data.game.code, data.player.token);
        feedback.value = 'Stanza creata! Ti porto alla lobby...';
        goToGame(data.game.code);
    } catch (e) {
        handleError(e);
    } finally {
        loadingHost.value = false;
    }
};

const joinGame = async () => {
    loadingJoin.value = true;
    feedback.value = '';
    error.value = '';
    try {
        const code = joinForm.code.trim().toUpperCase();
        const { data } = await axios.post(`/api/games/${code}/join`, {
            name: joinForm.name,
            avatar_url: joinForm.avatar_url,
        });
        saveToken(data.game.code, data.player.token);
        feedback.value = 'Sei entrato! Ti porto alla lobby...';
        goToGame(code);
    } catch (e) {
        handleError(e);
    } finally {
        loadingJoin.value = false;
    }
};

const toggleCategory = (id) => {
    if (hostForm.category_ids.includes(id)) {
        hostForm.category_ids = hostForm.category_ids.filter((c) => c !== id);
    } else {
        hostForm.category_ids.push(id);
    }
};
</script>

<template>
    <Head title="Party Game" />
    <div class="min-h-screen bg-gradient-to-b from-amber-200 via-orange-100 to-yellow-100">
        <div class="absolute inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -left-10 top-10 h-40 w-40 bg-white/30 rounded-full blur-3xl" />
            <div class="absolute right-0 bottom-10 h-48 w-48 bg-orange-400/20 rounded-full blur-3xl" />
        </div>

        <div class="relative mx-auto max-w-4xl px-4 py-8 space-y-6">
            <div class="flex justify-end">
                <Link
                    :href="route('admin.challenges.index')"
                    class="inline-flex items-center gap-2 rounded-full bg-white/80 px-4 py-2 text-sm font-semibold text-slate-800 shadow ring-1 ring-white/60 hover:-translate-y-0.5 transition"
                >
                    Area admin
                    <span aria-hidden="true">↗</span>
                </Link>
            </div>
            <header class="text-center space-y-2">
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">
                    Gioca al volo con gli amici
                </p>
                <h1 class="text-3xl font-black text-slate-900 sm:text-4xl">
                    Host o unisciti e via di sfide!
                </h1>
                <p class="text-slate-600">
                    Crea una partita lampo, invia il codice e tieni tutti sincronizzati anche se ricaricano la pagina.
                </p>
            </header>

            <div class="grid gap-6 md:grid-cols-2">
                <section class="rounded-3xl bg-white/80 shadow-xl shadow-orange-200/50 ring-1 ring-white/60 backdrop-blur p-6 space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold text-slate-900">Hosta una partita</h2>
                        <span class="text-xs px-3 py-1 rounded-full bg-orange-100 text-orange-700 font-semibold">Nuova</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="text-sm font-semibold text-slate-700">Nome host</label>
                        <input v-model="hostForm.host_name" type="text" class="col-span-2 rounded-xl border-slate-200 bg-slate-50 px-3 py-2 focus:border-orange-400 focus:ring-orange-300" placeholder="Tipo: Capitan Volpe" />

                        <label class="col-span-2 text-sm font-semibold text-slate-700">Avatar</label>
                        <div class="col-span-2 flex gap-2 flex-wrap">
                            <button
                                v-for="avatar in avatars"
                                :key="avatar"
                                type="button"
                                @click="hostForm.avatar_url = avatar"
                                class="h-12 w-12 rounded-full border-2 flex items-center justify-center text-lg transition hover:-translate-y-0.5"
                                :class="hostForm.avatar_url === avatar ? 'border-orange-500 bg-orange-50' : 'border-transparent bg-white shadow'"
                            >
                                {{ avatar }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-sm text-slate-700">
                        <div>
                            <div class="flex justify-between items-center">
                                <span>Totale turni</span>
                                <span class="font-semibold text-slate-900">
                                    {{ hostForm.total_turns === 0 ? '∞' : hostForm.total_turns }}
                                </span>
                            </div>
                            <input v-model.number="hostForm.total_turns" type="range" min="0" max="40" class="w-full accent-orange-500" />
                            <p class="text-[11px] text-slate-500 mt-1">Porta a 0 per partita infinita.</p>
                        </div>
                        <div>
                            <div class="flex justify-between">
                                <span>Difficoltà iniziale</span>
                                <span class="font-semibold text-slate-900">{{ hostForm.starting_difficulty }}</span>
                            </div>
                            <input v-model.number="hostForm.starting_difficulty" type="range" min="1" max="5" class="w-full accent-orange-500" />
                        </div>
                        <div>
                            <div class="flex justify-between">
                                <span>Incremento ogni N turni</span>
                                <span class="font-semibold text-slate-900">{{ hostForm.difficulty_step_turns }}</span>
                            </div>
                            <input v-model.number="hostForm.difficulty_step_turns" type="range" min="1" max="10" class="w-full accent-orange-500" />
                        </div>
                        <div>
                            <div class="flex justify-between">
                                <span>Numero prove proposte</span>
                                <span class="font-semibold text-slate-900">{{ hostForm.candidate_count }}</span>
                            </div>
                            <input v-model.number="hostForm.candidate_count" type="range" min="1" max="5" class="w-full accent-orange-500" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-slate-700">Categorie</span>
                            <span class="text-xs text-slate-500">Puoi lasciarle tutte selezionate</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="cat in categories"
                                :key="cat.id"
                                type="button"
                                @click="toggleCategory(cat.id)"
                                class="px-3 py-1 rounded-full text-sm font-semibold border transition"
                                :style="cat.color ? `background:${cat.color}1a;border-color:${cat.color}` : ''"
                                :class="hostForm.category_ids.includes(cat.id) ? 'border-slate-900/10 shadow-sm' : 'border-slate-200 text-slate-500 bg-white'"
                            >
                                {{ cat.name }}
                            </button>
                            <p v-if="!categories.length" class="text-sm text-slate-500">Nessuna categoria ancora: aggiungile dalla sezione admin.</p>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="hostGame"
                        class="w-full rounded-xl bg-gradient-to-r from-orange-500 to-pink-500 px-4 py-3 text-white font-bold shadow-lg shadow-orange-300/50 transition hover:-translate-y-0.5 disabled:opacity-50"
                        :disabled="loadingHost"
                    >
                        {{ loadingHost ? 'Creo la stanza...' : 'Crea partita e ottieni il codice' }}
                    </button>
                </section>

                <section class="rounded-3xl bg-slate-900 text-white p-6 space-y-4 shadow-xl shadow-slate-900/30 ring-1 ring-white/10">
                    <div class="flex items-center justify-between">
                        <h2 class="text-xl font-semibold">Unisciti con un codice</h2>
                        <span class="text-xs px-3 py-1 rounded-full bg-white/10">Ready</span>
                    </div>

                    <label class="text-sm font-semibold">Codice partita</label>
                    <input
                        v-model="joinForm.code"
                        type="text"
                        class="w-full rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-white placeholder:text-white/60 focus:border-orange-200 focus:ring-orange-200 uppercase tracking-[0.2em]"
                        placeholder="ABC12"
                    />

                    <label class="text-sm font-semibold">Il tuo nome</label>
                    <input
                        v-model="joinForm.name"
                        type="text"
                        class="w-full rounded-xl border border-white/10 bg-white/10 px-3 py-2 text-white placeholder:text-white/60 focus:border-orange-200 focus:ring-orange-200"
                        placeholder="Tipo: Panda Ninja"
                    />

                    <div class="space-y-2">
                        <span class="text-sm font-semibold">Avatar</span>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="avatar in avatars"
                                :key="`join-${avatar}`"
                                type="button"
                                @click="joinForm.avatar_url = avatar"
                                class="h-12 w-12 rounded-full border-2 flex items-center justify-center text-lg transition hover:-translate-y-0.5"
                                :class="joinForm.avatar_url === avatar ? 'border-orange-300 bg-white/10' : 'border-white/10 bg-white/5'"
                            >
                                {{ avatar }}
                            </button>
                        </div>
                    </div>

                    <button
                        type="button"
                        @click="joinGame"
                        class="w-full rounded-xl bg-white text-slate-900 px-4 py-3 font-bold shadow-lg shadow-orange-400/30 transition hover:-translate-y-0.5 disabled:opacity-50"
                        :disabled="loadingJoin"
                    >
                        {{ loadingJoin ? 'Entro...' : 'Entra nella partita' }}
                    </button>

                    <p class="text-xs text-white/70">
                        Manteniamo il token localmente per farti rientrare se ricarichi o se cade la connessione.
                    </p>
                </section>
            </div>

            <div v-if="feedback" class="rounded-xl bg-green-100 border border-green-200 text-green-800 px-4 py-3">
                {{ feedback }}
            </div>
            <div v-if="error" class="rounded-xl bg-red-50 border border-red-200 text-red-800 px-4 py-3">
                {{ error }}
            </div>
        </div>
    </div>
</template>
