<script setup>
import { Head, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive } from 'vue';

const page = usePage();
const categories = computed(() => page.props.categories || []);
const challenges = computed(() => page.props.challenges || []);

const form = reactive({
    category_id: categories.value[0]?.id,
    title: '',
    description: '',
    level: 1,
    is_active: true,
});

const submit = async () => {
    await axios.post(route('admin.challenges.store'), form);
    form.title = '';
    form.description = '';
    router.reload({ only: ['challenges'] });
};

const updateChallenge = async (challenge) => {
    await axios.patch(route('admin.challenges.update', challenge.id), challenge);
    router.reload({ only: ['challenges'] });
};

const deleteChallenge = async (challenge) => {
    if (!confirm('Eliminare questa prova?')) return;
    await axios.delete(route('admin.challenges.destroy', challenge.id));
    router.reload({ only: ['challenges'] });
};
</script>

<template>
    <Head title="Prove" />
    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Backoffice</p>
                <h1 class="text-2xl font-black text-slate-900">Prove</h1>
                <p class="text-slate-600">Inserisci prove con livello e categoria.</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-3">
                <h2 class="text-lg font-bold text-slate-900">Nuova prova</h2>
                <div class="grid gap-3 sm:grid-cols-4">
                    <select
                        v-model.number="form.category_id"
                        class="rounded-xl border border-slate-200 px-3 py-2 focus:border-orange-400 focus:ring-orange-300 sm:col-span-2"
                    >
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                            {{ cat.name }}
                        </option>
                    </select>
                    <input
                        v-model="form.title"
                        type="text"
                        placeholder="Titolo"
                        class="rounded-xl border border-slate-200 px-3 py-2 focus:border-orange-400 focus:ring-orange-300 sm:col-span-2"
                    />
                    <textarea
                        v-model="form.description"
                        placeholder="Descrizione"
                        class="sm:col-span-4 rounded-xl border border-slate-200 px-3 py-2 focus:border-orange-400 focus:ring-orange-300"
                        rows="2"
                    />
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        Livello
                        <input v-model.number="form.level" type="number" min="1" max="5" class="w-20 rounded-lg border border-slate-200 px-2 py-1" />
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" />
                        Attiva
                    </label>
                    <button
                        type="button"
                        class="sm:col-span-2 rounded-xl bg-slate-900 px-4 py-2 font-semibold text-white hover:-translate-y-0.5 transition"
                        @click="submit"
                    >
                        Salva prova
                    </button>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-3">
                <h2 class="text-lg font-bold text-slate-900">Elenco prove</h2>
                <div class="space-y-2">
                    <div
                        v-for="item in challenges"
                        :key="item.id"
                        class="rounded-xl border border-slate-200 px-4 py-3 flex flex-col gap-2"
                    >
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <select v-model.number="item.category_id" class="rounded-lg border border-slate-200 px-2 py-1">
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                </select>
                                <input v-model="item.title" class="text-lg font-semibold text-slate-900 bg-transparent border-none focus:ring-orange-400" />
                                <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Lvl {{ item.level }}</span>
                                <label class="flex items-center gap-1 text-sm text-slate-700">
                                    <input type="checkbox" v-model="item.is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" />
                                    Attiva
                                </label>
                            </div>
                            <div class="flex items-center gap-2">
                                <button class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white" @click="updateChallenge({ ...item })">
                                    Aggiorna
                                </button>
                                <button class="text-xs font-semibold text-red-600" @click="deleteChallenge(item)">Elimina</button>
                            </div>
                        </div>
                        <textarea
                            v-model="item.description"
                            class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-700 focus:ring-orange-400"
                            rows="2"
                        />
                    </div>
                    <p v-if="!challenges.length" class="text-sm text-slate-500">Nessuna prova ancora.</p>
                </div>
            </div>
        </div>
    </div>
</template>
