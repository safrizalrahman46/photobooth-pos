<script setup>
import { reactive, ref } from 'vue';

const props = defineProps({
    printerSettingRows: { type: Array, default: () => [] },
    branchOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingPrinterSettingId: { type: [Number, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits([
    'refresh-printer-settings',
    'create-printer-setting',
    'update-printer-setting',
    'delete-printer-setting',
    'set-default-printer-setting',
]);

const localError = ref('');
const draftMap = ref({});
const createForm = reactive({
    branch_id: '',
    device_name: '',
    printer_type: 'thermal',
    paper_width_mm: 80,
    is_default: false,
    is_active: true,
});

const draftFor = (row) => {
    const id = Number(row?.id || 0);

    if (!id) {
        return {
            branch_id: '',
            device_name: '',
            printer_type: 'thermal',
            paper_width_mm: 80,
            is_default: false,
            is_active: true,
        };
    }

    if (!draftMap.value[id]) {
        draftMap.value[id] = {
            branch_id: String(row?.branch_id || ''),
            device_name: String(row?.device_name || ''),
            printer_type: String(row?.printer_type || 'thermal'),
            paper_width_mm: Number(row?.paper_width_mm || 80),
            is_default: Boolean(row?.is_default),
            is_active: Boolean(row?.is_active),
        };
    }

    return draftMap.value[id];
};

const submitCreate = () => {
    const branchId = Number(createForm.branch_id || 0);

    if (!branchId || !String(createForm.device_name || '').trim()) {
        localError.value = 'Branch and device name are required.';
        return;
    }

    localError.value = '';
    emit('create-printer-setting', {
        branch_id: branchId,
        device_name: String(createForm.device_name || '').trim(),
        printer_type: String(createForm.printer_type || 'thermal'),
        paper_width_mm: Number(createForm.paper_width_mm || 80),
        is_default: Boolean(createForm.is_default),
        is_active: Boolean(createForm.is_active),
        connection: {},
    });
};

const submitUpdate = (id) => {
    const settingId = Number(id || 0);
    const draft = draftMap.value[settingId];

    if (!settingId || !draft) {
        return;
    }

    emit('update-printer-setting', {
        id: settingId,
        payload: {
            branch_id: Number(draft.branch_id || 0),
            device_name: String(draft.device_name || '').trim(),
            printer_type: String(draft.printer_type || 'thermal'),
            paper_width_mm: Number(draft.paper_width_mm || 80),
            is_default: Boolean(draft.is_default),
            is_active: Boolean(draft.is_active),
            connection: {},
        },
    });
};

const submitDelete = (id) => {
    const settingId = Number(id || 0);
    if (!settingId) return;
    if (!window.confirm('Delete this printer setting?')) return;

    emit('delete-printer-setting', settingId);
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #1F2937 0%, #374151 58%, #4B5563 100%); box-shadow: 0 6px 24px rgba(31,41,55,0.24);">
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Printer Settings</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.82);">Configure printer devices, active status, and default assignment.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-printer-settings')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <section class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
            <h3 class="text-sm font-semibold text-[#1F2937]">Add Printer</h3>
            <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                <label class="text-xs text-[#64748B]">Branch
                    <select v-model="createForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">Select branch</option>
                        <option v-for="branch in branchOptions" :key="`printer-create-branch-${branch.id}`" :value="String(branch.id)">
                            {{ branch.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Device Name
                    <input v-model="createForm.device_name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B]">Type
                    <select v-model="createForm.printer_type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="thermal">Thermal</option>
                        <option value="inkjet">Inkjet</option>
                        <option value="laser">Laser</option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Paper Width (mm)
                    <input v-model.number="createForm.paper_width_mm" type="number" min="58" max="120" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                    <input v-model="createForm.is_default" type="checkbox" >
                    Default
                </label>
                <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                    <input v-model="createForm.is_active" type="checkbox" >
                    Active
                </label>
            </div>
            <button type="button" class="mt-3 rounded-xl bg-[#111827] px-4 py-2 text-sm text-white" :disabled="saving" @click="submitCreate">
                {{ saving ? 'Saving...' : 'Add Printer' }}
            </button>
        </section>

        <section class="overflow-hidden rounded-2xl border" style="border-color: #E2E8F0; background: #FFFFFF;">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #E2E8F0; background: #F8FAFC;">
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Device</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Type</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Status</th>
                        <th class="px-4 py-3 text-left text-xs uppercase text-[#94A3B8]">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="row in printerSettingRows" :key="`printer-row-${row.id}`" style="border-bottom: 1px solid #F1F5F9;">
                        <td class="px-4 py-3 align-top">
                            <p class="text-sm font-semibold text-[#1F2937]">{{ row.branch_name }}</p>
                            <input v-model="draftFor(row).device_name" type="text" class="mt-1 w-full rounded border px-2 py-1 text-sm" style="border-color: #CBD5E1;" >
                        </td>
                        <td class="px-4 py-3 align-top">
                            <select v-model="draftFor(row).printer_type" class="rounded border px-2 py-1 text-sm" style="border-color: #CBD5E1;">
                                <option value="thermal">Thermal</option>
                                <option value="inkjet">Inkjet</option>
                                <option value="laser">Laser</option>
                            </select>
                            <input v-model.number="draftFor(row).paper_width_mm" type="number" min="58" max="120" class="mt-2 w-24 rounded border px-2 py-1 text-sm" style="border-color: #CBD5E1;" >
                        </td>
                        <td class="px-4 py-3 align-top">
                            <label class="flex items-center gap-2 text-xs text-[#64748B]">
                                <input v-model="draftFor(row).is_default" type="checkbox" >
                                Default
                            </label>
                            <label class="mt-2 flex items-center gap-2 text-xs text-[#64748B]">
                                <input v-model="draftFor(row).is_active" type="checkbox" >
                                Active
                            </label>
                        </td>
                        <td class="px-4 py-3 align-top">
                            <div class="flex flex-col gap-2">
                                <button type="button" class="rounded bg-[#1D4ED8] px-2 py-1 text-xs text-white" :disabled="saving" @click="submitUpdate(row.id)">Update</button>
                                <button type="button" class="rounded bg-[#0F766E] px-2 py-1 text-xs text-white" :disabled="saving" @click="emit('set-default-printer-setting', row.id)">Set Default</button>
                                <button type="button" class="rounded bg-[#DC2626] px-2 py-1 text-xs text-white" :disabled="deletingPrinterSettingId === row.id" @click="submitDelete(row.id)">
                                    {{ deletingPrinterSettingId === row.id ? 'Deleting...' : 'Delete' }}
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="!printerSettingRows.length">
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No printer settings found.</td>
                    </tr>
                </tbody>
            </table>
        </section>
    </div>
</template>

