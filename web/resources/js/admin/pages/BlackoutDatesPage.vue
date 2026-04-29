<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    blackoutDateRows: { type: Array, default: () => [] },
    branchOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingBlackoutDateId: { type: [Number, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits([
    'refresh-blackout-dates',
    'create-blackout-date',
    'update-blackout-date',
    'delete-blackout-date',
]);

const localError = ref('');
const draftMap = ref({});
const createForm = reactive({
    branch_id: '',
    blackout_date: '',
    reason: '',
    is_closed: true,
});

const draftFor = (row) => {
    const id = Number(row?.id || 0);

    if (!id) {
        return {
            branch_id: '',
            blackout_date: '',
            reason: '',
            is_closed: true,
        };
    }

    if (!draftMap.value[id]) {
        draftMap.value[id] = {
            branch_id: String(row?.branch_id || ''),
            blackout_date: String(row?.blackout_date || ''),
            reason: String(row?.reason || ''),
            is_closed: Boolean(row?.is_closed),
        };
    }

    return draftMap.value[id];
};

const submitCreate = () => {
    const branchId = Number(createForm.branch_id || 0);

    if (!branchId || !createForm.blackout_date) {
        localError.value = 'Branch and date are required.';
        return;
    }

    localError.value = '';
    emit('create-blackout-date', {
        branch_id: branchId,
        blackout_date: String(createForm.blackout_date),
        reason: String(createForm.reason || '').trim(),
        is_closed: Boolean(createForm.is_closed),
    });

    createForm.blackout_date = '';
    createForm.reason = '';
    createForm.is_closed = true;
};

const submitUpdate = (id) => {
    const blackoutId = Number(id || 0);
    const draft = draftMap.value[blackoutId];

    if (!blackoutId || !draft) {
        return;
    }

    emit('update-blackout-date', {
        id: blackoutId,
        payload: {
            branch_id: Number(draft.branch_id || 0),
            blackout_date: String(draft.blackout_date || ''),
            reason: String(draft.reason || '').trim(),
            is_closed: Boolean(draft.is_closed),
        },
    });
};

const submitDelete = (id) => {
    const blackoutId = Number(id || 0);
    if (!blackoutId) return;
    if (!window.confirm('Delete this blackout date?')) return;

    emit('delete-blackout-date', blackoutId);
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #7C2D12 0%, #9A3412 58%, #EA580C 100%); box-shadow: 0 6px 24px rgba(124,45,18,0.24);">
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Blackout Dates</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.82);">Manage fully closed dates or blocked booking dates per branch.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-blackout-dates')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <section class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
            <h3 class="text-sm font-semibold text-[#7C2D12]">Create Blackout Date</h3>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                <label class="text-xs text-[#64748B]">Branch
                    <select v-model="createForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">Select branch</option>
                        <option v-for="branch in branchOptions" :key="`blackout-create-branch-${branch.id}`" :value="String(branch.id)">
                            {{ branch.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Date
                    <input v-model="createForm.blackout_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B] xl:col-span-2">Reason
                    <input v-model="createForm.reason" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="flex items-center gap-2 text-sm text-[#334155]">
                    <input v-model="createForm.is_closed" type="checkbox" >
                    Full Closed
                </label>
            </div>
            <button type="button" class="mt-3 rounded-xl bg-[#C2410C] px-4 py-2 text-sm text-white" :disabled="saving" @click="submitCreate">
                {{ saving ? 'Saving...' : 'Create Blackout Date' }}
            </button>
        </section>

        <section class="overflow-hidden rounded-2xl border" style="border-color: #E2E8F0; background: #FFFFFF;">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #E2E8F0; background: #FFF7ED;">
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Branch & Date</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Reason</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Closed</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in blackoutDateRows" :key="`blackout-row-${row.id}`" style="border-bottom: 1px solid #F1F5F9;">
                        <td class="px-4 py-3 align-top">
                            <p class="text-sm font-semibold text-[#1F2937]">{{ row.branch_name }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ row.blackout_date_text }}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <input v-model="draftFor(row).reason" type="text" class="w-full rounded border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                        </td>
                        <td class="px-4 py-3 align-top">
                            <label class="flex items-center gap-2 text-sm text-[#475569]">
                                <input v-model="draftFor(row).is_closed" type="checkbox" >
                                Full Closed
                            </label>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-col gap-2">
                                <button type="button" class="rounded bg-[#EA580C] px-2 py-1 text-xs text-white" :disabled="saving" @click="submitUpdate(row.id)">Update</button>
                                <button type="button" class="rounded bg-[#DC2626] px-2 py-1 text-xs text-white" :disabled="deletingBlackoutDateId === row.id" @click="submitDelete(row.id)">
                                    {{ deletingBlackoutDateId === row.id ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!blackoutDateRows.length">
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No blackout dates found.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

