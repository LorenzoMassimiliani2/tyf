<script setup>
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed, reactive, ref } from 'vue';

const page = usePage();
const categories = computed(() => page.props.categories || []);
const challenges = computed(() => page.props.challenges || []);
const stats = computed(() => page.props.stats || []);
const initialFilters = page.props.filters || {};
const levels = [1, 2, 3, 4, 5];
const totalChallenges = computed(() => stats.value.reduce((sum, item) => sum + item.total, 0));
const showCoverage = ref(false);

const form = reactive({
    category_id: categories.value[0]?.id,
    title: '',
    description: '',
    level: 1,
    is_active: true,
});

const filters = reactive({
    category_id: initialFilters.category_id || '',
    level: initialFilters.level || '',
});

const statsByCategory = computed(() => {
    const map = {};
    categories.value.forEach((cat) => {
        map[cat.id] = { ...cat, total: 0, levels: {} };
    });
    stats.value.forEach((stat) => {
        if (!map[stat.category_id]) return;
        map[stat.category_id].total += stat.total;
        map[stat.category_id].levels[stat.level] = stat.total;
    });
    return Object.values(map);
});

const groupedChallenges = computed(() => {
    const groups = {};
    challenges.value.forEach((item) => {
        const level = item.level;
        const catId = item.category_id;
        if (!groups[level]) groups[level] = {};
        if (!groups[level][catId]) groups[level][catId] = [];
        groups[level][catId].push(item);
    });

    return Object.entries(groups)
        .sort(([a], [b]) => Number(a) - Number(b))
        .map(([level, cats]) => ({
            level: Number(level),
            categories: Object.entries(cats)
                .sort(([a], [b]) => Number(a) - Number(b))
                .map(([catId, items]) => ({
                    category: categories.value.find((c) => c.id === Number(catId)),
                    items,
                })),
        }));
});

const applyFilters = () => {
    router.get(
        route('admin.challenges.index'),
        {
            category_id: filters.category_id || undefined,
            level: filters.level || undefined,
        },
        { preserveState: true, preserveScroll: true, replace: true }
    );
};

const resetFilters = () => {
    filters.category_id = '';
    filters.level = '';
    applyFilters();
};

const submit = async () => {
    await axios.post(route('admin.challenges.store'), form);
    form.title = '';
    form.description = '';
    router.reload({ only: ['challenges', 'stats'] });
};

const updateChallenge = async (challenge) => {
    await axios.patch(route('admin.challenges.update', challenge.id), challenge);
    router.reload({ only: ['challenges', 'stats'] });
};

const deleteChallenge = async (challenge) => {
    if (!confirm('Eliminare questa prova?')) return;
    if (!challenge?.id) {
        alert('ID prova mancante, impossibile eliminare.');
        return;
    }
    await axios.delete(`/admin/challenges/${challenge.id}`);
    router.reload({ only: ['challenges', 'stats'] });
};
</script>

