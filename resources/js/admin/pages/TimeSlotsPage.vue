<script setup>
import { computed, reactive, ref } from 'vue';

const props = defineProps({
    timeSlotRows: { type: Array, default: () => [] },
    branchOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingTimeSlotId: { type: [Number, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits([
    'refresh-time-slots',
    'create-time-slot',
    'update-time-slot',
    'delete-time-slot',
    'generate-time-slots',
    'bulk-bookable',
]);

const localError = ref('');
const selectedSlotIds = ref([]);
const draftMap = ref({});
const createForm = reactive({
    branch_id: '',
    slot_date: '',
    start_time: '09:00',
    end_time: '09:30',
    capacity: 1,
    is_bookable: true,
});
const generateForm = reactive({
    branch_id: '',
    start_date: '',
    end_date: '',
    day_start_time: '09:00',
    day_end_time: '21:00',
    interval_minutes: 30,
    capacity: 1,
    is_bookable: true,
});
const todayDate = new Date().toISOString().slice(0, 10);
const listFilter = reactive({
    branch_id: '',
    slot_date: todayDate,
    is_bookable: 'all',
});

const normalizeTime = (value) => {
    const text = String(value || '').trim();

    if (!text) {
        return '';
    }

    return text.length === 5 ? `${text}:00` : text;
};

const slotDraftFor = (row) => {
    const id = Number(row?.id || 0);

    if (!id) {
        return {
            branch_id: '',
            slot_date: '',
            start_time: '',
            end_time: '',
            capacity: 1,
            is_bookable: true,
        };
    }

    if (!draftMap.value[id]) {
        draftMap.value[id] = {
            branch_id: String(row?.branch_id || ''),
            slot_date: String(row?.slot_date || ''),
            start_time: String(row?.start_time_text || '').slice(0, 5),
            end_time: String(row?.end_time_text || '').slice(0, 5),
            capacity: Number(row?.capacity || 1),
            is_bookable: Boolean(row?.is_bookable),
        };
    }

    return draftMap.value[id];
};

const toggleSelected = (slotId) => {
    const id = Number(slotId || 0);
    if (!id) return;

    if (selectedSlotIds.value.includes(id)) {
        selectedSlotIds.value = selectedSlotIds.value.filter((item) => item !== id);
        return;
    }

    selectedSlotIds.value = [...selectedSlotIds.value, id];
};

const submitCreate = () => {
    const branchId = Number(createForm.branch_id || 0);

    if (!branchId || !createForm.slot_date) {
        localError.value = 'Branch and slot date are required.';
        return;
    }

    localError.value = '';
    emit('create-time-slot', {
        branch_id: branchId,
        slot_date: String(createForm.slot_date),
        start_time: normalizeTime(createForm.start_time),
        end_time: normalizeTime(createForm.end_time),
        capacity: Number(createForm.capacity || 1),
        is_bookable: Boolean(createForm.is_bookable),
    });
};

const submitUpdate = (slotId) => {
    const id = Number(slotId || 0);
    const draft = draftMap.value[id];

    if (!id || !draft) {
        return;
    }

    localError.value = '';
    emit('update-time-slot', {
        id,
        payload: {
            branch_id: Number(draft.branch_id || 0),
            slot_date: String(draft.slot_date || ''),
            start_time: normalizeTime(draft.start_time),
            end_time: normalizeTime(draft.end_time),
            capacity: Number(draft.capacity || 1),
            is_bookable: Boolean(draft.is_bookable),
        },
    });
};

const submitDelete = (slotId) => {
    const id = Number(slotId || 0);

    if (!id) return;
    if (!window.confirm('Delete this time slot?')) return;

    emit('delete-time-slot', id);
};

const submitGenerate = () => {
    const branchId = Number(generateForm.branch_id || 0);
    if (!branchId || !generateForm.start_date || !generateForm.end_date) {
        localError.value = 'Branch and date range are required for generate.';
        return;
    }

    localError.value = '';
    emit('generate-time-slots', {
        branch_id: branchId,
        start_date: String(generateForm.start_date),
        end_date: String(generateForm.end_date),
        day_start_time: normalizeTime(generateForm.day_start_time),
        day_end_time: normalizeTime(generateForm.day_end_time),
        interval_minutes: Number(generateForm.interval_minutes || 30),
        capacity: Number(generateForm.capacity || 1),
        is_bookable: Boolean(generateForm.is_bookable),
    });
};

const applyBulk = (isBookable) => {
    const selectableIds = filteredRows.value.map((row) => row.id);
    const selectedIds = selectedSlotIds.value.filter((id) => selectableIds.includes(id));

    if (!selectedIds.length) {
        localError.value = 'Select at least one slot.';
        return;
    }

    localError.value = '';
    emit('bulk-bookable', {
        slot_ids: selectedIds,
        is_bookable: Boolean(isBookable),
    });
    selectedSlotIds.value = [];
};

const filteredRows = computed(() => {
    const branchId = Number(listFilter.branch_id || 0);
    const slotDate = String(listFilter.slot_date || '').trim();
    const status = String(listFilter.is_bookable || 'all');

    return (props.timeSlotRows || [])
        .filter((row) => {
            if (branchId > 0 && Number(row.branch_id || 0) !== branchId) {
                return false;
            }

            if (slotDate && String(row.slot_date || '') !== slotDate) {
                return false;
            }

            if (status === 'bookable' && !Boolean(row.is_bookable)) {
                return false;
            }

            if (status === 'blocked' && Boolean(row.is_bookable)) {
                return false;
            }

            return true;
        })
        .sort((a, b) => {
            const dateCompare = String(a.slot_date || '').localeCompare(String(b.slot_date || ''));

            if (dateCompare !== 0) {
                return dateCompare;
            }

            const branchCompare = String(a.branch_name || '').localeCompare(String(b.branch_name || ''));

            if (branchCompare !== 0) {
                return branchCompare;
            }

            return String(a.start_time || '').localeCompare(String(b.start_time || ''));
        });
});

const resetListFilter = () => {
    listFilter.branch_id = '';
    listFilter.slot_date = '';
    listFilter.is_bookable = 'all';
    selectedSlotIds.value = [];
};

const applyTodayFilter = () => {
    listFilter.slot_date = todayDate;
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #14532D 0%, #15803D 58%, #22C55E 100%); box-shadow: 0 6px 24px rgba(20,83,45,0.24);">
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Time Slots</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.8);">Manage bookable slot ranges and batch generation.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-time-slots')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-2xl border p-4" style="border-color: #DCFCE7; background: #FFFFFF;">
                <h3 class="text-sm font-semibold text-[#166534]">Create Slot</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="text-xs text-[#64748B]">Branch
                        <select v-model="createForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                            <option value="">Select branch</option>
                            <option v-for="branch in branchOptions" :key="`slot-create-branch-${branch.id}`" :value="String(branch.id)">
                                {{ branch.name }}
                            </option>
                        </select>
                    </label>
                    <label class="text-xs text-[#64748B]">Date
                        <input v-model="createForm.slot_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Start Time
                        <input v-model="createForm.start_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">End Time
                        <input v-model="createForm.end_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Capacity
                        <input v-model.number="createForm.capacity" type="number" min="1" max="100" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                        <input v-model="createForm.is_bookable" type="checkbox" >
                        Bookable
                    </label>
                </div>
                <button type="button" class="mt-3 rounded-xl bg-[#15803D] px-4 py-2 text-sm text-white" :disabled="saving" @click="submitCreate">
                    {{ saving ? 'Saving...' : 'Create Slot' }}
                </button>
            </div>

            <div class="rounded-2xl border p-4" style="border-color: #DCFCE7; background: #FFFFFF;">
                <h3 class="text-sm font-semibold text-[#166534]">Generate Slots</h3>
                <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="text-xs text-[#64748B]">Branch
                        <select v-model="generateForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                            <option value="">Select branch</option>
                            <option v-for="branch in branchOptions" :key="`slot-generate-branch-${branch.id}`" :value="String(branch.id)">
                                {{ branch.name }}
                            </option>
                        </select>
                    </label>
                    <label class="text-xs text-[#64748B]">Start Date
                        <input v-model="generateForm.start_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">End Date
                        <input v-model="generateForm.end_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">From
                        <input v-model="generateForm.day_start_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">To
                        <input v-model="generateForm.day_end_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Interval (min)
                        <input v-model.number="generateForm.interval_minutes" type="number" min="5" max="240" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Capacity
                        <input v-model.number="generateForm.capacity" type="number" min="1" max="100" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                        <input v-model="generateForm.is_bookable" type="checkbox" >
                        Bookable
                    </label>
                </div>
                <button type="button" class="mt-3 rounded-xl bg-[#166534] px-4 py-2 text-sm text-white" :disabled="saving" @click="submitGenerate">
                    {{ saving ? 'Generating...' : 'Generate Slots' }}
                </button>
            </div>
        </section>

        <section class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
            <div class="mb-3 grid grid-cols-1 gap-2 md:grid-cols-4">
                <label class="text-xs text-[#64748B]">Filter Branch
                    <select v-model="listFilter.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">All branches</option>
                        <option v-for="branch in branchOptions" :key="`slot-filter-branch-${branch.id}`" :value="String(branch.id)">
                            {{ branch.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Filter Date
                    <input v-model="listFilter.slot_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B]">Filter Status
                    <select v-model="listFilter.is_bookable" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="all">All</option>
                        <option value="bookable">Bookable</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </label>
                <div class="flex items-end gap-2">
                    <button type="button" class="rounded-lg border px-3 py-2 text-xs" style="border-color: #BFDBFE; color: #1D4ED8;" @click="applyTodayFilter">Today</button>
                    <button type="button" class="rounded-lg border px-3 py-2 text-xs" style="border-color: #CBD5E1; color: #64748B;" @click="resetListFilter">Clear</button>
                </div>
            </div>

            <div class="mb-3 flex flex-wrap items-center gap-2">
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" style="border-color: #86EFAC; color: #166534;" :disabled="saving" @click="applyBulk(true)">Set Selected Bookable</button>
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" style="border-color: #FCA5A5; color: #B91C1C;" :disabled="saving" @click="applyBulk(false)">Set Selected Blocked</button>
                <span class="text-xs text-[#94A3B8]">Showing {{ filteredRows.length }} of {{ timeSlotRows.length }} slots</span>
            </div>

            <div class="overflow-hidden rounded-xl border" style="border-color: #E2E8F0;">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #E2E8F0; background: #F8FAFC;">
                            <th class="px-3 py-2 text-left text-xs uppercase text-[#94A3B8]"></th>
                            <th class="px-3 py-2 text-left text-xs uppercase text-[#94A3B8]">Branch & Date</th>
                            <th class="px-3 py-2 text-left text-xs uppercase text-[#94A3B8]">Time</th>
                            <th class="px-3 py-2 text-left text-xs uppercase text-[#94A3B8]">Capacity</th>
                            <th class="px-3 py-2 text-left text-xs uppercase text-[#94A3B8]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in filteredRows" :key="`time-slot-row-${row.id}`" style="border-bottom: 1px solid #F1F5F9;">
                            <td class="px-3 py-2 align-top">
                                <input type="checkbox" :checked="selectedSlotIds.includes(row.id)" @change="toggleSelected(row.id)" >
                            </td>
                            <td class="px-3 py-2 align-top text-sm text-[#334155]">
                                <p class="font-semibold">{{ row.branch_name }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ row.slot_date_text }}</p>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <div class="grid grid-cols-2 gap-2">
                                    <input v-model="slotDraftFor(row).start_time" type="time" class="rounded border px-2 py-1 text-xs" style="border-color: #CBD5E1;" >
                                    <input v-model="slotDraftFor(row).end_time" type="time" class="rounded border px-2 py-1 text-xs" style="border-color: #CBD5E1;" >
                                </div>
                                <label class="mt-2 flex items-center gap-2 text-xs text-[#64748B]">
                                    <input v-model="slotDraftFor(row).is_bookable" type="checkbox" >
                                    Bookable
                                </label>
                            </td>
                            <td class="px-3 py-2 align-top">
                                <input v-model.number="slotDraftFor(row).capacity" type="number" min="1" max="100" class="w-20 rounded border px-2 py-1 text-xs" style="border-color: #CBD5E1;" >
                            </td>
                            <td class="px-3 py-2 align-top">
                                <div class="flex flex-col gap-2">
                                    <button type="button" class="rounded bg-[#15803D] px-2 py-1 text-xs text-white" :disabled="saving" @click="submitUpdate(row.id)">Update</button>
                                    <button type="button" class="rounded bg-[#DC2626] px-2 py-1 text-xs text-white" :disabled="deletingTimeSlotId === row.id" @click="submitDelete(row.id)">
                                        {{ deletingTimeSlotId === row.id ? 'Deleting...' : 'Delete' }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="!filteredRows.length">
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No time slots match the current filter.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
