<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    settlements: { type: Array, default: () => [] },
    openSessions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh', 'verify', 'create-correction']);

const selectedId = ref(null);
const verifyForm = reactive({ owner_received_cash: '', notes: '' });
const correctionForm = reactive({ amount: '', reason: '', affects_cash: true });
const localError = ref('');

const selectedSettlement = computed(() => {
    const rows = props.settlements || [];
    const selected = rows.find((row) => Number(row.id) === Number(selectedId.value));

    return selected || rows[0] || null;
});

watch(
    () => props.settlements,
    (rows) => {
        if (!selectedId.value && Array.isArray(rows) && rows.length) {
            selectedId.value = Number(rows[0].id || 0);
        }
    },
    { immediate: true },
);

const submitVerify = () => {
    const settlement = selectedSettlement.value;
    const amount = Number(verifyForm.owner_received_cash || 0);

    if (!settlement || amount < 0) return;

    emit('verify', {
        settlement_id: Number(settlement.id),
        payload: {
            owner_received_cash: amount,
            notes: String(verifyForm.notes || '').trim(),
        },
    });

    verifyForm.owner_received_cash = '';
    verifyForm.notes = '';
};

const submitCorrection = () => {
    const settlement = selectedSettlement.value;
    const amount = Number(correctionForm.amount || 0);
    const reason = String(correctionForm.reason || '').trim();

    if (!settlement || amount === 0 || reason === '') {
        localError.value = 'Nominal koreksi dan alasan wajib diisi.';
        return;
    }

    localError.value = '';
    emit('create-correction', {
        settlement_id: Number(settlement.id),
        payload: {
            amount,
            reason,
            affects_cash: Boolean(correctionForm.affects_cash),
        },
    });

    correctionForm.amount = '';
    correctionForm.reason = '';
};

