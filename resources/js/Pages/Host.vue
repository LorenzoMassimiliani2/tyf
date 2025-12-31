<script setup>
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

const categories = computed(() => props.categories ?? []);

const avatars = ['🦊', '🐼', '🤖', '👾', '🌟', '⚡', '🎲', '🚀', '🎨', '🍕', '🏓', '🎧', '🧊', '🛸'];

const form = reactive({
    host_name: '',
    avatar_url: avatars[0],
    total_turns: 12,
    starting_difficulty: 1,
    difficulty_step_turns: 3,
    candidate_count: 3,
    category_ids: props.categories.map((c) => c.id),
});

const loading = ref(false);
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

const submit = async () => {
    loading.value = true;
    feedback.value = '';
    error.value = '';
    try {
        const { data } = await axios.post('/api/games', form);
        saveToken(data.game.code, data.player.token);
        feedback.value = 'Stanza creata! Ti porto alla lobby...';
        goToGame(data.game.code);
    } catch (e) {
        handleError(e);
    } finally {
        loading.value = false;
    }
};

const toggleCategory = (id) => {
    if (form.category_ids.includes(id)) {
        form.category_ids = form.category_ids.filter((c) => c !== id);
    } else {
        form.category_ids.push(id);
    }
};
</script>

<template>
    <Head title="Crea partita" />
    <div class="min-h-screen bg-[#1c2d4f] text-white">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-24 left-6 h-24 w-24 bg-orange-500/30 rounded-full blur-3xl" />
            <div class="absolute bottom-10 right-4 h-32 w-32 bg-amber-400/25 rounded-full blur-3xl" />
        </div>

        <div class="relative mx-auto max-w-xl px-4 py-12 space-y-8">
            <div class="flex items-center justify-between">
                <Link :href="route('home')" class="text-sm font-semibold text-white/80">← Home</Link>
                <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-semibold">Host</span>
            </div>

            <header class="space-y-2">
                <h1 class="text-3xl font-black">Crea una partita</h1>
                <p class="text-sm text-white/70">Imposta nome, avatar e difficoltà.</p>
            </header>

            <div class="space-y-5 rounded-3xl bg-white/5 p-5 shadow-lg shadow-black/30 ring-1 ring-white/10 backdrop-blur">
                <div class="space-y-2">
                    <label class="text-sm font-semibold">Nome host</label>
                    <input v-model="form.host_name" type="text" class="w-full rounded-2xl bg-white/10 px-4 py-3 text-white placeholder:text-white/60 focus:border-orange-300 focus:ring-orange-200" />
                </div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold">Scegli un avatar</span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="avatar in avatars"
                            :key="avatar"
                            type="button"
                            @click="form.avatar_url = avatar"
                            class="h-12 w-12 rounded-full border-2 flex items-center justify-center text-lg transition"
                            :class="form.avatar_url === avatar ? 'border-orange-300 bg-white/10' : 'border-white/10 bg-white/5'"
                        >
                            {{ avatar }}
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 text-sm">
                    <div>
                        <div class="flex justify-between">
                            <span>Totale turni</span>
                            <span class="font-semibold">{{ form.total_turns === 0 ? '∞' : form.total_turns }}</span>
                        </div>
                        <input v-model.number="form.total_turns" type="range" min="0" max="40" class="w-full accent-orange-400" />
                        <p class="text-[11px] text-white/60 mt-1">Metti 0 per una partita senza fine.</p>
                    </div>
                    <div>
                        <div class="flex justify-between">
                            <span>Difficoltà iniziale</span>
                            <span class="font-semibold">{{ form.starting_difficulty }}</span>
                        </div>
                        <input v-model.number="form.starting_difficulty" type="range" min="1" max="5" class="w-full accent-orange-400" />
                    </div>
                    <div>
                        <div class="flex justify-between">
                            <span>Incremento ogni N turni</span>
                            <span class="font-semibold">{{ form.difficulty_step_turns }}</span>
                        </div>
                        <input v-model.number="form.difficulty_step_turns" type="range" min="1" max="10" class="w-full accent-orange-400" />
                    </div>
                    <div>
                        <div class="flex justify-between">
                            <span>Numero prove proposte</span>
                            <span class="font-semibold">{{ form.candidate_count }}</span>
                        </div>
                        <input v-model.number="form.candidate_count" type="range" min="1" max="5" class="w-full accent-orange-400" />
                    </div>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-semibold">Categorie</span>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="cat in categories"
                            :key="cat.id"
                            type="button"
                            @click="toggleCategory(cat.id)"
                            class="px-3 py-1 rounded-full text-sm font-semibold border transition"
                            :style="cat.color ? `background:${cat.color}1a;border-color:${cat.color}` : ''"
                            :class="form.category_ids.includes(cat.id) ? 'border-white/20 text-white bg-white/10' : 'border-white/10 text-white/70 bg-white/5'"
                        >
                            {{ cat.name }}
                        </button>
                        <p v-if="!categories.length" class="text-sm text-white/60">Nessuna categoria ancora: aggiungile dalla sezione admin.</p>
                    </div>
                </div>

                <button
                    type="button"
                    @click="submit"
                    class="w-full rounded-2xl bg-gradient-to-r from-orange-400 to-pink-500 px-4 py-3 text-lg font-bold text-white shadow-lg shadow-orange-500/40 transition hover:-translate-y-0.5 disabled:opacity-50"
                    :disabled="loading"
                >
                    {{ loading ? 'Creo la stanza...' : 'Crea e ottieni il codice' }}
                </button>

                <div v-if="feedback" class="rounded-xl bg-green-100 text-green-900 px-4 py-3 text-sm">
                    {{ feedback }}
                </div>
                <div v-if="error" class="rounded-xl bg-red-100 text-red-900 px-4 py-3 text-sm">
                    {{ error }}
                </div>
            </div>
        </div>
    </div>
</template>
