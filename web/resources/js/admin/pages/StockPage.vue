<script setup>
import { computed, reactive, ref } from 'vue';
import { RefreshCw, Package, TrendingUp, TrendingDown, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    addOnRows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-add-ons', 'move-stock']);

// ─── Modal state ───────────────────────────────────────────────
const stockModalOpen = ref(false);
const stockModalError = ref('');
const stockTarget = ref(null);
const toastVisible = ref(false);
let toastTimer = null;

const stockForm = reactive({
    movement_type: 'in',
    qty: 1,
    notes: '',
});

// ─── Data ──────────────────────────────────────────────────────
const stockRows = computed(() => props.addOnRows.filter(i => i.is_physical));

const stats = computed(() => {
    const total = stockRows.value.length;
    const low = stockRows.value.filter(i => i.available_stock > 0 && i.available_stock <= i.low_stock_threshold).length;
    const out = stockRows.value.filter(i => i.available_stock <= 0).length;
    return { total, low, out };
});

// ─── Helpers ───────────────────────────────────────────────────
const resolveStockTone = (row) => {
    const stock = Number(row?.available_stock ?? 0);
    const threshold = Number(row?.low_stock_threshold ?? 0);

    if (stock <= 0) return { label: 'Out of stock', variant: 'out' };
    if (stock <= threshold) return { label: 'Low stock', variant: 'low' };
    return { label: 'Ready', variant: 'ready' };
};

/**
 * Progress bar width (0–100).
 * Full = stock ≥ 3× threshold; scales down linearly below that.
 */
const stockProgress = (row) => {
    const stock = Number(row?.available_stock ?? 0);
    const max = Math.max(stock, Number(row?.low_stock_threshold ?? 0) * 3, 1);
    return Math.min(100, Math.round((stock / max) * 100));
};

const projectedStock = computed(() => {
    if (!stockTarget.value) return null;
    const current = stockTarget.value.available_stock;
    const qty = Number(stockForm.qty) || 0;
    return stockForm.movement_type === 'in' ? current + qty : current - qty;
});

// ─── Toast ─────────────────────────────────────────────────────
const showToast = () => {
    toastVisible.value = true;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { toastVisible.value = false; }, 2500);
};

// ─── Modal ─────────────────────────────────────────────────────
const openStockModal = (row) => {
    stockTarget.value = row;
    stockForm.movement_type = 'in';
    stockForm.qty = 1;
    stockForm.notes = '';
    stockModalError.value = '';
    stockModalOpen.value = true;
};

const closeStockModal = () => {
    stockModalOpen.value = false;
    stockTarget.value = null;
};

// ─── Validation & submit ───────────────────────────────────────
const validate = () => {
    if (!stockTarget.value) return false;
    if (stockForm.qty < 1) {
        stockModalError.value = 'Jumlah minimal 1.';
        return false;
    }
    if (stockForm.movement_type === 'out' && stockForm.qty > stockTarget.value.available_stock) {
        stockModalError.value = 'Stok tidak mencukupi untuk dikeluarkan.';
        return false;
    }
    stockModalError.value = '';
    return true;
};

const submit = async () => {
    if (!validate()) return;

    await emit('move-stock', {
        id: stockTarget.value.id,
        payload: {
            movement_type: stockForm.movement_type,
            qty: stockForm.qty,
            notes: stockForm.notes,
        },
    });

    closeStockModal();
    showToast();
};
</script>

