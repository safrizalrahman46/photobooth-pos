<script setup>
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    initialStats: {
        type: Array,
        default: () => [],
    },
    initialRows: {
        type: Array,
        default: () => [],
    },
    initialPagination: {
        type: Object,
        default: () => ({
            current_page: 1,
            per_page: 15,
            total: 0,
            last_page: 1,
        }),
    },
    dataUrl: {
        type: String,
        default: '',
    },
});

const search = ref('');
const filterStatus = ref('all');
const expandedId = ref(null);
const loading = ref(false);
const rows = ref(Array.isArray(props.initialRows) ? props.initialRows : []);

const pagination = ref({
    current_page: Number(props.initialPagination?.current_page || 1),
    per_page: Number(props.initialPagination?.per_page || 15),
    total: Number(props.initialPagination?.total || rows.value.length || 0),
    last_page: Number(props.initialPagination?.last_page || 1),
});

let debounceTimer = null;
let activeRequestController = null;

const filters = [
    { key: 'all', label: 'Semua' },
    { key: 'pending', label: 'pending' },
    { key: 'booked', label: 'booked' },
    { key: 'used', label: 'used' },
    { key: 'expired', label: 'expired' },
];

const statusClassMap = {
    pending: 'bg-[#F59E0B]/10 text-[#F59E0B] border-[#F59E0B]/20',
    booked: 'bg-[#2563EB]/10 text-[#2563EB] border-[#2563EB]/20',
    used: 'bg-gray-100 text-gray-500 border-gray-200',
    expired: 'bg-[#EF4444]/10 text-[#EF4444] border-[#EF4444]/20',
};

const statusDotClassMap = {
    pending: 'bg-[#F59E0B]',
    booked: 'bg-[#2563EB]',
    used: 'bg-gray-400',
    expired: 'bg-[#EF4444]',
};

const normalizedRows = computed(() => {
    return (rows.value || []).map((row) => {
        const addOns = Array.isArray(row.add_ons) ? row.add_ons : [];

        return {
            ...row,
            add_ons: addOns,
            add_ons_count: Number(row.add_ons_count || addOns.length || 0),
            add_ons_total: Number(row.add_ons_total || 0),
        };
    });
});

const hasPagination = computed(() => Number(pagination.value.last_page || 1) > 1);
const canGoPrev = computed(() => Number(pagination.value.current_page || 1) > 1);
const canGoNext = computed(() => Number(pagination.value.current_page || 1) < Number(pagination.value.last_page || 1));

