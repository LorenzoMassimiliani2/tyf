<script setup>
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
import { computed } from 'vue';

const page = usePage();
const categories = computed(() => page.props.categories || []);

const form = useForm({
    name: '',
    color: '#f97316',
    is_active: true,
});

const submit = () => {
    form.post(route('admin.categories.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset('name'),
    });
};

const updateCategory = async (category) => {
    await axios.patch(route('admin.categories.update', category.id), category);
    router.reload({ only: ['categories'] });
};

const deleteCategory = async (category) => {
    if (!confirm('Vuoi eliminare questa categoria?')) return;
    await axios.delete(route('admin.categories.destroy', category.id));
    router.reload({ only: ['categories'] });
};
</script>

<template>
    <Head title="Categorie" />
    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-4xl space-y-6 px-4">
            <div>
                <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Backoffice</p>
                <h1 class="text-2xl font-black text-slate-900">Categorie</h1>
                <p class="text-slate-600">Gestisci le categorie per le prove.</p>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow">
                <h2 class="text-lg font-bold text-slate-900">Nuova categoria</h2>
                <form class="mt-4 grid gap-3 sm:grid-cols-3" @submit.prevent="submit">
                    <input
                        v-model="form.name"
                        type="text"
                        placeholder="Nome"
                        class="rounded-xl border border-slate-200 px-3 py-2 focus:border-orange-400 focus:ring-orange-300 sm:col-span-1"
                    />
                    <input
                        v-model="form.color"
                        type="text"
                        placeholder="#f97316"
                        class="rounded-xl border border-slate-200 px-3 py-2 focus:border-orange-400 focus:ring-orange-300 sm:col-span-1"
                    />
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" v-model="form.is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" />
                        Attiva
                    </label>
                    <button
                        type="submit"
                        class="sm:col-span-3 rounded-xl bg-slate-900 px-4 py-2 font-semibold text-white hover:-translate-y-0.5 transition"
                    >
                        Salva
                    </button>
                </form>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-3">
                <h2 class="text-lg font-bold text-slate-900">Elenco</h2>
                <div class="grid gap-3">
                    <div
                        v-for="cat in categories"
                        :key="cat.id"
                        class="rounded-xl border border-slate-200 px-4 py-3 flex flex-wrap items-center gap-3 justify-between"
                    >
                        <div class="flex items-center gap-3">
                            <span class="h-8 w-8 rounded-full" :style="`background:${cat.color || '#f97316'}`" />
                            <div>
                                <input
                                    v-model="cat.name"
                                    class="text-lg font-semibold text-slate-900 bg-transparent border-none focus:ring-orange-400"
                                />
                                <input
                                    v-model="cat.color"
                                    class="text-sm text-slate-500 bg-transparent border-none focus:ring-orange-400"
                                    placeholder="#hex"
                                />
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="flex items-center gap-1 text-sm text-slate-700">
                                <input type="checkbox" v-model="cat.is_active" class="rounded border-slate-300 text-orange-500 focus:ring-orange-400" />
                                Attiva
                            </label>
                            <button
                                class="rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white"
                                @click="updateCategory({ ...cat })"
                            >
                                Aggiorna
                            </button>
                            <button class="text-xs font-semibold text-red-600" @click="deleteCategory(cat)">Elimina</button>
                        </div>
                    </div>
                    <p v-if="!categories.length" class="text-sm text-slate-500">Ancora nessuna categoria.</p>
                </div>
            </div>
        </div>
    </div>
</template>