const snapshot = computed(() => selectedSettlement.value?.snapshot || {});
const packageRows = computed(() => Array.isArray(snapshot.value.package_sales) ? snapshot.value.package_sales : []);
const expenseRows = computed(() => Array.isArray(snapshot.value.expenses) ? snapshot.value.expenses : []);
const dpRows = computed(() => Array.isArray(snapshot.value.dp_info) ? snapshot.value.dp_info : []);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-3xl px-6 py-5" style="background: linear-gradient(135deg, #0F172A 0%, #1E3A8A 55%, #0369A1 100%); box-shadow: 0 10px 28px rgba(15,23,42,0.24);">
            <div class="relative flex flex-wrap items-start justify-between gap-3 text-white">
                <div>
                    <h2 class="text-[1.35rem] font-bold">Setoran Kasir</h2>
                    <p class="text-sm text-white/80">Cocokkan struk setoran kasir dengan uang cash yang diterima owner.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #DC2626;">
            {{ localError || errorMessage }}
        </p>

        <section v-if="openSessions.length" class="rounded-3xl border p-4" style="border-color: #FDE68A; background: #FFFBEB;">
            <h3 class="text-sm font-semibold text-[#92400E]">Sesi Belum Ditutup</h3>
            <div class="mt-3 grid gap-2 md:grid-cols-2 xl:grid-cols-3">
                <div v-for="session in openSessions" :key="`open-session-${session.id}`" class="rounded-2xl bg-white px-4 py-3">
                    <p class="text-sm font-semibold text-[#111827]">{{ session.cashier_name }}</p>
                    <p class="text-xs text-[#92400E]">{{ session.branch_name }} - {{ session.business_date_text }}</p>
                    <p class="mt-1 text-xs text-[#64748B]">Dibuka {{ session.opened_at_text }}</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[380px_1fr]">
            <section class="rounded-3xl border bg-white p-4" style="border-color: #E2E8F0;">
                <h3 class="mb-3 text-sm font-semibold text-[#0F172A]">Daftar Setoran</h3>
                <div class="max-h-[620px] space-y-2 overflow-y-auto pr-1">
                    <button
                        v-for="row in settlements"
                        :key="`settlement-${row.id}`"
                        type="button"
                        class="w-full rounded-2xl border px-4 py-3 text-left transition"
                        :style="Number(row.id) === Number(selectedSettlement?.id) ? 'border-color:#2563EB;background:#EFF6FF;' : 'border-color:#E2E8F0;background:#FFFFFF;'"
                        @click="selectedId = Number(row.id)"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-sm font-bold text-[#0F172A]">{{ row.settlement_code }}</p>
                            <span v-if="row.is_late_close" class="rounded-full bg-[#FEF3C7] px-2 py-0.5 text-[0.68rem] font-semibold text-[#92400E]">Late</span>
                        </div>
                        <p class="mt-1 text-xs text-[#64748B]">{{ row.cashier_name }} - {{ row.business_date_text }}</p>
                        <p class="mt-2 text-sm font-semibold text-[#059669]">{{ row.final_cash_to_deposit_text }}</p>
                    </button>
                    <p v-if="!settlements.length" class="py-12 text-center text-sm text-[#94A3B8]">Belum ada setoran kasir.</p>
                </div>
            </section>

            <section v-if="selectedSettlement" class="space-y-4">
                <div class="rounded-3xl border bg-white p-5" style="border-color: #E2E8F0;">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-[#64748B]">No Setoran</p>
                            <h3 class="mt-1 text-2xl font-black text-[#0F172A]">{{ selectedSettlement.settlement_code }}</h3>
                            <p class="mt-1 text-sm text-[#64748B]">{{ selectedSettlement.period_label }}</p>
                        </div>
                        <div class="rounded-2xl bg-[#F8FAFC] px-4 py-3 text-right">
                            <p class="text-xs text-[#64748B]">JML. Disetor Cash</p>
                            <p class="mt-1 text-xl font-black text-[#059669]">{{ selectedSettlement.final_cash_to_deposit_text }}</p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="rounded-2xl border bg-white p-4" style="border-color:#E2E8F0;"><p class="text-xs text-[#64748B]">Total Penjualan</p><p class="mt-1 font-bold">{{ selectedSettlement.total_sales_text }}</p></div>
                    <div class="rounded-2xl border bg-white p-4" style="border-color:#E2E8F0;"><p class="text-xs text-[#64748B]">Cash Diterima</p><p class="mt-1 font-bold">{{ selectedSettlement.cash_received_text }}</p></div>
                    <div class="rounded-2xl border bg-white p-4" style="border-color:#E2E8F0;"><p class="text-xs text-[#64748B]">Non Cash</p><p class="mt-1 font-bold">{{ selectedSettlement.non_cash_received_text }}</p></div>
                    <div class="rounded-2xl border bg-white p-4" style="border-color:#E2E8F0;"><p class="text-xs text-[#64748B]">Pengeluaran</p><p class="mt-1 font-bold">{{ selectedSettlement.cash_expenses_total_text }}</p></div>
                </div>

                <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
                    <section class="rounded-3xl border bg-white p-5" style="border-color:#E2E8F0;">
                        <h4 class="text-sm font-semibold text-[#0F172A]">Penjualan Per Paket</h4>
                        <div class="mt-3 space-y-2">
                            <div v-for="row in packageRows" :key="`package-${row.package_name}`" class="flex justify-between text-sm"><span>{{ row.package_name }}</span><strong>{{ row.amount_text }}</strong></div>
                            <p v-if="!packageRows.length" class="text-xs text-[#94A3B8]">Tidak ada data paket.</p>
                        </div>
                    </section>
                    <section class="rounded-3xl border bg-white p-5" style="border-color:#E2E8F0;">
                        <h4 class="text-sm font-semibold text-[#0F172A]">Info DP</h4>
                        <div class="mt-3 space-y-2">
                            <div v-for="row in dpRows" :key="`dp-${row.stage}`" class="flex justify-between text-sm"><span>{{ row.label }}</span><strong>{{ row.amount_text }}</strong></div>
                        </div>
                    </section>
                    <section class="rounded-3xl border bg-white p-5" style="border-color:#E2E8F0;">
                        <h4 class="text-sm font-semibold text-[#0F172A]">Detail Pengeluaran</h4>
                        <div class="mt-3 space-y-2">
                            <div v-for="row in expenseRows" :key="`expense-${row.id}`" class="flex justify-between text-sm"><span>{{ row.title }}</span><strong>{{ row.amount_text }}</strong></div>
                            <p v-if="!expenseRows.length" class="text-xs text-[#94A3B8]">Tidak ada pengeluaran.</p>
                        </div>
                    </section>
                </div>

                <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                    <div class="rounded-3xl border bg-white p-5" style="border-color:#E2E8F0;">
                        <h4 class="text-sm font-semibold text-[#0F172A]">Cocokkan Uang Owner</h4>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <input v-model="verifyForm.owner_received_cash" type="number" class="rounded-xl border px-3 py-2 text-sm" placeholder="Uang cash diterima" style="border-color:#CBD5E1;">
                            <input v-model="verifyForm.notes" type="text" class="rounded-xl border px-3 py-2 text-sm" placeholder="Catatan" style="border-color:#CBD5E1;">
                        </div>
                        <button type="button" class="mt-3 rounded-xl bg-[#2563EB] px-4 py-2 text-sm font-semibold text-white" :disabled="saving" @click="submitVerify">Simpan Pencocokan</button>
                    </div>
                    <div class="rounded-3xl border bg-white p-5" style="border-color:#E2E8F0;">
                        <h4 class="text-sm font-semibold text-[#0F172A]">Koreksi Setoran</h4>
                        <div class="mt-3 grid gap-3 md:grid-cols-2">
                            <input v-model="correctionForm.amount" type="number" class="rounded-xl border px-3 py-2 text-sm" placeholder="Nominal +/-" style="border-color:#CBD5E1;">
                            <input v-model="correctionForm.reason" type="text" class="rounded-xl border px-3 py-2 text-sm" placeholder="Alasan" style="border-color:#CBD5E1;">
                        </div>
                        <label class="mt-3 flex items-center gap-2 text-xs text-[#64748B]"><input v-model="correctionForm.affects_cash" type="checkbox"> Mempengaruhi cash disetor</label>
                        <button type="button" class="mt-3 rounded-xl bg-[#0F172A] px-4 py-2 text-sm font-semibold text-white" :disabled="saving" @click="submitCorrection">Tambah Koreksi</button>
                    </div>
                </section>
            </section>
        </div>
    </div>
</template>