const fetchRows = async (page = 1) => {
    if (!props.dataUrl) {
        return;
    }

    if (activeRequestController) {
        activeRequestController.abort();
    }

    const controller = new AbortController();
    activeRequestController = controller;
    loading.value = true;

    try {
        const params = new URLSearchParams();
        const trimmedSearch = String(search.value || '').trim();

        params.set('page', String(page));
        params.set('per_page', String(pagination.value.per_page || 15));
        params.set('status', filterStatus.value || 'all');

        if (trimmedSearch) {
            params.set('search', trimmedSearch);
        }

        const response = await fetch(`${props.dataUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            signal: controller.signal,
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        const data = payload?.data || {};
        const incomingRows = Array.isArray(data.rows) ? data.rows : [];
        const incomingPagination = data.pagination || {};

        rows.value = incomingRows;
        pagination.value = {
            current_page: Number(incomingPagination.current_page || 1),
            per_page: Number(incomingPagination.per_page || 15),
            total: Number(incomingPagination.total || incomingRows.length),
            last_page: Number(incomingPagination.last_page || 1),
        };
    } catch (error) {
        if (error?.name !== 'AbortError') {
            console.error('Failed to fetch dashboard rows:', error);
        }
    } finally {
        if (activeRequestController === controller) {
            activeRequestController = null;
            loading.value = false;
        }
    }
};

const setFilterStatus = (status) => {
    filterStatus.value = status;
    expandedId.value = null;
    fetchRows(1);
};

const toggleExpanded = (row) => {
    if (!row.add_ons_count) {
        return;
    }

    expandedId.value = expandedId.value === row.id ? null : row.id;
};

const statusClasses = (status) => {
    return statusClassMap[status] || 'bg-gray-100 text-gray-500 border-gray-200';
};

const statusDotClasses = (status) => {
    return statusDotClassMap[status] || 'bg-gray-400';
};

const formatRupiah = (amount) => {
    const numeric = Number(amount || 0);
    return `Rp ${numeric.toLocaleString('id-ID')}`;
};

const goToPrevPage = () => {
    if (!canGoPrev.value) {
        return;
    }

    expandedId.value = null;
    fetchRows(Number(pagination.value.current_page || 1) - 1);
};

const goToNextPage = () => {
    if (!canGoNext.value) {
        return;
    }

    expandedId.value = null;
    fetchRows(Number(pagination.value.current_page || 1) + 1);
};

watch(search, () => {
    expandedId.value = null;

    if (debounceTimer) {
        window.clearTimeout(debounceTimer);
    }

    debounceTimer = window.setTimeout(() => {
        fetchRows(1);
    }, 350);
});

onBeforeUnmount(() => {
    if (debounceTimer) {
        window.clearTimeout(debounceTimer);
    }

    if (activeRequestController) {
        activeRequestController.abort();
    }
});
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC] -m-6 px-4 py-8 sm:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-7xl">
            <div class="mb-8 flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-[#2563EB] to-[#3B82F6] text-white shadow-md shadow-[#2563EB]/20">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect x="3" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="14" width="7" height="7" rx="1" />
                        <rect x="3" y="14" width="7" height="7" rx="1" />
                    </svg>
                </div>
                <div>
                    <h1 class="text-[#1F2937]" style="font-size: 1.5rem; font-weight: 700;">Admin Dashboard</h1>
                    <p class="text-sm text-gray-500">Ready to Pict Studio - Kelola booking & aktivitas</p>
                </div>
            </div>

            <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
                <div
                    v-for="(stat, index) in initialStats"
                    :key="`stat-${index}-${stat.label}`"
                    class="rounded-xl border-0 bg-white p-5 shadow-sm ring-1 ring-black/5"
                >
                    <div class="flex items-center gap-4">
                        <div
                            class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                            :style="{ backgroundColor: `${stat.color}15`, color: stat.color }"
                        >
                            <svg
                                v-if="stat.icon === 'calendar'"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M8 2v4" />
                                <path d="M16 2v4" />
                                <rect x="3" y="4" width="18" height="18" rx="2" />
                                <path d="M3 10h18" />
                            </svg>

                            <svg
                                v-else-if="stat.icon === 'camera'"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M2 7h4l2-2h8l2 2h4v13H2z" />
                                <circle cx="12" cy="13" r="3" />
                            </svg>

                            <svg
                                v-else-if="stat.icon === 'users'"
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
                                <circle cx="8.5" cy="7" r="4" />
                                <path d="M20 8v6" />
                                <path d="M23 11h-6" />
                            </svg>

                            <svg
                                v-else
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-6 w-6"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                aria-hidden="true"
                            >
                                <path d="M3 17l6-6 4 4 7-7" />
                                <path d="M14 8h6v6" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500">{{ stat.label }}</p>
                            <p class="text-[#1F2937]" style="font-size: 1.25rem; font-weight: 700;">{{ stat.value }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border-0 bg-white shadow-sm ring-1 ring-black/5">
                <div class="border-b border-gray-50 px-6 py-4">
                    <h2 class="text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">Daftar Booking</h2>
                </div>

                <div class="flex flex-col gap-3 border-b border-gray-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="relative w-full max-w-sm flex-1">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <circle cx="11" cy="11" r="8" />
                            <path d="m21 21-4.3-4.3" />
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Cari nama, ID, atau paket..."
                            class="h-10 w-full rounded-md border border-gray-200 bg-white pl-9 pr-3 text-sm text-gray-700 outline-none transition focus:border-[#2563EB] focus:ring-2 focus:ring-[#2563EB]/20"
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M3 6h18" />
                            <path d="M6 12h12" />
                            <path d="M10 18h4" />
                        </svg>

                        <button
                            v-for="item in filters"
                            :key="item.key"
                            type="button"
                            class="rounded-lg px-3 py-1.5 text-xs capitalize transition-colors"
                            :class="filterStatus === item.key ? 'bg-[#2563EB] text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            @click="setFilterStatus(item.key)"
                        >
                            {{ item.label }}
                        </button>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-50 text-left">
                                <th class="w-10 px-2 py-3"></th>
                                <th class="px-4 py-3 text-xs text-gray-500" style="font-weight: 500;">ID</th>
                                <th class="px-4 py-3 text-xs text-gray-500" style="font-weight: 500;">Nama</th>
                                <th class="hidden px-4 py-3 text-xs text-gray-500 md:table-cell" style="font-weight: 500;">Paket</th>
                                <th class="hidden px-4 py-3 text-xs text-gray-500 sm:table-cell" style="font-weight: 500;">Tanggal</th>
                                <th class="px-4 py-3 text-xs text-gray-500" style="font-weight: 500;">Waktu</th>
                                <th class="px-4 py-3 text-xs text-gray-500" style="font-weight: 500;">Status</th>
                                <th class="hidden px-4 py-3 text-xs text-gray-500 sm:table-cell" style="font-weight: 500;">Bayar</th>
                                <th class="hidden px-4 py-3 text-xs text-gray-500 lg:table-cell" style="font-weight: 500;">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="row in normalizedRows" :key="`row-${row.id}`">
                                <tr
                                    class="border-b border-gray-50 transition-colors"
                                    :class="row.add_ons_count ? 'cursor-pointer hover:bg-gray-50/50' : ''"
                                    @click="toggleExpanded(row)"
                                >
                                    <td class="px-2 py-3 text-center">
                                        <span
                                            v-if="row.add_ons_count"
                                            class="mx-auto flex h-6 w-6 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                                        >
                                            <svg
                                                v-if="expandedId === row.id"
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path d="m18 15-6-6-6 6" />
                                            </svg>
                                            <svg
                                                v-else
                                                xmlns="http://www.w3.org/2000/svg"
                                                class="h-4 w-4"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="2"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                aria-hidden="true"
                                            >
                                                <path d="m6 9 6 6 6-6" />
                                            </svg>
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 text-sm text-[#2563EB]" style="font-weight: 500;">{{ row.id }}</td>

                                    <td class="px-4 py-3 text-sm text-[#1F2937]">
                                        <div class="flex items-center gap-2">
                                            {{ row.name }}
                                            <span
                                                v-if="row.add_ons_count"
                                                class="inline-flex items-center gap-1 rounded-full bg-[#2563EB]/10 px-2 py-0.5 text-[0.65rem] text-[#2563EB]"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M6 2h12l4 7H2z" />
                                                    <path d="M3 9h18v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z" />
                                                </svg>
                                                {{ row.add_ons_count }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="hidden px-4 py-3 text-sm text-gray-600 md:table-cell">{{ row.pkg }}</td>
                                    <td class="hidden px-4 py-3 text-sm text-gray-500 sm:table-cell">{{ row.date }}</td>
                                    <td class="px-4 py-3 text-sm text-[#1F2937]" style="font-weight: 500;">{{ row.time }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs capitalize" :class="statusClasses(row.status)">
                                            <span class="mr-1.5 h-1.5 w-1.5 rounded-full" :class="statusDotClasses(row.status)"></span>
                                            {{ row.status }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-3 text-sm text-gray-500 sm:table-cell">{{ row.payment }}</td>
                                    <td class="hidden px-4 py-3 text-sm text-[#1F2937] lg:table-cell" style="font-weight: 500;">{{ row.amount_text }}</td>
                                </tr>

                                <tr v-if="expandedId === row.id && row.add_ons_count" class="bg-[#F0F7FF]">
                                    <td colspan="9" class="px-4 py-0">
                                        <div class="py-3 pl-8 sm:pl-12">
                                            <p class="mb-2 text-xs text-[#2563EB]" style="font-weight: 600;">Detail Add-on</p>
                                            <div class="space-y-1.5">
                                                <div
                                                    v-for="(addOn, addOnIndex) in row.add_ons"
                                                    :key="`addon-${row.id}-${addOnIndex}`"
                                                    class="flex max-w-md items-center justify-between"
                                                >
                                                    <span class="text-sm text-gray-700">
                                                        {{ addOn.label }} <span class="text-gray-400">x{{ addOn.qty }}</span>
                                                    </span>
                                                    <span class="text-sm text-[#1F2937]" style="font-weight: 500;">
                                                        {{ formatRupiah(addOn.line_total) }}
                                                    </span>
                                                </div>

                                                <div class="mt-2 flex max-w-md items-center justify-between border-t border-[#2563EB]/15 pt-2">
                                                    <span class="text-xs text-gray-500">Total Add-on</span>
                                                    <span class="text-sm text-[#2563EB]" style="font-weight: 600;">
                                                        {{ formatRupiah(row.add_ons_total) }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="loading">
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-400">Memuat data...</td>
                            </tr>

                            <tr v-else-if="!normalizedRows.length">
                                <td colspan="9" class="px-4 py-12 text-center text-sm text-gray-400">Tidak ada booking ditemukan</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    class="flex items-center justify-between gap-3 border-t border-gray-50 px-4 py-3"
                    :class="hasPagination ? '' : 'sm:justify-end'"
                >
                    <p class="text-xs text-gray-500">
                        Menampilkan {{ normalizedRows.length }} dari {{ pagination.total }} data
                    </p>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 transition-colors"
                            :class="canGoPrev ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                            :disabled="!canGoPrev || loading"
                            @click="goToPrevPage"
                        >
                            Sebelumnya
                        </button>

                        <span class="text-xs text-gray-500">
                            Halaman {{ pagination.current_page }} / {{ Math.max(pagination.last_page, 1) }}
                        </span>

                        <button
                            type="button"
                            class="rounded-lg border border-gray-200 px-3 py-1.5 text-xs text-gray-600 transition-colors"
                            :class="canGoNext ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                            :disabled="!canGoNext || loading"
                            @click="goToNextPage"
                        >
                            Selanjutnya
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
