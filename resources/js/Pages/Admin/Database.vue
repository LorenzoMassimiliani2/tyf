<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

const page = usePage();
const db = computed(() => page.props.db || {});
const queryResult = computed(() => page.props.query_result || null);
const queryError = computed(() => page.props.query_error || null);
const querySql = computed(() => page.props.query_sql || '');

const form = reactive({
    sql: querySql.value || 'SELECT * FROM users LIMIT 5;',
});

const submit = async () => {
    router.post(
        route('admin.db.query'),
        { sql: form.sql },
        { preserveScroll: true, preserveState: true, replace: true }
    );
};
</script>

<template>
    <Head title="DB Tools" />
    <div class="min-h-screen bg-slate-100 py-10">
        <div class="mx-auto max-w-5xl space-y-6 px-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-orange-600">Backoffice</p>
                    <h1 class="text-2xl font-black text-slate-900">DB Tools</h1>
                    <p class="text-slate-600">Info sul database e console SELECT veloce.</p>
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
                        class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-slate-700 shadow hover:-translate-y-0.5 transition"
                    >
                        Sessioni
                    </Link>
                    <Link
                        :href="route('admin.db.index')"
                        class="rounded-full bg-[#1c2d4f] px-3 py-1 text-sm font-semibold text-white shadow hover:-translate-y-0.5 transition"
                    >
                        DB
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-4">
                <h2 class="text-lg font-bold text-slate-900">Info DB</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase text-slate-500 font-semibold">Driver</p>
                        <p class="text-base font-semibold text-slate-900">{{ db.driver }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase text-slate-500 font-semibold">Database</p>
                        <p class="text-base font-semibold text-slate-900">{{ db.database }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                        <p class="text-[11px] uppercase text-slate-500 font-semibold">Dimensione stimata</p>
                        <p class="text-base font-semibold text-slate-900">{{ db.size_human }}</p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl bg-white p-5 shadow space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-lg font-bold text-slate-900">Console SELECT (read-only)</h2>
                        <p class="text-sm text-slate-600">Solo SELECT. I risultati vengono mostrati sotto.</p>
                    </div>
                    <span class="text-xs rounded-full bg-orange-100 px-3 py-1 font-semibold text-orange-700">Safe</span>
                </div>
                <form class="space-y-3" @submit.prevent="submit">
                    <textarea
                        v-model="form.sql"
                        rows="4"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2 font-mono text-sm text-slate-800 focus:border-orange-400 focus:ring-orange-300"
                        placeholder="SELECT * FROM users LIMIT 10;"
                    />
                    <button
                        type="submit"
                        class="rounded-xl bg-[#1c2d4f] px-4 py-2 font-semibold text-white hover:-translate-y-0.5 transition"
                    >
                        Esegui
                    </button>
                </form>

                <div v-if="queryError" class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-red-800 text-sm">
                    {{ queryError }}
                </div>
                <div v-if="queryResult" class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                    <p class="text-xs font-semibold text-slate-600 mb-2">Risultati (max {{ queryResult.length }} righe)</p>
                    <div class="overflow-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr>
                                    <th
                                        v-for="(val, key) in (queryResult[0] || {})"
                                        :key="key"
                                        class="border-b border-slate-200 bg-white px-2 py-1 text-left font-semibold text-slate-700"
                                    >
                                        {{ key }}
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(row, idx) in queryResult" :key="idx" class="odd:bg-white even:bg-slate-100/80">
                                    <td
                                        v-for="(val, key) in row"
                                        :key="key"
                                        class="px-2 py-1 align-top text-slate-800"
                                    >
                                        {{ val }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