<template>
    <Head title="Prove" />
    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Backoffice</p>
                    <h1 class="text-2xl font-black text-slate-900">Prove</h1>
                    <p class="text-slate-600">Inserisci prove con livello e categoria.</p>
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
                        class="rounded-full bg-[#1c2d4f] px-3 py-1 text-sm font-semibold text-white shadow hover:-translate-y-0.5 transition"
                    >
                        Prove
                    </Link>
                    <Link
                        :href="route('admin.sessions.index')"
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                    >
                        Sessioni
                    </Link>
                    <Link
                        :href="route('admin.db.index')"
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                    >
                        DB
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Copertura prove</h2>
                        <p class="text-sm text-slate-600">Numero prove per combinazione livello e categoria.</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-semibold rounded-full bg-orange-100 px-3 py-1 text-orange-700">
                            Totale {{ totalChallenges }}
                        </span>
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 hover:-translate-y-0.5 transition"
                            @click="showCoverage = !showCoverage"
                        >
                            {{ showCoverage ? 'Nascondi' : 'Mostra' }}
                        </button>
                    </div>
                </div>
                <div v-show="showCoverage" class="space-y-3">
                    <div
                        v-for="cat in statsByCategory"
                        :key="cat.id"
                        class="rounded-xl border border-slate-100 px-4 py-3 bg-slate-50/60"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="h-8 w-8 rounded-full border" :style="`background:${cat.color || '#f97316'}20;border-color:${cat.color || '#f97316'}`" />
                                <div class="font-semibold text-slate-900">{{ cat.name }}</div>
                            </div>
                            <span class="text-xs font-semibold text-slate-600">Tot {{ cat.total }}</span>
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span
                                v-for="level in levels"
                                :key="`stat-${cat.id}-${level}`"
                                class="rounded-full px-3 py-1 text-xs font-semibold border"
                                :class="(cat.levels[level] || 0) === 0 ? 'border-slate-200 text-slate-400 bg-white' : 'border-orange-200 bg-orange-50 text-orange-700'"
                            >
                                Lvl {{ level }}: {{ cat.levels[level] || 0 }}
                            </span>
                        </div>
                    </div>
                </div>
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
                        class="sm:col-span-2 rounded-xl bg-[#1c2d4f] px-4 py-2 font-semibold text-white hover:-translate-y-0.5 transition"
                        @click="submit"
                    >
                        Salva prova
                    </button>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-3">
                <h2 class="text-lg font-bold text-slate-900">Elenco prove</h2>
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-600">Categoria</label>
                        <select v-model="filters.category_id" class="rounded-lg border border-slate-200 px-3 py-2 min-w-[180px]">
                            <option value="">Tutte</option>
                            <option v-for="cat in categories" :key="cat.id" :value="String(cat.id)">{{ cat.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-semibold text-slate-600">Livello</label>
                        <select v-model="filters.level" class="rounded-lg border border-slate-200 px-3 py-2 min-w-[140px]">
                            <option value="">Tutti</option>
                            <option v-for="level in levels" :key="`filter-level-${level}`" :value="String(level)">Livello {{ level }}</option>
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button
                            type="button"
                            class="rounded-xl bg-[#1c2d4f] px-4 py-2 font-semibold text-white hover:-translate-y-0.5 transition"
                            @click="applyFilters"
                        >
                            Applica filtri
                        </button>
                        <button
                            type="button"
                            class="rounded-xl border border-slate-200 px-4 py-2 font-semibold text-slate-700 hover:-translate-y-0.5 transition"
                            @click="resetFilters"
                        >
                            Azzera
                        </button>
                    </div>
                </div>

                <div class="space-y-4">
                    <div v-for="group in groupedChallenges" :key="`lvl-${group.level}`" class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-700">Livello {{ group.level }}</span>
                            <div class="h-px flex-1 bg-slate-200" />
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div
                                v-for="catGroup in group.categories"
                                :key="`lvl-${group.level}-cat-${catGroup.category?.id || 'none'}`"
                                class="rounded-xl border border-slate-200 bg-white px-4 py-3 space-y-2"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="h-8 w-8 rounded-full border"
                                            :style="`background:${catGroup.category?.color || '#f97316'}20;border-color:${catGroup.category?.color || '#f97316'}`"
                                        />
                                        <div class="font-semibold text-slate-900">
                                            {{ catGroup.category?.name || 'Senza categoria' }}
                                        </div>
                                    </div>
                                    <span class="text-xs text-slate-500">{{ catGroup.items.length }} prova/e</span>
                                </div>
                                <div class="space-y-2">
                                    <div
                                        v-for="item in catGroup.items"
                                        :key="item.id"
                                        class="rounded-lg border border-slate-100 px-3 py-2 flex flex-col gap-2"
                                    >
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <select v-model.number="item.category_id" class="rounded-lg border border-slate-200 px-2 py-1 text-sm">
                                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                                                </select>
                                                <input v-model="item.title" class="text-sm font-semibold text-slate-900 bg-transparent border-none focus:ring-orange-400" />
                                                <label class="flex items-center gap-1 text-xs text-slate-700">
                                                    <input type="checkbox" v-model="item.is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" />
                                                    Attiva
                                                </label>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button class="rounded-full bg-[#1c2d4f] px-3 py-1 text-xs font-semibold text-white" @click="updateChallenge({ ...item })">
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
                                </div>
                            </div>
                        </div>
                    </div>
                    <p v-if="!groupedChallenges.length" class="text-sm text-slate-500">Nessuna prova trovata con questi filtri.</p>
                </div>
            </div>
        </div>
    </div>
</template>