<template>
    <div class="sm-wrapper">

        <!-- ── TOAST ─────────────────────────────────────────────── -->
        <Transition name="toast">
            <div v-if="toastVisible" class="sm-toast">
                <CheckCircle class="sm-toast__icon" />
                Stok berhasil diperbarui
            </div>
        </Transition>

        <!-- ── HEADER ─────────────────────────────────────────────── -->
        <div class="sm-header">
            <div class="sm-header__left">
                <span class="sm-header__eyebrow">
                    <Package class="sm-header__eyebrow-icon" />
                    Inventory Management
                </span>
                <h2 class="sm-header__title">Stock Add-on</h2>
                <p class="sm-header__sub">Barang fisik — update stok masuk &amp; keluar</p>
            </div>
            <button class="sm-btn-refresh" :disabled="loading" @click="emit('refresh-add-ons')">
                <RefreshCw class="sm-btn-refresh__icon" :class="{ 'sm-spin': loading }" />
                Refresh
            </button>
        </div>

        <!-- ── ERROR BANNER ───────────────────────────────────────── -->
        <div v-if="errorMessage" class="sm-error-banner">
            <span class="sm-error-banner__icon">⚠</span>
            {{ errorMessage }}
        </div>

        <!-- ── STAT CARDS ─────────────────────────────────────────── -->
        <div class="sm-stats">
            <div class="sm-stat sm-stat--blue">
                <p class="sm-stat__label">Total item</p>
                <p class="sm-stat__val">{{ stats.total }}</p>
                <div class="sm-stat__bar">
                    <div class="sm-stat__bar-fill" style="width: 100%" />
                </div>
            </div>
            <div class="sm-stat sm-stat--amber">
                <p class="sm-stat__label">Low stock</p>
                <p class="sm-stat__val">{{ stats.low }}</p>
                <div class="sm-stat__bar">
                    <div class="sm-stat__bar-fill"
                        :style="{ width: stats.total ? Math.round((stats.low / stats.total) * 100) + '%' : '0%' }" />
                </div>
            </div>
            <div class="sm-stat sm-stat--red">
                <p class="sm-stat__label">Out of stock</p>
                <p class="sm-stat__val">{{ stats.out }}</p>
                <div class="sm-stat__bar">
                    <div class="sm-stat__bar-fill"
                        :style="{ width: stats.total ? Math.round((stats.out / stats.total) * 100) + '%' : '0%' }" />
                </div>
            </div>
        </div>

        <!-- ── TABLE ──────────────────────────────────────────────── -->
        <div class="sm-table-card">
            <table class="sm-table">
                <thead>
                    <tr>
                        <th class="sm-th sm-th--left">Item</th>
                        <th class="sm-th sm-th--center">Stok tersedia</th>
                        <th class="sm-th sm-th--center">Status</th>
                        <th class="sm-th sm-th--right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- empty state -->
                    <tr v-if="stockRows.length === 0">
                        <td colspan="4" class="sm-empty">Tidak ada item fisik.</td>
                    </tr>

                    <tr v-for="row in stockRows" :key="row.id" class="sm-row">
                        <!-- item -->
                        <td class="sm-td">
                            <p class="sm-item__name">{{ row.name }}</p>
                            <p class="sm-item__sku">{{ row.sku }}</p>
                        </td>

                        <!-- stock number + threshold -->
                        <td class="sm-td sm-td--center">
                            <p class="sm-stock__num">{{ row.available_stock }}</p>
                            <p class="sm-stock__threshold">threshold: {{ row.low_stock_threshold }}</p>
                        </td>

                        <!-- status badge + mini progress -->
                        <td class="sm-td sm-td--center">
                            <span class="sm-badge" :class="`sm-badge--${resolveStockTone(row).variant}`">
                                <span class="sm-badge__dot" />
                                {{ resolveStockTone(row).label }}
                            </span>
                            <div class="sm-prog">
                                <div class="sm-prog__fill" :class="`sm-prog__fill--${resolveStockTone(row).variant}`"
                                    :style="{ width: stockProgress(row) + '%' }" />
                            </div>
                        </td>

                        <!-- action -->
                        <td class="sm-td sm-td--right">
                            <button class="sm-btn-update" @click="openStockModal(row)">
                                Update stok
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- ── MODAL ──────────────────────────────────────────────── -->
        <Transition name="modal">
            <div v-if="stockModalOpen" class="sm-modal-bg" @click.self="closeStockModal">
                <div class="sm-modal" role="dialog" aria-modal="true"
                    :aria-labelledby="'modal-title-' + stockTarget?.id">

                    <!-- header -->
                    <div class="sm-modal__header">
                        <div>
                            <p :id="'modal-title-' + stockTarget?.id" class="sm-modal__title">
                                {{ stockTarget?.name }}
                            </p>
                            <p class="sm-modal__sub">{{ stockTarget?.sku }}</p>
                        </div>
                        <button class="sm-modal__close" @click="closeStockModal" aria-label="Tutup modal">×</button>
                    </div>

                    <!-- info boxes -->
                    <div class="sm-modal__info-row">
                        <div class="sm-modal__info-box">
                            <p class="sm-ib__label">Stok sekarang</p>
                            <p class="sm-ib__val">{{ stockTarget?.available_stock }}</p>
                        </div>
                        <div class="sm-modal__info-box">
                            <p class="sm-ib__label">Threshold</p>
                            <p class="sm-ib__val">{{ stockTarget?.low_stock_threshold }}</p>
                        </div>
                        <div class="sm-modal__info-box">
                            <p class="sm-ib__label">Setelah disimpan</p>
                            <p class="sm-ib__val" :class="{
                                'sm-ib__val--danger': projectedStock !== null && projectedStock < 0,
                                'sm-ib__val--warning': projectedStock !== null && projectedStock >= 0 && projectedStock <= (stockTarget?.low_stock_threshold ?? 0),
                                'sm-ib__val--success': projectedStock !== null && projectedStock > (stockTarget?.low_stock_threshold ?? 0),
                            }">
                                {{ projectedStock ?? '—' }}
                            </p>
                        </div>
                    </div>

                    <!-- movement type toggle -->
                    <div class="sm-form__group">
                        <label class="sm-form__label">Tipe pergerakan</label>
                        <div class="sm-toggle-row">
                            <button class="sm-toggle" :class="stockForm.movement_type === 'in' ? 'sm-toggle--in' : ''"
                                @click="stockForm.movement_type = 'in'">
                                <TrendingUp class="sm-toggle__icon" />
                                <span>Stock In</span>
                                <span class="sm-toggle__hint">Tambah stok</span>
                            </button>
                            <button class="sm-toggle" :class="stockForm.movement_type === 'out' ? 'sm-toggle--out' : ''"
                                @click="stockForm.movement_type = 'out'">
                                <TrendingDown class="sm-toggle__icon" />
                                <span>Stock Out</span>
                                <span class="sm-toggle__hint">Kurangi stok</span>
                            </button>
                        </div>
                    </div>

                    <!-- qty -->
                    <div class="sm-form__group">
                        <label class="sm-form__label" for="sm-qty">Jumlah</label>
                        <input id="sm-qty" v-model.number="stockForm.qty" type="number" min="1" class="sm-form__input"
                            placeholder="Masukkan jumlah" />
                    </div>

                    <!-- notes -->
                    <div class="sm-form__group">
                        <label class="sm-form__label" for="sm-notes">Catatan <span
                                class="sm-form__optional">(opsional)</span></label>
                        <input id="sm-notes" v-model="stockForm.notes" type="text" class="sm-form__input"
                            placeholder="Mis: restock dari supplier" />
                    </div>

                    <!-- validation error -->
                    <div v-if="stockModalError" class="sm-modal__error">
                        <span>⚠</span> {{ stockModalError }}
                    </div>

                    <!-- footer -->
                    <div class="sm-modal__footer">
                        <button class="sm-btn-cancel" @click="closeStockModal">Batal</button>
                        <button class="sm-btn-save" :disabled="saving" @click="submit">
                            {{ saving ? 'Menyimpan…' : 'Simpan perubahan' }}
                        </button>
                    </div>

                </div>
            </div>
        </Transition>

    </div>
