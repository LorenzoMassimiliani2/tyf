<script setup>
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import { reactive, ref } from 'vue';

const avatars = ['🦊', '🐼', '🤖', '👾', '🌟', '⚡', '🎲', '🚀', '🎨', '🍕', '🏓', '🎧', '🧊', '🛸'];

const form = reactive({
    code: '',
    name: '',
    avatar_url: avatars[1],
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
        const code = form.code.trim().toUpperCase();
        const { data } = await axios.post(`/api/games/${code}/join`, {
            name: form.name,
            avatar_url: form.avatar_url,
        });
        saveToken(data.game.code, data.player.token);
        feedback.value = 'Sei dentro! Ti porto alla lobby...';
        goToGame(code);
    } catch (e) {
        handleError(e);
    } finally {
        loading.value = false;
    }
};
</script>

<template>
    <Head title="Unisciti a una partita" />
    <div class="min-h-screen bg-gradient-to-b from-orange-50 via-amber-50 to-orange-100">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-16 right-6 h-28 w-28 bg-orange-400/30 rounded-full blur-3xl" />
            <div class="absolute bottom-8 left-6 h-32 w-32 bg-orange-200/30 rounded-full blur-3xl" />
        </div>

        <div class="relative mx-auto max-w-xl px-4 py-12 space-y-8">
            <div class="flex items-center justify-between">
                <Link :href="route('home')" class="text-sm font-semibold text-orange-700">← Home</Link>
                <span class="rounded-full bg-orange-500/90 px-3 py-1 text-xs font-semibold text-white">Join</span>
            </div>

            <header class="space-y-2">
                <h1 class="text-3xl font-black text-slate-900">Entra in una partita</h1>
                <p class="text-sm text-slate-700">Inserisci il codice e il tuo nome.</p>
            </header>

            <div class="space-y-5 rounded-3xl bg-white px-5 py-5 shadow-lg shadow-orange-200/70 ring-1 ring-orange-100">
                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-800">Codice partita</label>
                    <input
                        v-model="form.code"
                        type="text"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-orange-400 focus:ring-orange-200 uppercase tracking-[0.25em]"
                        placeholder="ABC12"
                    />
                </div>

                <div class="space-y-2">
                    <label class="text-sm font-semibold text-slate-800">Il tuo nome</label>
                    <input
                        v-model="form.name"
                        type="text"
                        class="w-full rounded-2xl border border-slate-200 px-4 py-3 text-slate-900 placeholder:text-slate-400 focus:border-orange-400 focus:ring-orange-200"
                    />
                </div>

                <div class="space-y-3">
                    <span class="text-sm font-semibold text-slate-800">Avatar</span>
                    <div class="flex flex-wrap gap-2">
                        <button
                            v-for="avatar in avatars"
                            :key="avatar"
                            type="button"
                            @click="form.avatar_url = avatar"
                            class="h-12 w-12 rounded-full border-2 flex items-center justify-center text-lg transition"
                            :class="form.avatar_url === avatar ? 'border-orange-400 bg-orange-50' : 'border-slate-200 bg-white'"
                        >
                            {{ avatar }}
                        </button>
                    </div>
                </div>

                <button
                    type="button"
                    @click="submit"
                    class="w-full rounded-2xl bg-gradient-to-r from-orange-500 to-orange-400 px-4 py-3 text-lg font-bold text-white shadow-lg shadow-orange-200/70 transition hover:-translate-y-0.5 disabled:opacity-50"
                    :disabled="loading"
                >
                    {{ loading ? 'Entro...' : 'Entra nella partita' }}
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
