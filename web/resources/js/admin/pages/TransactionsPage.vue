<script setup>
import { ref, reactive } from 'vue';
import { ChevronDown, ReceiptText, WalletCards, Pencil, X } from 'lucide-vue-next';

const props = defineProps({
    panelTransactionsUrl: { type: String, default: '/admin/transactions' },
    normalizedRecentTransactions: { type: Array, default: () => [] },
    transactionTodayTotal: { type: String, default: 'Rp 0' },
    resolveMethodStyle: { type: Function, required: true },
    resolveTransactionStatus: { type: Function, required: true },
    canManageTransactions: { type: Boolean, default: false },
});

const emit = defineEmits(['transaction-updated']);

const getCsrfToken = () => {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
};

const formatRupiah = (value) => {
    const num = Number(value || 0);
    return 'Rp ' + num.toLocaleString('id-ID');
};

const formatDatetimeForInput = (isoString) => {
    if (!isoString) return '';
    const d = new Date(isoString);
    if (isNaN(d.getTime())) return '';
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
};

/* ── Transaction Edit Modal ── */
const editingTransaction = ref(null);
const editForm = reactive({ notes: '', discount_amount: 0 });
const txSaving = ref(false);
const txError = ref('');

const openEdit = (transaction) => {
    editingTransaction.value = transaction;
    editForm.notes = transaction.notes || '';
    editForm.discount_amount = transaction.discountAmount || 0;
    txError.value = '';
};

const closeTxEdit = () => {
    editingTransaction.value = null;
    txSaving.value = false;
    txError.value = '';
};