</template>

<style scoped>
/* ─────────────────────────────────────────────────
   Layout wrapper
───────────────────────────────────────────────── */
.sm-wrapper {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 1.25rem;
    font-family: inherit;
}

/* ─────────────────────────────────────────────────
   Toast
───────────────────────────────────────────────── */
.sm-toast {
    position: fixed;
    top: 1.25rem;
    right: 1.25rem;
    z-index: 9999;
    background: #3B6D11;
    color: #EAF3DE;
    padding: 10px 16px;
    border-radius: 10px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

.sm-toast__icon {
    width: 15px;
    height: 15px;
}

.toast-enter-active,
.toast-leave-active {
    transition: opacity 0.3s, transform 0.3s;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateY(-8px);
}

/* ─────────────────────────────────────────────────
   Header
───────────────────────────────────────────────── */
.sm-header {
    background: linear-gradient(135deg, #0F766E, #059669);
    border-radius: 14px;
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.sm-header__left {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sm-header__eyebrow {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: rgba(255, 255, 255, 0.7);
}

.sm-header__eyebrow-icon {
    width: 13px;
    height: 13px;
}

.sm-header__title {
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    margin: 0;
}

.sm-header__sub {
    font-size: 13px;
    color: rgba(255, 255, 255, 0.65);
    margin: 0;
}

.sm-btn-refresh {
    background: rgba(255, 255, 255, 0.15);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: #fff;
    border-radius: 8px;
    padding: 8px 14px;
    font-size: 13px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 6px;
    transition: background 0.15s;
    white-space: nowrap;
    flex-shrink: 0;
}

.sm-btn-refresh:hover:not(:disabled) {
    background: rgba(255, 255, 255, 0.25);
}

.sm-btn-refresh:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.sm-btn-refresh__icon {
    width: 14px;
    height: 14px;
}

.sm-spin {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    to {
        transform: rotate(360deg);
    }
}

/* ─────────────────────────────────────────────────
   Error banner
───────────────────────────────────────────────── */
.sm-error-banner {
    background: #FCEBEB;
    color: #A32D2D;
    border: 1px solid #F7C1C1;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 13px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.sm-error-banner__icon {
    font-size: 15px;
}

/* ─────────────────────────────────────────────────
   Stats
───────────────────────────────────────────────── */
.sm-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;
}

.sm-stat {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem 1.25rem;
}

.sm-stat__label {
    font-size: 12px;
    color: #6b7280;
    margin: 0 0 4px;
}

.sm-stat__val {
    font-size: 26px;
    font-weight: 600;
    margin: 0 0 8px;
}

.sm-stat__bar {
    height: 3px;
    border-radius: 2px;
    background: #e5e7eb;
    overflow: hidden;
}

.sm-stat__bar-fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.4s;
}

.sm-stat--blue .sm-stat__val {
    color: #185FA5;
}

.sm-stat--blue .sm-stat__bar-fill {
    background: #378ADD;
}

.sm-stat--amber .sm-stat__val {
    color: #BA7517;
}

.sm-stat--amber .sm-stat__bar-fill {
    background: #BA7517;
}

.sm-stat--red .sm-stat__val {
    color: #A32D2D;
}

.sm-stat--red .sm-stat__bar-fill {
    background: #E24B4A;
}

/* ─────────────────────────────────────────────────
   Table card
───────────────────────────────────────────────── */
.sm-table-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    overflow: hidden;
}

.sm-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.sm-th {
    padding: 10px 14px;
    font-size: 12px;
    font-weight: 600;
    color: #6b7280;
    background: #f9fafb;
    text-align: left;
    letter-spacing: 0.02em;
}

.sm-th--center {
    text-align: center;
}

.sm-th--right {
    text-align: right;
}

.sm-row {
    border-top: 1px solid #f3f4f6;
    transition: background 0.1s;
}

.sm-row:hover {
    background: #fafafa;
}

.sm-td {
    padding: 12px 14px;
    vertical-align: middle;
}

.sm-td--center {
    text-align: center;
}

.sm-td--right {
    text-align: right;
}

.sm-item__name {
    font-weight: 600;
    font-size: 14px;
    color: #111827;
    margin: 0;
}

.sm-item__sku {
    font-size: 11px;
    color: #9ca3af;
    margin: 2px 0 0;
}

.sm-stock__num {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.sm-stock__threshold {
    font-size: 11px;
    color: #9ca3af;
    margin: 2px 0 0;
}

.sm-empty {
    padding: 3rem;
    text-align: center;
    color: #9ca3af;
    font-size: 14px;
}

/* ─────────────────────────────────────────────────
   Status badge
───────────────────────────────────────────────── */
.sm-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 4px 10px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 500;
}

.sm-badge__dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    flex-shrink: 0;
}

