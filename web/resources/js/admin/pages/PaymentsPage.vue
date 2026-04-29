<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    paymentRows: { type: Array, default: () => [] },
    transactionOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-payments', 'create-payment']);

const localError = ref('');
const form = reactive({
    transaction_id: '',
    method: 'cash',
    amount: '',
    reference_no: '',
    notes: '',
});

const submitCreate = () => {
    const transactionId = Number(form.transaction_id || 0);
    const amount = Number(form.amount || 0);

    if (!transactionId || amount <= 0) {
        localError.value = 'Transaction and amount are required.';
        return;
    }

    localError.value = '';
    emit('create-payment', {
        transaction_id: transactionId,
        payload: {
            method: String(form.method || 'cash'),
            amount,
            reference_no: String(form.reference_no || '').trim(),
            notes: String(form.notes || '').trim(),
        },
    });

    form.amount = '';
    form.reference_no = '';
    form.notes = '';
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #581C87 0%, #7E22CE 58%, #A855F7 100%); box-shadow: 0 6px 24px rgba(88,28,135,0.24);">
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Payments</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.82);">Track payment history and add manual payments to open transactions.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-payments')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <section class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
            <h3 class="text-sm font-semibold text-[#581C87]">Add Payment</h3>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-5">
                <label class="text-xs text-[#64748B] xl:col-span-2">Transaction
                    <select v-model="form.transaction_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">Select open transaction</option>
                        <option v-for="trx in transactionOptions" :key="`payment-trx-${trx.id}`" :value="String(trx.id)">
                            {{ trx.transaction_code }} - {{ trx.customer_name }} ({{ trx.remaining_amount_text }})
                        </option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Method
                    <select v-model="form.method" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="cash">Cash</option>
                        <option value="qris">QRIS</option>
                        <option value="transfer">Transfer</option>
                        <option value="card">Card</option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Amount
                    <input v-model="form.amount" type="number" min="1" step="1000" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B]">Reference
                    <input v-model="form.reference_no" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
            </div>
            <label class="mt-3 block text-xs text-[#64748B]">Notes
                <input v-model="form.notes" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
            </label>
            <button type="button" class="mt-3 rounded-xl bg-[#7E22CE] px-4 py-2 text-sm text-white" :disabled="saving" @click="submitCreate">
                {{ saving ? 'Saving...' : 'Add Payment' }}
            </button>
        </section>

        <section class="overflow-hidden rounded-2xl border" style="border-color: #E2E8F0; background: #FFFFFF;">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #E2E8F0; background: #FAF5FF;">
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Payment</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Transaction</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Amount</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Method</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Cashier</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in paymentRows" :key="`payment-row-${row.id}`" style="border-bottom: 1px solid #F1F5F9;">
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-[#1F2937]">{{ row.payment_code }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ row.paid_at_text }}</p>
                        </td>
                        <td class="px-4 py-3">
                            <p class="text-sm font-semibold text-[#6D28D9]">{{ row.transaction_code }}</p>
                            <p class="text-xs text-[#64748B]">{{ row.customer_name }} - {{ row.branch_name }}</p>
                        </td>
                        <td class="px-4 py-3 text-sm font-semibold text-[#1F2937]">{{ row.amount_text }}</td>
                        <td class="px-4 py-3 text-sm text-[#64748B]">{{ row.method }}</td>
                        <td class="px-4 py-3 text-sm text-[#64748B]">{{ row.cashier_name }}</td>
                    </tr>
                    <tr v-if="!paymentRows.length">
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No payments found.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

