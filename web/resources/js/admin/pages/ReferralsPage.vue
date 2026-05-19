<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { Plus, RefreshCw, Trash2, Pencil, X } from 'lucide-vue-next';
import AdminModal from '../components/AdminModal.vue';

const props = defineProps({
    payload: { type: Object, default: () => ({}) },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-referrals', 'save-referral', 'delete-referral']);

const formOpen = ref(false);
const editingId = ref(null);
const localError = ref('');

const filterForm = reactive({
    from: '',
    to: '',
    code: '',
    channel: '',
    status: '',
});

const form = reactive({
    code: '',
    source_name: '',
    source_type: 'campaign',
    description: '',
    discount_type: 'fixed',
    discount_value: '',
    max_discount_amount: '',
    min_order_amount: '',
    branch_id: '',
    package_id: '',
    usage_limit: '',
    valid_from: '',
    valid_until: '',
    is_active: true,
});

const summary = computed(() => props.payload?.summary || {});
const codes = computed(() => Array.isArray(props.payload?.codes) ? props.payload.codes : []);
const redemptions = computed(() => Array.isArray(props.payload?.redemptions) ? props.payload.redemptions : []);
const breakdowns = computed(() => props.payload?.breakdowns || {});
const options = computed(() => props.payload?.options || {});
const sourceTypes = computed(() => Array.isArray(options.value.source_types) ? options.value.source_types : []);
const discountTypes = computed(() => Array.isArray(options.value.discount_types) ? options.value.discount_types : []);
const channels = computed(() => Array.isArray(options.value.channels) ? options.value.channels : []);
const branches = computed(() => Array.isArray(options.value.branches) ? options.value.branches : []);
const packages = computed(() => Array.isArray(options.value.packages) ? options.value.packages : []);

const statusOptions = [
    { value: 'applied', label: 'Applied' },
    { value: 'paid', label: 'Paid' },
    { value: 'done', label: 'Done' },
    { value: 'voided', label: 'Voided' },
];

const filteredPackages = computed(() => {
    const branchId = Number(form.branch_id || 0);

    return packages.value.filter((item) => {
        const packageBranchId = item.branch_id ? Number(item.branch_id) : null;

        return !branchId || packageBranchId === null || packageBranchId === branchId;
    });
});

const activeCodeCount = computed(() => codes.value.filter((row) => Boolean(row.is_active)).length);
const inactiveCodeCount = computed(() => Math.max(codes.value.length - activeCodeCount.value, 0));

const activeFiltersCount = computed(() => Object.values(filterForm).filter((value) => String(value || '').trim() !== '').length);

const summaryCards = computed(() => [
    {
        label: 'Pemakaian',
        value: summary.value.total_redemptions || 0,
        helper: `${summary.value.paid_redemptions || 0} paid / ${summary.value.voided_redemptions || 0} voided`,
        accent: '#047857',
    },
    {
        label: 'Customer Unik',
        value: summary.value.unique_customers || 0,
        helper: `${codes.value.length} kode terdaftar`,
        accent: '#0F766E',
    },
    {
        label: 'Total Diskon',
        value: summary.value.discount_text || 'Rp 0',
        helper: 'Nilai benefit customer',
        accent: '#059669',
    },
    {
        label: 'Omzet Setelah Diskon',
        value: summary.value.final_text || 'Rp 0',
        helper: summary.value.subtotal_text ? `Subtotal ${summary.value.subtotal_text}` : 'Subtotal Rp 0',
        accent: '#0F172A',
    },
    {
        label: 'Kode Aktif',
        value: activeCodeCount.value,
        helper: `${inactiveCodeCount.value} nonaktif`,
        accent: '#2563EB',
    },
]);

const formatCurrency = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;

const normalizeDateTimeLocal = (value) => {
    const normalized = String(value || '').replace(' ', 'T');

    return normalized ? normalized.slice(0, 16) : '';
};

const formatDiscount = (row) => {
    if (row.discount_type === 'percent') {
        return `${Number(row.discount_value || 0).toLocaleString('id-ID')}%`;
    }

    return formatCurrency(row.discount_value);
};

const periodLabel = (row) => {
    const from = row.valid_from ? normalizeDateTimeLocal(row.valid_from).replace('T', ' ') : '';
    const until = row.valid_until ? normalizeDateTimeLocal(row.valid_until).replace('T', ' ') : '';

    if (from && until) {
        return `${from} - ${until}`;
    }

    if (from) {
        return `Mulai ${from}`;
    }

    if (until) {
        return `Sampai ${until}`;
    }

    return 'Tanpa periode khusus';
};

const usagePercent = (row) => {
    const limit = Number(row.usage_limit || 0);

    if (limit <= 0) {
        return 100;
    }

    return Math.min(Math.round((Number(row.used_count || 0) / limit) * 100), 100);
};

const statusClass = (status) => {
    if (status === 'voided') {
        return 'bg-[#FEF2F2] text-[#DC2626]';
    }

    if (status === 'paid' || status === 'done') {
        return 'bg-[#ECFDF5] text-[#047857]';
    }

    return 'bg-[#EFF6FF] text-[#2563EB]';
};

const channelLabel = (value) => channels.value.find((item) => item.value === value)?.label || value || '-';

const resetForm = () => {
    editingId.value = null;
    form.code = '';
    form.source_name = '';
    form.source_type = 'campaign';
    form.description = '';
    form.discount_type = 'fixed';
    form.discount_value = '';
    form.max_discount_amount = '';
    form.min_order_amount = '';
    form.branch_id = '';
    form.package_id = '';
    form.usage_limit = '';
    form.valid_from = '';
    form.valid_until = '';
    form.is_active = true;
    localError.value = '';
};

const openCreate = () => {
    resetForm();
    formOpen.value = true;
};

const openEdit = (row) => {
    editingId.value = Number(row.id || 0);
    form.code = String(row.code || '');
    form.source_name = String(row.source_name || '');
    form.source_type = String(row.source_type || 'other');
    form.description = String(row.description || '');
    form.discount_type = String(row.discount_type || 'fixed');
    form.discount_value = String(row.discount_value || '');
    form.max_discount_amount = row.max_discount_amount === null || row.max_discount_amount === undefined ? '' : String(row.max_discount_amount);
    form.min_order_amount = String(row.min_order_amount || '');
    form.branch_id = row.branch_id ? String(row.branch_id) : '';
    form.package_id = row.package_id ? String(row.package_id) : '';
    form.usage_limit = row.usage_limit ? String(row.usage_limit) : '';
    form.valid_from = normalizeDateTimeLocal(row.valid_from);
    form.valid_until = normalizeDateTimeLocal(row.valid_until);
    form.is_active = Boolean(row.is_active);
    localError.value = '';
    formOpen.value = true;
};

const closeForm = () => {
    formOpen.value = false;
    localError.value = '';
};

const submitForm = () => {
    const discountValue = Number(form.discount_value || 0);
    const maxDiscount = Number(form.max_discount_amount || 0);

    if (!form.code.trim() || !form.source_name.trim()) {
        localError.value = 'Kode dan sumber referal wajib diisi.';
        return;
    }

    if (!/^[A-Za-z0-9_-]+$/.test(form.code.trim())) {
        localError.value = 'Kode hanya boleh berisi huruf, angka, underscore, atau dash.';
        return;
    }

    if (discountValue <= 0) {
        localError.value = 'Nilai diskon harus lebih dari 0.';
        return;
    }

    if (form.discount_type === 'percent' && discountValue > 100) {
        localError.value = 'Diskon persentase maksimal 100%.';
        return;
    }

    if (form.discount_type === 'fixed' && maxDiscount > 0) {
        form.max_discount_amount = '';
    }

    if (form.valid_from && form.valid_until && form.valid_until < form.valid_from) {
        localError.value = 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.';
        return;
    }

    const payload = {
        code: form.code.trim().toUpperCase(),
        source_name: form.source_name.trim(),
        source_type: form.source_type,
        description: form.description.trim(),
        discount_type: form.discount_type,
        discount_value: discountValue,
        max_discount_amount: form.max_discount_amount === '' ? null : Number(form.max_discount_amount || 0),
        min_order_amount: form.min_order_amount === '' ? 0 : Number(form.min_order_amount || 0),
        branch_id: form.branch_id ? Number(form.branch_id) : null,
        package_id: form.package_id ? Number(form.package_id) : null,
        usage_limit: form.usage_limit ? Number(form.usage_limit) : null,
        valid_from: form.valid_from || null,
        valid_until: form.valid_until || null,
        is_active: Boolean(form.is_active),
    };

    localError.value = '';
    emit('save-referral', {
        id: editingId.value,
        payload,
        onSuccess: () => {
            formOpen.value = false;
        },
        onError: (message) => {
            localError.value = message || 'Gagal menyimpan kode referal.';
        },
    });
};

const requestDelete = (row) => {
    if (!window.confirm(`Hapus kode referal ${row.code}? Riwayat pemakaian tetap tersimpan sebagai snapshot.`)) {
        return;
    }

    emit('delete-referral', Number(row.id || 0));
};

const applyFilters = () => {
    emit('refresh-referrals', { ...filterForm });
};

const resetFilters = () => {
    filterForm.from = '';
    filterForm.to = '';
    filterForm.code = '';
    filterForm.channel = '';
    filterForm.status = '';
    emit('refresh-referrals', {});
};

watch(() => form.branch_id, () => {
    if (!form.package_id) {
        return;
    }

    const packageStillAllowed = filteredPackages.value.some((item) => String(item.id) === String(form.package_id));

    if (!packageStillAllowed) {
        form.package_id = '';
    }
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-3xl px-6 py-5" style="background: linear-gradient(135deg, #064E3B 0%, #047857 56%, #10B981 100%); box-shadow: 0 8px 28px rgba(5,150,105,0.24);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-12 -top-12 h-44 w-44 rounded-full" style="background: rgba(209,250,229,0.18);"></div>
                <div class="absolute left-8 top-7 h-10 w-10 rounded-full" style="background: rgba(255,255,255,0.14);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3 text-white">
                <div>
                    <h2 class="text-[1.35rem] font-bold">Kode Referal</h2>
                    <p class="text-sm text-white/80">Kelola diskon referal, batas pemakaian, scope cabang/paket, dan performa per channel.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60" :disabled="loading" @click="emit('refresh-referrals', { ...filterForm })">
                        <RefreshCw class="h-4 w-4" :class="loading ? 'animate-spin' : ''" />
                        {{ loading ? 'Refreshing...' : 'Refresh' }}
                    </button>
                    <button type="button" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-[#047857] transition hover:bg-[#ECFDF5]" @click="openCreate">
                        <Plus class="h-4 w-4" /> Kode Baru
                    </button>
                </div>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div v-for="card in summaryCards" :key="card.label" class="rounded-3xl border bg-white p-4" style="border-color: #D1FAE5; box-shadow: 0 1px 3px rgba(5,150,105,0.08), 0 8px 20px rgba(5,150,105,0.08);">
                <p class="text-xs text-[#94A3B8]">{{ card.label }}</p>
                <p class="mt-1 text-xl font-bold" :style="{ color: card.accent }">{{ card.value }}</p>
                <p class="mt-1 text-xs text-[#64748B]">{{ card.helper }}</p>
            </div>
        </div>

        <section class="rounded-3xl border bg-white p-4" style="border-color: #D1FAE5; box-shadow: 0 1px 3px rgba(5,150,105,0.08);">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h3 class="text-sm font-semibold text-[#0F172A]">Filter Riwayat</h3>
                    <p class="text-xs text-[#94A3B8]">{{ activeFiltersCount ? `${activeFiltersCount} filter aktif` : 'Tampilkan semua pemakaian referal' }}</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs font-semibold text-[#64748B]" style="border-color: #E2E8F0;" @click="resetFilters">Reset</button>
            </div>
            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-6">
                <label class="text-xs text-[#64748B]">From
                    <input v-model="filterForm.from" type="date" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                </label>
                <label class="text-xs text-[#64748B]">To
                    <input v-model="filterForm.to" type="date" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                </label>
                <label class="text-xs text-[#64748B]">Kode
                    <input v-model="filterForm.code" type="text" placeholder="PROMO10" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm uppercase" style="border-color: #CBD5E1;">
                </label>
                <label class="text-xs text-[#64748B]">Channel
                    <select v-model="filterForm.channel" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">Semua channel</option>
                        <option v-for="channel in channels" :key="channel.value" :value="channel.value">{{ channel.label }}</option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Status
                    <select v-model="filterForm.status" class="mt-1 w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">Semua status</option>
                        <option v-for="status in statusOptions" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </label>
                <button type="button" class="self-end rounded-xl bg-[#047857] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#065F46] disabled:cursor-not-allowed disabled:opacity-70" :disabled="loading" @click="applyFilters">
                    {{ loading ? 'Loading...' : 'Terapkan' }}
                </button>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_360px]">
            <div class="overflow-hidden rounded-3xl border bg-white" style="border-color: #D1FAE5; box-shadow: 0 1px 3px rgba(5,150,105,0.08), 0 8px 20px rgba(5,150,105,0.08);">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b px-5 py-4" style="border-color: #E2E8F0;">
                    <div>
                        <h3 class="font-semibold text-[#0F172A]">Master Kode</h3>
                        <p class="text-xs text-[#94A3B8]">{{ codes.length }} kode, {{ activeCodeCount }} aktif</p>
                    </div>
                    <button type="button" class="rounded-xl bg-[#ECFDF5] px-3 py-1.5 text-xs font-semibold text-[#047857]" @click="openCreate">Tambah kode</button>
                </div>
                <div class="rtp-admin-table-wrap">
                    <table class="rtp-admin-table rtp-admin-table--wide w-full text-sm">
                        <thead class="bg-[#F8FAFC] text-xs uppercase text-[#94A3B8]">
                            <tr>
                                <th class="px-4 py-3 text-left">Kode</th>
                                <th class="px-4 py-3 text-left">Sumber</th>
                                <th class="px-4 py-3 text-left">Diskon</th>
                                <th class="px-4 py-3 text-left">Pemakaian</th>
                                <th class="px-4 py-3 text-left">Scope</th>
                                <th class="px-4 py-3 text-left">Periode</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in codes" :key="row.id" class="border-t align-top" style="border-color: #F1F5F9;">
                                <td class="px-4 py-3">
                                    <p class="font-bold text-[#047857]">{{ row.code }}</p>
                                    <span class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[0.68rem] font-semibold" :class="row.is_active ? 'bg-[#ECFDF5] text-[#047857]' : 'bg-[#FEF2F2] text-[#DC2626]'">
                                        {{ row.is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-[#0F172A]">{{ row.source_name }}</p>
                                    <p class="mt-1 text-xs text-[#64748B]">{{ row.source_type }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-semibold text-[#0F172A]">{{ formatDiscount(row) }}</p>
                                    <p v-if="row.max_discount_amount" class="mt-1 text-xs text-[#64748B]">Maks {{ formatCurrency(row.max_discount_amount) }}</p>
                                    <p v-if="row.min_order_amount" class="mt-1 text-xs text-[#64748B]">Min {{ formatCurrency(row.min_order_amount) }}</p>
                                </td>
                                <td class="px-4 py-3 min-w-[150px]">
                                    <div class="flex items-center justify-between gap-2 text-xs text-[#64748B]">
                                        <span>{{ row.used_count }} / {{ row.usage_limit || '∞' }}</span>
                                        <span>{{ row.total_discount_text }}</span>
                                    </div>
                                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-[#E2E8F0]">
                                        <div class="h-full rounded-full bg-[#10B981]" :style="{ width: `${usagePercent(row)}%` }"></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="text-xs text-[#0F172A]">{{ row.branch_name }}</p>
                                    <p class="mt-1 text-xs text-[#64748B]">{{ row.package_name }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-[#64748B] min-w-[170px]">{{ periodLabel(row) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <button type="button" class="mr-2 rounded-lg border p-2 text-[#2563EB] transition hover:bg-[#EFF6FF]" style="border-color: #DBEAFE;" @click="openEdit(row)">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button type="button" class="rounded-lg border p-2 text-[#DC2626] transition hover:bg-[#FEF2F2]" style="border-color: #FECACA;" @click="requestDelete(row)">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </td>
                            </tr>
                            <tr v-if="!codes.length">
                                <td colspan="7" class="px-4 py-12 text-center text-[#94A3B8]">Belum ada kode referal. Buat kode pertama dari tombol Kode Baru.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="space-y-4">
                <div class="rounded-3xl border bg-white p-4" style="border-color: #D1FAE5; box-shadow: 0 1px 3px rgba(5,150,105,0.08);">
                    <h3 class="mb-3 font-semibold text-[#0F172A]">Top Kode</h3>
                    <div class="space-y-2">
                        <div v-for="row in (breakdowns.by_code || []).slice(0, 6)" :key="row.label" class="rounded-2xl bg-[#F8FAFC] px-3 py-2 text-sm">
                            <div class="flex justify-between gap-3">
                                <span class="font-medium text-[#0F172A]">{{ row.label }}</span>
                                <span class="font-semibold text-[#047857]">{{ row.total_redemptions }}</span>
                            </div>
                            <p class="mt-1 text-xs text-[#64748B]">Diskon {{ row.discount_text }} · Net {{ row.final_text }}</p>
                        </div>
                        <p v-if="!(breakdowns.by_code || []).length" class="text-sm text-[#94A3B8]">Belum ada data.</p>
                    </div>
                </div>
                <div class="rounded-3xl border bg-white p-4" style="border-color: #D1FAE5; box-shadow: 0 1px 3px rgba(5,150,105,0.08);">
                    <h3 class="mb-3 font-semibold text-[#0F172A]">Channel</h3>
                    <div class="space-y-2">
                        <div v-for="row in (breakdowns.by_channel || []).slice(0, 6)" :key="row.label" class="flex justify-between rounded-2xl bg-[#F8FAFC] px-3 py-2 text-sm">
                            <span>{{ channelLabel(row.label) }}</span>
                            <span class="font-semibold text-[#047857]">{{ row.total_redemptions }}</span>
                        </div>
                        <p v-if="!(breakdowns.by_channel || []).length" class="text-sm text-[#94A3B8]">Belum ada data.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="overflow-hidden rounded-3xl border bg-white" style="border-color: #D1FAE5; box-shadow: 0 1px 3px rgba(5,150,105,0.08), 0 8px 20px rgba(5,150,105,0.08);">
            <div class="border-b px-5 py-4" style="border-color: #E2E8F0;">
                <h3 class="font-semibold text-[#0F172A]">Riwayat Pemakaian</h3>
                <p class="text-xs text-[#94A3B8]">Maksimal 250 transaksi terbaru sesuai filter aktif.</p>
            </div>
            <div class="rtp-admin-table-wrap">
                <table class="rtp-admin-table rtp-admin-table--wide w-full text-sm">
                    <thead class="bg-[#F8FAFC] text-xs uppercase text-[#94A3B8]">
                        <tr>
                            <th class="px-4 py-3 text-left">Waktu</th>
                            <th class="px-4 py-3 text-left">Kode</th>
                            <th class="px-4 py-3 text-left">Customer</th>
                            <th class="px-4 py-3 text-left">Channel</th>
                            <th class="px-4 py-3 text-right">Subtotal</th>
                            <th class="px-4 py-3 text-right">Diskon</th>
                            <th class="px-4 py-3 text-right">Final</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in redemptions" :key="row.id" class="border-t" style="border-color: #F1F5F9;">
                            <td class="px-4 py-3 text-[#64748B]">{{ row.redeemed_at_text }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-[#047857]">{{ row.referral_code }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ row.source_name || '-' }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-[#0F172A]">{{ row.customer_name || '-' }}</p>
                                <p class="text-xs text-[#64748B]">{{ row.customer_phone || '-' }}</p>
                            </td>
                            <td class="px-4 py-3 text-[#64748B]">{{ channelLabel(row.channel) }}</td>
                            <td class="px-4 py-3 text-right text-[#64748B]">{{ row.subtotal_text }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-[#059669]">{{ row.discount_text }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-[#0F172A]">{{ row.final_text }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-semibold" :class="statusClass(row.status)">{{ row.status }}</span>
                            </td>
                        </tr>
                        <tr v-if="!redemptions.length">
                            <td colspan="8" class="px-4 py-12 text-center text-[#94A3B8]">Belum ada pemakaian referal untuk filter ini.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <AdminModal :show="formOpen" panel-class="max-w-3xl" panel-padding-class="p-0" panel-radius-class="rounded-3xl">
                <div class="flex items-start justify-between gap-4 border-b px-5 py-4" style="border-color: #E2E8F0;">
                    <div>
                        <h3 class="text-lg font-semibold text-[#0F172A]">{{ editingId ? 'Edit Kode Referal' : 'Kode Referal Baru' }}</h3>
                        <p class="mt-1 text-xs text-[#64748B]">Kode akan otomatis disimpan uppercase dan langsung tersedia untuk channel yang sesuai.</p>
                    </div>
                    <button type="button" class="rounded-lg p-2 text-[#64748B] transition hover:bg-[#F8FAFC]" @click="closeForm">
                        <X class="h-4 w-4" />
                    </button>
                </div>
                <div class="p-5">
                    <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">{{ localError }}</p>
                    <div class="rtp-admin-form-grid">
                        <label class="text-sm text-[#475569]">Kode
                            <input v-model="form.code" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 uppercase" style="border-color: #E2E8F0;" placeholder="PROMO10">
                        </label>
                        <label class="text-sm text-[#475569]">Nama Sumber
                            <input v-model="form.source_name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" placeholder="Instagram, Staff A, Partner B">
                        </label>
                        <label class="text-sm text-[#475569]">Tipe Sumber
                            <select v-model="form.source_type" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option v-for="item in sourceTypes" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </label>
                        <label class="text-sm text-[#475569]">Tipe Diskon
                            <select v-model="form.discount_type" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option v-for="item in discountTypes" :key="item.value" :value="item.value">{{ item.label }}</option>
                            </select>
                        </label>
                        <label class="text-sm text-[#475569]">Nilai Diskon
                            <input v-model="form.discount_value" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" :placeholder="form.discount_type === 'percent' ? '10' : '50000'">
                            <span class="mt-1 block text-xs text-[#94A3B8]">{{ form.discount_type === 'percent' ? 'Isi 1-100 untuk persentase.' : 'Isi nominal rupiah tanpa titik.' }}</span>
                        </label>
                        <label class="text-sm text-[#475569]">Maks Diskon
                            <input v-model="form.max_discount_amount" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border px-3 py-2 disabled:bg-[#F8FAFC] disabled:text-[#94A3B8]" style="border-color: #E2E8F0;" :disabled="form.discount_type !== 'percent'" placeholder="Opsional untuk percent">
                        </label>
                        <label class="text-sm text-[#475569]">Minimum Transaksi
                            <input v-model="form.min_order_amount" type="number" min="0" step="0.01" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" placeholder="0">
                        </label>
                        <label class="text-sm text-[#475569]">Limit Pemakaian
                            <input v-model="form.usage_limit" type="number" min="1" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" placeholder="Kosong = tanpa limit">
                        </label>
                        <label class="text-sm text-[#475569]">Cabang
                            <select v-model="form.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option value="">Semua cabang</option>
                                <option v-for="branch in branches" :key="branch.id" :value="String(branch.id)">{{ branch.name }}</option>
                            </select>
                        </label>
                        <label class="text-sm text-[#475569]">Paket
                            <select v-model="form.package_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option value="">Semua paket</option>
                                <option v-for="pkg in filteredPackages" :key="pkg.id" :value="String(pkg.id)">{{ pkg.name }}</option>
                            </select>
                        </label>
                        <label class="text-sm text-[#475569]">Berlaku Dari
                            <input v-model="form.valid_from" type="datetime-local" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                        </label>
                        <label class="text-sm text-[#475569]">Berlaku Sampai
                            <input v-model="form.valid_until" type="datetime-local" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                        </label>
                    </div>
                    <label class="mt-3 block text-sm text-[#475569]">Deskripsi
                        <textarea v-model="form.description" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" placeholder="Catatan internal untuk campaign atau partner."></textarea>
                    </label>
                    <label class="mt-3 flex items-center gap-2 text-sm text-[#475569]"><input v-model="form.is_active" type="checkbox"> Aktif</label>
                    <div class="rtp-admin-actions mt-5">
                        <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeForm">Cancel</button>
                        <button type="button" class="rounded-xl bg-[#047857] px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-70" :disabled="saving" @click="submitForm">{{ saving ? 'Saving...' : 'Save' }}</button>
                    </div>
                </div>
        </AdminModal>
    </div>
</template>