.sm-badge--ready {
    background: #EAF3DE;
    color: #3B6D11;
}

.sm-badge--ready .sm-badge__dot {
    background: #639922;
}

.sm-badge--low {
    background: #FAEEDA;
    color: #854F0B;
}

.sm-badge--low .sm-badge__dot {
    background: #BA7517;
}

.sm-badge--out {
    background: #FCEBEB;
    color: #A32D2D;
}

.sm-badge--out .sm-badge__dot {
    background: #E24B4A;
}

/* ─────────────────────────────────────────────────
   Mini progress bar
───────────────────────────────────────────────── */
.sm-prog {
    width: 72px;
    height: 3px;
    background: #e5e7eb;
    border-radius: 2px;
    overflow: hidden;
    margin: 6px auto 0;
}

.sm-prog__fill {
    height: 100%;
    border-radius: 2px;
    transition: width 0.4s;
}

.sm-prog__fill--ready {
    background: #639922;
}

.sm-prog__fill--low {
    background: #BA7517;
}

.sm-prog__fill--out {
    background: #E24B4A;
}

/* ─────────────────────────────────────────────────
   Update button
───────────────────────────────────────────────── */
.sm-btn-update {
    background: #f9fafb;
    border: 1px solid #d1d5db;
    color: #374151;
    border-radius: 8px;
    padding: 6px 12px;
    font-size: 12px;
    cursor: pointer;
    transition: all 0.15s;
}

.sm-btn-update:hover {
    background: #f3f4f6;
    border-color: #9ca3af;
}

/* ─────────────────────────────────────────────────
   Modal overlay
───────────────────────────────────────────────── */
.sm-modal-bg {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
    padding: 1rem;
}

