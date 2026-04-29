<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    branchRows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingBranchId: { type: [Number, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-branches', 'create-branch', 'update-branch', 'delete-branch']);

const localError = ref('');
const draftMap = ref({});
const createForm = reactive({
    code: '',
    name: '',
    timezone: 'Asia/Jakarta',
    phone: '',
    address: '',
    is_active: true,
});

const draftFor = (row) => {
    const id = Number(row?.id || 0);

    if (!id) {
        return {
            code: '',
            name: '',
            timezone: 'Asia/Jakarta',
            phone: '',
            address: '',
            is_active: true,
        };
    }

    if (!draftMap.value[id]) {
        draftMap.value[id] = {
            code: String(row?.code || ''),
            name: String(row?.name || ''),
            timezone: String(row?.timezone || 'Asia/Jakarta'),
            phone: String(row?.phone || ''),
            address: String(row?.address || ''),
            is_active: Boolean(row?.is_active),
        };
    }

    return draftMap.value[id];
};

const submitCreate = () => {
    if (!String(createForm.name || '').trim()) {
        localError.value = 'Branch name is required.';
        return;
    }

    localError.value = '';
    emit('create-branch', {
        code: String(createForm.code || '').trim() || null,
        name: String(createForm.name || '').trim(),
        timezone: String(createForm.timezone || 'Asia/Jakarta').trim() || 'Asia/Jakarta',
        phone: String(createForm.phone || '').trim(),
        address: String(createForm.address || '').trim(),
        is_active: Boolean(createForm.is_active),
    });

    createForm.code = '';
    createForm.name = '';
    createForm.timezone = 'Asia/Jakarta';
    createForm.phone = '';
    createForm.address = '';
    createForm.is_active = true;
};

const submitUpdate = (id) => {
    const branchId = Number(id || 0);
    const draft = draftMap.value[branchId];

    if (!branchId || !draft) {
        return;
    }

    if (!String(draft.name || '').trim()) {
        localError.value = 'Branch name is required.';
        return;
    }

    localError.value = '';
    emit('update-branch', {
        id: branchId,
        payload: {
            code: String(draft.code || '').trim(),
            name: String(draft.name || '').trim(),
            timezone: String(draft.timezone || 'Asia/Jakarta').trim() || 'Asia/Jakarta',
            phone: String(draft.phone || '').trim(),
            address: String(draft.address || '').trim(),
            is_active: Boolean(draft.is_active),
        },
    });
};

const submitDelete = (id) => {
    const branchId = Number(id || 0);

    if (!branchId) {
        return;
    }

    if (!window.confirm('Delete this branch? If linked to data, it may be deactivated instead.')) {
        return;
    }

    emit('delete-branch', branchId);
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #0B132B 0%, #1C2541 58%, #3A506B 100%); box-shadow: 0 6px 24px rgba(11,19,43,0.24);">
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Branches</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.8);">Manage all operational branches used by admin modules.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-branches')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <section class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
            <h3 class="text-sm font-semibold text-[#1F2937]">Create Branch</h3>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label class="text-xs text-[#64748B]">Code
                    <input v-model="createForm.code" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" placeholder="Auto when empty" >
                </label>
                <label class="text-xs text-[#64748B]">Name
                    <input v-model="createForm.name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B]">Timezone
                    <input v-model="createForm.timezone" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B]">Phone
                    <input v-model="createForm.phone" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B] md:col-span-2">Address
                    <input v-model="createForm.address" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                    <input v-model="createForm.is_active" type="checkbox" >
                    Active
                </label>
            </div>
            <button type="button" class="mt-3 rounded-xl bg-[#0F172A] px-4 py-2 text-sm text-white" :disabled="saving" @click="submitCreate">
                {{ saving ? 'Saving...' : 'Create Branch' }}
            </button>
        </section>

        <section class="overflow-hidden rounded-2xl border" style="border-color: #E2E8F0; background: #FFFFFF;">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #E2E8F0; background: #F8FAFC;">
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Branch</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Contact</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Usage</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in branchRows" :key="`branch-row-${row.id}`" style="border-bottom: 1px solid #F1F5F9;">
                        <td class="px-4 py-3 align-top">
                            <div class="grid grid-cols-1 gap-2">
                                <input v-model="draftFor(row).code" type="text" class="rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                                <input v-model="draftFor(row).name" type="text" class="rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                                <input v-model="draftFor(row).timezone" type="text" class="rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                                <label class="flex items-center gap-2 text-xs text-[#64748B]">
                                    <input v-model="draftFor(row).is_active" type="checkbox" >
                                    Active
                                </label>
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="grid grid-cols-1 gap-2">
                                <input v-model="draftFor(row).phone" type="text" class="rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                                <input v-model="draftFor(row).address" type="text" class="rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                            </div>
                        </td>
                        <td class="px-4 py-3 align-top text-sm text-[#475569]">
                            <p>Bookings: {{ row.bookings_count }}</p>
                            <p>Slots: {{ row.time_slots_count }}</p>
                            <p>Transactions: {{ row.transactions_count }}</p>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-col gap-2">
                                <button type="button" class="rounded-lg bg-[#1D4ED8] px-3 py-1.5 text-xs text-white" :disabled="saving" @click="submitUpdate(row.id)">
                                    Update
                                </button>
                                <button type="button" class="rounded-lg bg-[#DC2626] px-3 py-1.5 text-xs text-white" :disabled="deletingBranchId === row.id" @click="submitDelete(row.id)">
                                    {{ deletingBranchId === row.id ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!branchRows.length">
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No branch data available.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