const saveTxEdit = async () => {
    if (!editingTransaction.value) return;
    txSaving.value = true;
    txError.value = '';
    try {
        const res = await fetch(`/admin/transactions/${editingTransaction.value.record_id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify({
                notes: editForm.notes,
                discount_amount: editForm.discount_amount,
            }),
        });
        const result = await res.json();
        if (!res.ok) throw new Error(result.message || 'Gagal menyimpan.');
        emit('transaction-updated', result.data);
        window.location.reload();
    } catch (err) {
        txError.value = err.message || 'Terjadi kesalahan.';
    } finally {
        txSaving.value = false;
    }
};

/* ── Payment Edit Modal ── */
const editingPayment = ref(null);
const paymentForm = reactive({
    method: 'cash',
    amount: 0,
    reference_no: '',
    paid_at: '',
});
const pmSaving = ref(false);
const pmError = ref('');

const PAYMENT_METHODS = [
    { value: 'cash', label: 'CASH' },
    { value: 'qris', label: 'QRIS' },
    { value: 'transfer', label: 'TRANSFER' },
    { value: 'card', label: 'CARD' },
];

const openPaymentEdit = (payment) => {
    editingPayment.value = payment;
    paymentForm.method = payment.methodLower || 'cash';
    paymentForm.amount = payment.amount || 0;
    paymentForm.reference_no = payment.referenceNo || '';
    paymentForm.paid_at = formatDatetimeForInput(payment.paidAt || '');
    pmError.value = '';
};

const closePaymentEdit = () => {
    editingPayment.value = null;
    pmSaving.value = false;
    pmError.value = '';
};

const savePaymentEdit = async () => {
    if (!editingPayment.value) return;
    pmSaving.value = true;
    pmError.value = '';
    try {
        const body = {};
        if (paymentForm.method !== editingPayment.value.methodLower) {
            body.method = paymentForm.method;
        }
        if (Math.abs(paymentForm.amount - (editingPayment.value.amount || 0)) > 0.001) {
            body.amount = paymentForm.amount;
        }
        if (paymentForm.reference_no !== (editingPayment.value.referenceNo || '')) {
            body.reference_no = paymentForm.reference_no;
        }
        const origPaidAt = formatDatetimeForInput(editingPayment.value.paidAt || '');
        if (paymentForm.paid_at !== origPaidAt) {
            body.paid_at = paymentForm.paid_at || null;
        }

        if (Object.keys(body).length === 0) {
            pmError.value = 'Tidak ada perubahan.';
            pmSaving.value = false;
            return;
        }

        const res = await fetch(`/admin/payments/${editingPayment.value.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
                'Accept': 'application/json',
            },
            body: JSON.stringify(body),
        });
        const result = await res.json();
        if (!res.ok) throw new Error(result.message || 'Gagal menyimpan pembayaran.');
        window.location.reload();
    } catch (err) {
        pmError.value = err.message || 'Terjadi kesalahan.';
    } finally {
        pmSaving.value = false;
    }
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-3xl px-6 py-5" style="background: linear-gradient(135deg, #1E1B4B 0%, #312E81 52%, #4338CA 100%); box-shadow: 0 8px 28px rgba(49,46,129,0.28);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-10 -top-10 h-36 w-36 rounded-full" style="background: rgba(199,210,254,0.2);"></div>
                <div class="absolute right-8 top-5 h-9 w-9 rounded-full" style="background: rgba(224,231,255,0.2);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3 text-white">
                <div>
                    <h2 class="text-[1.35rem] font-bold">Transactions</h2>
                    <p class="text-sm text-white/75">Riwayat transaksi dengan rincian item dan pembayaran.</p>
                </div>
                <span class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #312E81;">Baris bisa dibuka per transaksi</span>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Total Transactions</p>
                <p class="mt-1 text-2xl font-bold text-[#1F2937]">{{ normalizedRecentTransactions.length }}</p>
            </div>
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Visible Paid Amount</p>
                <p class="mt-1 text-xl font-bold text-[#059669]">{{ transactionTodayTotal }}</p>
            </div>
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Paid</p>
                <p class="mt-1 text-2xl font-bold text-[#059669]">{{ normalizedRecentTransactions.filter((item) => item.status === 'paid').length }}</p>
            </div>
            <div class="rounded-3xl border p-4" style="border-color: #E0E7FF; background: #FFFFFF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <p class="text-xs text-[#94A3B8]">Partial / Unpaid</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ normalizedRecentTransactions.filter((item) => item.status !== 'paid').length }}</p>
            </div>
        </div>

        <div class="space-y-4">
            <details v-for="transaction in normalizedRecentTransactions" :key="`transaction-module-row-${transaction.record_id || transaction.id}`" class="group overflow-hidden rounded-3xl border bg-white" style="border-color: #E0E7FF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                <summary class="list-none cursor-pointer px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="text-base font-bold text-[#312E81]">{{ transaction.id }}</p>
                                <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-semibold" :style="{ background: resolveTransactionStatus(transaction.status).bg, color: resolveTransactionStatus(transaction.status).color }">
                                    {{ resolveTransactionStatus(transaction.status).label }}
                                </span>
                                <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-semibold" :style="{ background: resolveMethodStyle(transaction.method).bg, color: resolveMethodStyle(transaction.method).color }">
                                    {{ transaction.method }}
                                </span>
                                <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-semibold text-[#64748B]">{{ transaction.items.length }} item</span>
                                <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-semibold text-[#64748B]">{{ transaction.payments.length }} payment</span>
                            </div>
                            <p class="mt-2 text-sm font-semibold text-[#0F172A]">{{ transaction.customer }}</p>
                            <p class="mt-1 text-xs text-[#64748B]">{{ transaction.branchName || '-' }} · {{ transaction.cashier }}</p>
                            <p class="mt-1 text-xs text-[#94A3B8]">{{ transaction.time }}</p>
                        </div>

                        <div class="flex items-start gap-4">
                            <div class="text-right">
                                <p class="text-xs text-[#94A3B8]">Paid / Total</p>
                                <p class="mt-1 text-sm font-bold text-[#0F172A]">{{ transaction.paidText }} / {{ transaction.totalText }}</p>
                                <p class="mt-1 text-xs text-[#64748B]">Sisa {{ transaction.remainingText }}</p>
                            </div>
                            <div class="flex h-10 w-10 items-center justify-center rounded-2xl bg-[#EEF2FF] text-[#4338CA] transition-transform group-open:rotate-180">
                                <ChevronDown class="h-4 w-4" />
                            </div>
                        </div>
                    </div>
                </summary>

                <div class="border-t px-5 py-5" style="border-color: #EEF2FF; background: #F8FAFC;">
                    <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1fr)_minmax(320px,0.95fr)]">
                        <section class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                            <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-[#0F172A]">
                                <ReceiptText class="h-4 w-4 text-[#2563EB]" />
                                Item Transaksi
                            </div>
                            <div class="max-h-[360px] space-y-3 overflow-y-auto pr-1">
                                <div v-for="(item, itemIndex) in transaction.items" :key="`transaction-item-${transaction.id}-${itemIndex}`" class="flex items-start justify-between gap-3 rounded-2xl bg-[#F8FAFC] px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-[#0F172A]">{{ item.itemName }}</p>
                                        <p class="mt-1 text-xs text-[#64748B]">{{ item.itemType }} · {{ item.qty }} x {{ item.unitPriceText }}</p>
                                    </div>
                                    <p class="text-sm font-semibold text-[#1F2937]">{{ item.lineTotalText }}</p>
                                </div>
                                <p v-if="!transaction.items.length" class="rounded-2xl bg-[#F8FAFC] px-4 py-8 text-center text-sm text-[#94A3B8]">Tidak ada item pada transaksi ini.</p>
                            </div>
                        </section>

                        <section class="space-y-4">
                            <article class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                                <div class="flex items-center justify-between gap-3 mb-4">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-[#0F172A]">
                                        <WalletCards class="h-4 w-4 text-[#059669]" />
                                        Riwayat Pembayaran
                                    </div>
                                </div>
                                <div class="max-h-[260px] space-y-3 overflow-y-auto pr-1">
                                    <div v-for="(payment, paymentIndex) in transaction.payments" :key="`transaction-payment-${transaction.id}-${paymentIndex}`" class="rounded-2xl bg-[#F8FAFC] px-4 py-3">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="text-sm font-semibold text-[#0F172A]">{{ payment.paymentCode }}</p>
                                                    <button v-if="canManageTransactions && payment.id"
                                                        @click="openPaymentEdit(payment)"
                                                        class="inline-flex items-center gap-1 rounded-xl px-2 py-1 text-[0.68rem] font-semibold transition-all hover:scale-105"
                                                        style="background: #EEF2FF; color: #4338CA;">
                                                        <Pencil class="h-3.5 w-3.5" />
                                                        Edit
                                                    </button>
                                                </div>
                                                <p class="mt-1 text-xs text-[#64748B]">{{ payment.method }} · {{ payment.cashierName }}</p>
                                                <p class="mt-1 text-xs text-[#94A3B8]">{{ payment.paidAtText }}</p>
                                                <p v-if="payment.referenceNo" class="mt-1 text-xs text-[#94A3B8]">Ref: {{ payment.referenceNo }}</p>
                                            </div>
                                            <p class="text-sm font-semibold text-[#059669] whitespace-nowrap">{{ payment.amountText }}</p>
                                        </div>
                                    </div>
                                    <p v-if="!transaction.payments.length" class="rounded-2xl bg-[#F8FAFC] px-4 py-8 text-center text-sm text-[#94A3B8]">Belum ada pembayaran pada transaksi ini.</p>
                                </div>
                            </article>

                            <article class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-[#0F172A]">
                                        <span>Detail Transaksi</span>
                                    </div>
                                    <button v-if="canManageTransactions" @click="openEdit(transaction)"
                                        class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-semibold transition-all hover:scale-105"
                                        style="background: #EEF2FF; color: #4338CA;">
                                        <Pencil class="h-3.5 w-3.5" />
                                        Edit
                                    </button>
                                </div>

                                <div v-if="transaction.lastModified"
                                    class="mb-3 rounded-xl px-3 py-2 text-xs font-medium"
                                    style="background: #FFFBEB; color: #D97706;">
                                    <span class="font-semibold">Terakhir diubah</span> oleh
                                    {{ transaction.lastModified.by }} ·
                                    {{ transaction.lastModified.at_human }}
                                    <br>
                                    {{ transaction.lastModified.message }}
                                </div>

                                <div class="mt-4 grid grid-cols-2 gap-3 text-sm">
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Phone</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.customerPhone || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Izin Share Foto</p>
                                        <p class="mt-1 font-medium" :class="transaction.socialMediaConsent ? 'text-[#059669]' : 'text-[#94A3B8]'">{{ transaction.socialMediaConsent ? 'Ya' : 'Tidak' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Change</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.changeText }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Notes</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.notes || '-' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Discount</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ formatRupiah(transaction.discountAmount) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-[#94A3B8]">Remaining</p>
                                        <p class="mt-1 font-medium text-[#0F172A]">{{ transaction.remainingText }}</p>
                                    </div>
                                </div>
                            </article>
                        </section>
                    </div>
                </div>
            </details>

            <div v-if="!normalizedRecentTransactions.length" class="rounded-3xl border bg-white px-4 py-14 text-center text-sm text-[#94A3B8]" style="border-color: #E0E7FF; box-shadow: 0 1px 3px rgba(49,46,129,0.08), 0 8px 20px rgba(49,46,129,0.08);">
                No recent transactions.
            </div>
        </div>
    </div>

    <!-- Transaction Edit Modal -->
    <Teleport to="body">
        <div v-if="editingTransaction" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);" @click.self="closeTxEdit">
            <div class="w-full max-w-lg rounded-3xl border bg-white p-6 shadow-2xl" style="border-color: #E0E7FF;">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold text-[#1F2937]">Edit Transaksi</h3>
                    <button @click="closeTxEdit" class="flex h-8 w-8 items-center justify-center rounded-xl text-[#94A3B8] hover:bg-[#F1F5F9] hover:text-[#475569] transition-colors">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Notes</label>
                        <textarea v-model="editForm.notes" rows="2"
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none transition-colors resize-none"
                            style="border-color: #E2E8F0; background: #F8FAFC; color: #1F2937;"
                            placeholder="Catatan transaksi..."></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Discount Amount (Rp)</label>
                        <input v-model.number="editForm.discount_amount" type="number" min="0"
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none transition-colors"
                            style="border-color: #E2E8F0; background: #F8FAFC; color: #1F2937;"
                            placeholder="0" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Paid Amount (Rp)</label>
                        <div class="w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #E2E8F0; background: #F1F5F9; color: #64748B;">
                            {{ editingTransaction ? formatRupiah(editingTransaction.paidAmount) : '-' }}
                        </div>
                        <p class="mt-1 text-xs text-[#94A3B8]">Ubah melalui edit pembayaran di "Riwayat Pembayaran"</p>
                    </div>

                    <div v-if="txError" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-medium text-red-600">
                        {{ txError }}
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button @click="closeTxEdit" :disabled="txSaving"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition-colors"
                        style="background: #F1F5F9; color: #475569;">
                        Batal
                    </button>
                    <button @click="saveTxEdit" :disabled="txSaving"
                        class="inline-flex items-center gap-2 rounded-xl px-5 py-2 text-sm font-semibold text-white transition-all hover:scale-105 disabled:opacity-60 disabled:hover:scale-100"
                        style="background: linear-gradient(135deg, #4338CA, #312E81);">
                        <svg v-if="txSaving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ txSaving ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Payment Edit Modal -->
    <Teleport to="body">
        <div v-if="editingPayment" class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.6); backdrop-filter: blur(4px);" @click.self="closePaymentEdit">
            <div class="w-full max-w-md rounded-3xl border bg-white p-6 shadow-2xl" style="border-color: #E0E7FF;">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-lg font-bold text-[#1F2937]">Edit Pembayaran</h3>
                    <button @click="closePaymentEdit" class="flex h-8 w-8 items-center justify-center rounded-xl text-[#94A3B8] hover:bg-[#F1F5F9] hover:text-[#475569] transition-colors">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Payment Method</label>
                        <select v-model="paymentForm.method"
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none transition-colors"
                            style="border-color: #E2E8F0; background: #F8FAFC; color: #1F2937;">
                            <option v-for="m in PAYMENT_METHODS" :key="m.value" :value="m.value">{{ m.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Amount (Rp)</label>
                        <input v-model.number="paymentForm.amount" type="number" min="0"
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none transition-colors"
                            style="border-color: #E2E8F0; background: #F8FAFC; color: #1F2937;"
                            placeholder="0" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Reference No</label>
                        <input v-model="paymentForm.reference_no" type="text" maxlength="120"
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none transition-colors"
                            style="border-color: #E2E8F0; background: #F8FAFC; color: #1F2937;"
                            placeholder="Nomor referensi (opsional)" />
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-[#64748B] mb-1.5">Paid At</label>
                        <input v-model="paymentForm.paid_at" type="datetime-local"
                            class="w-full rounded-xl border px-3 py-2 text-sm outline-none transition-colors"
                            style="border-color: #E2E8F0; background: #F8FAFC; color: #1F2937;" />
                    </div>

                    <div v-if="pmError" class="rounded-xl bg-red-50 px-3 py-2 text-xs font-medium text-red-600">
                        {{ pmError }}
                    </div>
                </div>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button @click="closePaymentEdit" :disabled="pmSaving"
                        class="rounded-xl px-4 py-2 text-sm font-semibold transition-colors"
                        style="background: #F1F5F9; color: #475569;">
                        Batal
                    </button>
                    <button @click="savePaymentEdit" :disabled="pmSaving"
                        class="inline-flex items-center gap-2 rounded-xl px-5 py-2 text-sm font-semibold text-white transition-all hover:scale-105 disabled:opacity-60 disabled:hover:scale-100"
                        style="background: linear-gradient(135deg, #4338CA, #312E81);">
                        <svg v-if="pmSaving" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                        </svg>
                        {{ pmSaving ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