.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s;
}

.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}

.modal-enter-active .sm-modal,
.modal-leave-active .sm-modal {
    transition: transform 0.2s;
}

.modal-enter-from .sm-modal {
    transform: translateY(10px);
}

.modal-leave-to .sm-modal {
    transform: translateY(10px);
}

.sm-modal {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #e5e7eb;
    padding: 1.5rem;
    width: 100%;
    max-width: 400px;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
}

/* modal header */
.sm-modal__header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.sm-modal__title {
    font-size: 15px;
    font-weight: 600;
    color: #111827;
    margin: 0;
}

.sm-modal__sub {
    font-size: 12px;
    color: #9ca3af;
    margin: 2px 0 0;
}

.sm-modal__close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: #9ca3af;
    line-height: 1;
    padding: 2px 6px;
    border-radius: 6px;
    transition: background 0.1s, color 0.1s;
}

.sm-modal__close:hover {
    background: #f3f4f6;
    color: #374151;
}

/* info boxes */
.sm-modal__info-row {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 8px;
    margin-bottom: 1.25rem;
}

.sm-modal__info-box {
    background: #f9fafb;
    border: 1px solid #f3f4f6;
    border-radius: 8px;
    padding: 10px 12px;
}

.sm-ib__label {
    font-size: 11px;
    color: #6b7280;
    margin: 0 0 4px;
}

.sm-ib__val {
    font-size: 18px;
    font-weight: 600;
    color: #111827;
    margin: 0;
    transition: color 0.2s;
}

.sm-ib__val--success {
    color: #3B6D11;
}

.sm-ib__val--warning {
    color: #854F0B;
}

.sm-ib__val--danger {
    color: #A32D2D;
}

/* toggle */
.sm-toggle-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 8px;
}

.sm-toggle {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
    padding: 10px;
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    background: #f9fafb;
    cursor: pointer;
    font-size: 13px;
    font-weight: 500;
    color: #6b7280;
    transition: all 0.15s;
}

.sm-toggle:hover {
    border-color: #d1d5db;
    background: #f3f4f6;
}

.sm-toggle__icon {
    width: 18px;
    height: 18px;
}

.sm-toggle__hint {
    font-size: 11px;
    font-weight: 400;
    color: #9ca3af;
}

.sm-toggle--in {
    border-color: #0F6E56;
    background: #E1F5EE;
    color: #085041;
}

.sm-toggle--in .sm-toggle__hint {
    color: #3B6D11;
}

.sm-toggle--out {
    border-color: #993C1D;
    background: #FAECE7;
    color: #4A1B0C;
}

.sm-toggle--out .sm-toggle__hint {
    color: #854F0B;
}

/* form */
.sm-form__group {
    margin-bottom: 14px;
}

.sm-form__label {
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    display: block;
    margin-bottom: 5px;
}

.sm-form__optional {
    font-weight: 400;
    color: #9ca3af;
}

.sm-form__input {
    width: 100%;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 9px 11px;
    font-size: 13px;
    color: #111827;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.sm-form__input:focus {
    border-color: #0F6E56;
    box-shadow: 0 0 0 3px rgba(15, 118, 110, 0.12);
}

/* error */
.sm-modal__error {
    background: #FCEBEB;
    color: #A32D2D;
    border: 1px solid #F7C1C1;
    border-radius: 8px;
    padding: 9px 12px;
    font-size: 12px;
    display: flex;
    align-items: center;
    gap: 6px;
    margin-bottom: 12px;
}

/* modal footer */
.sm-modal__footer {
    display: flex;
    justify-content: flex-end;
    gap: 8px;
    padding-top: 4px;
}

.sm-btn-cancel {
    background: none;
    border: 1px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 16px;
    font-size: 13px;
    cursor: pointer;
    color: #374151;
    transition: all 0.15s;
}

.sm-btn-cancel:hover {
    background: #f3f4f6;
}

.sm-btn-save {
    background: #0F766E;
    border: none;
    border-radius: 8px;
    padding: 8px 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    color: #fff;
    transition: background 0.15s;
}

.sm-btn-save:hover:not(:disabled) {
    background: #0F6E56;
}

.sm-btn-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

/* ─────────────────────────────────────────────────
   Responsive
───────────────────────────────────────────────── */
@media (max-width: 640px) {
    .sm-stats {
        grid-template-columns: 1fr;
    }

    .sm-modal__info-row {
        grid-template-columns: 1fr 1fr;
    }
}
</style>