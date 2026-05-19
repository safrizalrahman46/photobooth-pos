<script setup>
import { computed, reactive, ref } from 'vue';
import { CheckCircle, Pencil, Plus, RefreshCw, Trash2, TrendingDown, TrendingUp } from 'lucide-vue-next';
import AdminModal from '../components/AdminModal.vue';

const props = defineProps({
    inventoryItems: { type: Array, default: () => [] },
    inventoryMovements: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingInventoryItemId: { type: [Number, String, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits([
    'refresh-stock',
    'create-inventory-item',
    'update-inventory-item',
    'delete-inventory-item',
    'move-stock',
]);

const itemModalOpen = ref(false);
const itemModalMode = ref('create');
const editingItemId = ref(null);
const stockModalOpen = ref(false);
const stockTarget = ref(null);
const localError = ref('');
const toastVisible = ref(false);
let toastTimer = null;

const itemForm = reactive({
    code: '',
    name: '',
    unit: 'pcs',
    available_stock: 0,
    low_stock_threshold: 0,
    is_active: true,
    sort_order: 0,
});

const stockForm = reactive({
    movement_type: 'in',
    qty: 1,
    notes: '',
});

const stats = computed(() => {
    const total = props.inventoryItems.length;
    const low = props.inventoryItems.filter((item) => Number(item.available_stock || 0) > 0 && Number(item.available_stock || 0) <= Number(item.low_stock_threshold || 0)).length;
    const out = props.inventoryItems.filter((item) => Number(item.available_stock || 0) <= 0).length;

    return { total, low, out };
});

const resolveStockTone = (row) => {
    const stock = Number(row?.available_stock ?? 0);
    const threshold = Number(row?.low_stock_threshold ?? 0);

    if (stock <= 0) return { label: 'Out of stock', variant: 'out', style: { background: '#FEF2F2', color: '#B91C1C' } };
    if (stock <= threshold) return { label: 'Low stock', variant: 'low', style: { background: '#FFF7ED', color: '#C2410C' } };
    return { label: 'Ready', variant: 'ready', style: { background: '#ECFDF5', color: '#047857' } };
};

const resetItemForm = () => {
    itemForm.code = '';
    itemForm.name = '';
    itemForm.unit = 'pcs';
    itemForm.available_stock = 0;
    itemForm.low_stock_threshold = 0;
    itemForm.is_active = true;
    itemForm.sort_order = 0;
    editingItemId.value = null;
    localError.value = '';
};

const openCreateItemModal = () => {
    resetItemForm();
    itemModalMode.value = 'create';
    itemModalOpen.value = true;
};

const openEditItemModal = (item) => {
    itemModalMode.value = 'edit';
    editingItemId.value = Number(item.id || 0);
    itemForm.code = String(item.code || '');
    itemForm.name = String(item.name || '');
    itemForm.unit = String(item.unit || 'pcs');
    itemForm.available_stock = Math.max(0, Number(item.available_stock || 0));
    itemForm.low_stock_threshold = Math.max(0, Number(item.low_stock_threshold || 0));
    itemForm.is_active = Boolean(item.is_active);
    itemForm.sort_order = Math.max(0, Number(item.sort_order || 0));
    localError.value = '';
    itemModalOpen.value = true;
};

const closeItemModal = () => {
    itemModalOpen.value = false;
    localError.value = '';
};

const submitItemForm = async () => {
    if (!String(itemForm.name || '').trim()) {
        localError.value = 'Nama barang wajib diisi.';
        return;
    }

    const payload = {
        code: String(itemForm.code || '').trim(),
        name: String(itemForm.name || '').trim(),
        unit: String(itemForm.unit || 'pcs').trim() || 'pcs',
        available_stock: Math.max(0, Number(itemForm.available_stock || 0)),
        low_stock_threshold: Math.max(0, Number(itemForm.low_stock_threshold || 0)),
        is_active: Boolean(itemForm.is_active),
        sort_order: Math.max(0, Number(itemForm.sort_order || 0)),
    };

    try {
        if (itemModalMode.value === 'create') {
            await emit('create-inventory-item', payload);
        } else {
            await emit('update-inventory-item', { id: editingItemId.value, payload });
        }

        closeItemModal();
    } catch {
        // Parent surfaces request errors.
    }
};

const openStockModal = (row) => {
    stockTarget.value = row;
    stockForm.movement_type = 'in';
    stockForm.qty = 1;
    stockForm.notes = '';
    localError.value = '';
    stockModalOpen.value = true;
};

const closeStockModal = () => {
    stockModalOpen.value = false;
    stockTarget.value = null;
    localError.value = '';
};

const projectedStock = computed(() => {
    if (!stockTarget.value) return null;

    const current = Math.max(0, Number(stockTarget.value.available_stock || 0));
    const qty = Math.max(1, Number(stockForm.qty || 1));

    return stockForm.movement_type === 'out' ? current - qty : current + qty;
});

const showToast = () => {
    toastVisible.value = true;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toastVisible.value = false; }, 2500);
};

const submitMovement = async () => {
    if (!stockTarget.value?.id) return;

    if (Number(stockForm.qty || 0) < 1) {
        localError.value = 'Jumlah minimal 1.';
        return;
    }

    if (stockForm.movement_type === 'out' && Number(stockForm.qty || 0) > Number(stockTarget.value.available_stock || 0)) {
        localError.value = 'Stok tidak mencukupi untuk dikeluarkan.';
        return;
    }

    try {
        await emit('move-stock', {
            id: Number(stockTarget.value.id),
            payload: {
                movement_type: stockForm.movement_type,
                qty: Math.max(1, Number(stockForm.qty || 1)),
                notes: String(stockForm.notes || '').trim(),
            },
        });

        closeStockModal();
        showToast();
    } catch {
        // Parent surfaces request errors.
    }
};

const requestDelete = async (item) => {
    const confirmed = window.confirm(`Delete ${String(item.name || 'this item')}? This action cannot be undone.`);

    if (!confirmed) return;

    try {
        await emit('delete-inventory-item', Number(item.id || 0));
    } catch {
        // Parent surfaces request errors.
    }
};
</script>

<template>
    <div class="space-y-5">
        <Transition name="toast">
            <div v-if="toastVisible" class="fixed right-5 top-5 z-[9999] flex items-center gap-2 rounded-xl px-4 py-2 text-sm text-white shadow-lg" style="background: #0F766E;">
                <CheckCircle class="h-4 w-4" />
                Stok berhasil diperbarui
            </div>
        </Transition>

        <section class="rounded-2xl px-6 py-5" style="background: linear-gradient(135deg, #0F766E, #059669); box-shadow: 0 6px 24px rgba(15,118,110,0.18);">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium" style="color: rgba(255,255,255,0.72);">Inventory Management</p>
                    <h2 class="text-[1.35rem] font-bold text-white">Stock Barang</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.72);">Kelola barang fisik, stok, dan riwayat pergerakan.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-xl border px-3 py-2 text-sm font-semibold text-white" style="border-color: rgba(255,255,255,0.34); background: rgba(255,255,255,0.1);" :disabled="loading" @click="emit('refresh-stock')">
                        <RefreshCw class="mr-1.5 inline h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                        Refresh
                    </button>
                    <button type="button" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #0F766E;" @click="openCreateItemModal">
                        <Plus class="mr-1 inline h-3.5 w-3.5" />
                        Tambah Barang
                    </button>
                </div>
            </div>
        </section>

        <p v-if="errorMessage" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ errorMessage }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Total barang</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#0F766E]">{{ stats.total }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Low stock</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#D97706]">{{ stats.low }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Out of stock</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#DC2626]">{{ stats.out }}</p>
            </article>
        </div>

        <div class="overflow-hidden rounded-2xl border bg-white" style="border-color: #DBEAFE; box-shadow: 0 1px 3px rgba(37,99,235,0.08), 0 6px 18px rgba(37,99,235,0.08);">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: #ECFDF5; color: #334155;">
                        <tr>
                            <th class="whitespace-nowrap px-3 py-2 text-left font-semibold">Kode</th>
                            <th class="whitespace-nowrap px-3 py-2 text-left font-semibold">Barang</th>
                            <th class="whitespace-nowrap px-3 py-2 text-center font-semibold">Stok</th>
                            <th class="whitespace-nowrap px-3 py-2 text-center font-semibold">Status</th>
                            <th class="whitespace-nowrap px-3 py-2 text-center font-semibold">Aktif</th>
                            <th class="whitespace-nowrap px-3 py-2 text-right font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="loading">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-[#94A3B8]">Loading stock data...</td>
                        </tr>
                        <tr v-for="row in inventoryItems" v-else :key="`inventory-row-${row.id}`" class="border-t hover:bg-[#FAFCFF]" style="border-color: #E2E8F0;">
                            <td class="px-3 py-2 text-[#475569]">{{ row.code }}</td>
                            <td class="px-3 py-2">
                                <p class="font-semibold text-[#1E293B]">{{ row.name }}</p>
                                <p class="text-xs text-[#64748B]">Unit: {{ row.unit }}</p>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <p class="font-semibold text-[#0F172A]">{{ Number(row.available_stock || 0) }}</p>
                                <p class="text-[11px] text-[#64748B]">Low: {{ Number(row.low_stock_threshold || 0) }}</p>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :style="resolveStockTone(row).style">
                                    {{ resolveStockTone(row).label }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :style="row.is_active ? { background: '#ECFDF5', color: '#059669' } : { background: '#F8FAFC', color: '#64748B' }">
                                    {{ row.is_active ? 'active' : 'inactive' }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex flex-wrap items-center justify-end gap-1.5">
                                    <button type="button" class="rounded-lg border px-2.5 py-1 text-xs font-semibold" style="border-color: #0EA5E9; color: #0369A1;" @click="openStockModal(row)">Stock</button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-semibold" style="border-color: #2563EB; color: #2563EB;" @click="openEditItemModal(row)">
                                        <Pencil class="h-3.5 w-3.5" /> Edit
                                    </button>
                                    <button type="button" class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-semibold" style="border-color: #FECACA; color: #EF4444;" :disabled="Number(deletingInventoryItemId || 0) === Number(row.id)" @click="requestDelete(row)">
                                        <Trash2 class="h-3.5 w-3.5" /> Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!loading && !inventoryItems.length">
                            <td colspan="6" class="px-4 py-8 text-center text-sm text-[#94A3B8]">Belum ada barang stok.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <section class="overflow-hidden rounded-2xl border bg-white" style="border-color: #E2E8F0;">
            <header class="border-b px-4 py-3" style="border-color: #E2E8F0; background: #F8FAFC;">
                <h3 class="text-sm font-semibold text-[#1E293B]">Riwayat Movement</h3>
                <p class="text-xs text-[#64748B]">Termasuk stok manual dan auto deduction dari booking terverifikasi.</p>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead style="background: #F8FAFC; color: #475569;">
                        <tr>
                            <th class="px-3 py-2 text-left">Waktu</th>
                            <th class="px-3 py-2 text-left">Barang</th>
                            <th class="px-3 py-2 text-center">Type</th>
                            <th class="px-3 py-2 text-center">Qty</th>
                            <th class="px-3 py-2 text-center">Before</th>
                            <th class="px-3 py-2 text-center">After</th>
                            <th class="px-3 py-2 text-left">Source</th>
                            <th class="px-3 py-2 text-left">Actor</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="movement in inventoryMovements" :key="`movement-${movement.id}`" class="border-t" style="border-color: #E2E8F0;">
                            <td class="px-3 py-2 text-[#64748B]">{{ movement.created_at_text }}</td>
                            <td class="px-3 py-2">
                                <p class="font-semibold text-[#1E293B]">{{ movement.inventory_item_name }}</p>
                                <p class="text-xs text-[#64748B]">{{ movement.inventory_item_code }}</p>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :style="movement.movement_type === 'in' ? { background: '#ECFDF5', color: '#047857' } : { background: '#FEF2F2', color: '#B91C1C' }">
                                    {{ movement.movement_type === 'in' ? 'IN' : 'OUT' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center font-semibold text-[#0F172A]">{{ movement.qty }} {{ movement.unit }}</td>
                            <td class="px-3 py-2 text-center text-[#64748B]">{{ movement.stock_before }}</td>
                            <td class="px-3 py-2 text-center text-[#64748B]">{{ movement.stock_after }}</td>
                            <td class="px-3 py-2 text-[#64748B]">{{ movement.source_ref || movement.source_type || 'manual' }}</td>
                            <td class="px-3 py-2 text-[#64748B]">{{ movement.actor_name }}</td>
                        </tr>
                        <tr v-if="!inventoryMovements.length">
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-[#94A3B8]">Belum ada riwayat movement.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <AdminModal :show="itemModalOpen" panel-class="max-w-2xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">{{ itemModalMode === 'create' ? 'Tambah Barang' : 'Edit Barang' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeItemModal">Close</button>
                </div>
                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">{{ localError }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <label class="text-sm text-[#475569]">Kode (optional)<input v-model="itemForm.code" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                    <label class="text-sm text-[#475569]">Nama Barang<input v-model="itemForm.name" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                    <label class="text-sm text-[#475569]">Unit<input v-model="itemForm.unit" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                    <label class="text-sm text-[#475569]">Stok Awal<input v-model.number="itemForm.available_stock" type="number" min="0" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                    <label class="text-sm text-[#475569]">Low-stock Threshold<input v-model.number="itemForm.low_stock_threshold" type="number" min="0" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                    <label class="text-sm text-[#475569]">Sort Order<input v-model.number="itemForm.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                </div>
                <label class="mt-3 inline-flex items-center gap-2 text-sm text-[#475569]"><input v-model="itemForm.is_active" type="checkbox"> Active item</label>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeItemModal">Cancel</button>
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background: #0F766E;" :disabled="saving" @click="submitItemForm">
                        {{ saving ? 'Saving...' : 'Save' }}
                    </button>
                </div>
        </AdminModal>

        <AdminModal :show="stockModalOpen" z-class="z-50" panel-class="max-w-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">Stock Movement - {{ stockTarget?.name || '-' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeStockModal">Close</button>
                </div>
                <p class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #BFDBFE; background: #EFF6FF; color: #1E3A8A;">Stock saat ini: <strong>{{ Number(stockTarget?.available_stock || 0) }}</strong> {{ stockTarget?.unit || 'pcs' }}</p>
                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">{{ localError }}</p>
                <div class="grid gap-3 sm:grid-cols-2">
                    <button type="button" class="rounded-xl border px-4 py-3 text-left" :style="stockForm.movement_type === 'in' ? { borderColor: '#0F766E', background: '#ECFDF5', color: '#0F766E' } : { borderColor: '#E2E8F0', color: '#475569' }" @click="stockForm.movement_type = 'in'">
                        <TrendingUp class="mb-1 h-4 w-4" /> Stock In
                    </button>
                    <button type="button" class="rounded-xl border px-4 py-3 text-left" :style="stockForm.movement_type === 'out' ? { borderColor: '#DC2626', background: '#FEF2F2', color: '#B91C1C' } : { borderColor: '#E2E8F0', color: '#475569' }" @click="stockForm.movement_type = 'out'">
                        <TrendingDown class="mb-1 h-4 w-4" /> Stock Out
                    </button>
                </div>
                <label class="mt-3 block text-sm text-[#475569]">Jumlah<input v-model.number="stockForm.qty" type="number" min="1" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></label>
                <label class="mt-3 block text-sm text-[#475569]">Catatan<input v-model="stockForm.notes" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" placeholder="Mis: restock supplier / adjustment"></label>
                <p class="mt-3 text-sm text-[#475569]">Proyeksi setelah disimpan: <strong class="text-[#0F172A]">{{ projectedStock }}</strong></p>
                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeStockModal">Cancel</button>
                    <button type="button" class="rounded-xl px-4 py-2 text-sm font-semibold text-white" style="background: #0F766E;" :disabled="saving" @click="submitMovement">
                        {{ saving ? 'Saving...' : 'Save Stock Movement' }}
                    </button>
                </div>
        </AdminModal>
    </div>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}
</style>
