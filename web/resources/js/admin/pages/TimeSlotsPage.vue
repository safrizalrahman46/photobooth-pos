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
const todayDate = new Date().toISOString().slice(0, 10);

const createForm = reactive({
    branch_id: '',
    slot_date: todayDate,
    start_time: '09:00',
    end_time: '09:30',
    capacity: 1,
    is_bookable: true,
});

const generateForm = reactive({
    branch_id: '',
    start_date: todayDate,
    end_date: todayDate,
    day_start_time: '09:00',
    day_end_time: '21:00',
    interval_minutes: 30,
    capacity: 1,
    is_bookable: true,
});

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

const formatDuration = (value) => {
    const minutes = Number(value || 0);

    if (minutes <= 0) {
        return '-';
    }

    return `${minutes} menit`;
};

const statusTone = (row) => {
    if (!row.is_bookable) {
        return {
            bg: '#FEF2F2',
            border: '#FECACA',
            text: '#B91C1C',
            label: 'Ditutup',
        };
    }

    if (row.is_full) {
        return {
            bg: '#FFF7ED',
            border: '#FED7AA',
            text: '#C2410C',
            label: 'Penuh',
        };
    }

    return {
        bg: '#F0FDF4',
        border: '#BBF7D0',
        text: '#166534',
        label: 'Bisa Dibooking',
    };
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
    if (!id) {
        return;
    }

    if (selectedSlotIds.value.includes(id)) {
        selectedSlotIds.value = selectedSlotIds.value.filter((item) => item !== id);
        return;
    }

    selectedSlotIds.value = [...selectedSlotIds.value, id];
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

const filteredSlotIds = computed(() => {
    return filteredRows.value
        .map((row) => Number(row.id || 0))
        .filter((id) => id > 0);
});

const allFilteredSelected = computed(() => {
    if (!filteredSlotIds.value.length) {
        return false;
    }

    return filteredSlotIds.value.every((id) => selectedSlotIds.value.includes(id));
});

const slotSummaryCards = computed(() => {
    const rows = filteredRows.value;
    const totalSlots = rows.length;
    const bookableSlots = rows.filter((row) => row.is_bookable).length;
    const fullSlots = rows.filter((row) => row.is_full).length;
    const remainingCapacity = rows.reduce((sum, row) => sum + Number(row.remaining_parallel_capacity || 0), 0);

    return [
        { label: 'Slot Tampil', value: totalSlots, helper: 'Sesuai filter aktif', tone: '#2563EB' },
        { label: 'Bisa Dibooking', value: bookableSlots, helper: 'Slot terbuka untuk booking baru', tone: '#16A34A' },
        { label: 'Penuh', value: fullSlots, helper: 'Booking paralel tersisa 0', tone: '#EA580C' },
        { label: 'Sisa Paralel', value: remainingCapacity, helper: 'Akumulasi kapasitas tersisa', tone: '#7C3AED' },
    ];
});

const submitCreate = () => {
    const branchId = Number(createForm.branch_id || 0);

    if (!branchId || !createForm.slot_date) {
        localError.value = 'Branch dan tanggal slot wajib diisi.';
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

    if (!id) {
        return;
    }

    if (!window.confirm('Hapus slot ini? Gunakan nonaktifkan jika hanya ingin menutup booking.')) {
        return;
    }

    emit('delete-time-slot', id);
};

const submitGenerate = () => {
    const branchId = Number(generateForm.branch_id || 0);

    if (!branchId || !generateForm.start_date || !generateForm.end_date) {
        localError.value = 'Branch dan rentang tanggal wajib diisi untuk generate slot.';
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
        localError.value = 'Pilih minimal satu slot dari hasil filter.';
        return;
    }

    localError.value = '';
    emit('bulk-bookable', {
        slot_ids: selectedIds,
        is_bookable: Boolean(isBookable),
    });
    selectedSlotIds.value = [];
};

const selectAllFiltered = () => {
    if (!filteredSlotIds.value.length) {
        return;
    }

    selectedSlotIds.value = Array.from(new Set([
        ...selectedSlotIds.value,
        ...filteredSlotIds.value,
    ]));
};

const clearFilteredSelection = () => {
    if (!filteredSlotIds.value.length) {
        return;
    }

    selectedSlotIds.value = selectedSlotIds.value.filter((id) => !filteredSlotIds.value.includes(id));
};

const resetListFilter = () => {
    listFilter.branch_id = '';
    listFilter.slot_date = todayDate;
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
                    <p class="text-sm" style="color: rgba(255,255,255,0.82);">Atur rentang jam slot dan berapa booking yang boleh berjalan bersamaan pada slot tersebut.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-time-slots')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <section class="rounded-2xl border p-4" style="border-color: #DBEAFE; background: #F8FBFF;">
            <div class="grid gap-3 md:grid-cols-3">
                <div class="rounded-xl border px-4 py-3" style="border-color: #BFDBFE; background: #FFFFFF;">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#2563EB]">Slot</p>
                    <p class="mt-1 text-sm text-[#334155]">Slot menentukan rentang jam yang bisa dipilih customer, misalnya `20:00 - 20:30`.</p>
                </div>
                <div class="rounded-xl border px-4 py-3" style="border-color: #BBF7D0; background: #FFFFFF;">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#16A34A]">Booking Paralel</p>
                    <p class="mt-1 text-sm text-[#334155]">`Maks. Booking Paralel` berarti berapa booking yang boleh overlap pada slot yang sama, bukan jumlah orang.</p>
                </div>
                <div class="rounded-xl border px-4 py-3" style="border-color: #FDE68A; background: #FFFFFF;">
                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#D97706]">Durasi Package</p>
                    <p class="mt-1 text-sm text-[#334155]">Ketersediaan akhir tetap dipengaruhi durasi package. Slot pendek bisa valid untuk package singkat, tapi gagal untuk package lebih lama.</p>
                </div>
            </div>
        </section>

        <p v-if="errorMessage || localError" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ localError || errorMessage }}
        </p>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-2">
            <div class="rounded-2xl border p-4" style="border-color: #DCFCE7; background: #FFFFFF;">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-[#166534]">Buat Slot Manual</h3>
                    <p class="mt-1 text-xs text-[#64748B]">Gunakan untuk tanggal atau rentang jam khusus yang tidak ikut pola harian normal.</p>
                </div>

                <div class="rtp-admin-form-grid">
                    <label class="text-xs text-[#64748B]">Branch
                        <select v-model="createForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                            <option value="">Select branch</option>
                            <option v-for="branch in branchOptions" :key="`slot-create-branch-${branch.id}`" :value="String(branch.id)">
                                {{ branch.name }}
                            </option>
                        </select>
                    </label>
                    <label class="text-xs text-[#64748B]">Tanggal Slot
                        <input v-model="createForm.slot_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Jam Mulai
                        <input v-model="createForm.start_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Jam Selesai
                        <input v-model="createForm.end_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Maks. Booking Paralel
                        <input v-model.number="createForm.capacity" type="number" min="1" max="100" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                        <input v-model="createForm.is_bookable" type="checkbox" >
                        Bisa Dibooking
                    </label>
                </div>

                <button type="button" class="mt-3 w-full rounded-xl bg-[#15803D] px-4 py-2 text-sm text-white sm:w-auto" :disabled="saving" @click="submitCreate">
                    {{ saving ? 'Menyimpan...' : 'Buat Slot' }}
                </button>
            </div>

            <div class="rounded-2xl border p-4" style="border-color: #DCFCE7; background: #FFFFFF;">
                <div class="mb-3">
                    <h3 class="text-sm font-semibold text-[#166534]">Generate Slot Otomatis</h3>
                    <p class="mt-1 text-xs text-[#64748B]">Gunakan untuk membentuk jadwal satu hari penuh atau satu periode sekaligus dengan pola yang konsisten.</p>
                </div>

                <div class="rtp-admin-form-grid">
                    <label class="text-xs text-[#64748B]">Branch
                        <select v-model="generateForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                            <option value="">Select branch</option>
                            <option v-for="branch in branchOptions" :key="`slot-generate-branch-${branch.id}`" :value="String(branch.id)">
                                {{ branch.name }}
                            </option>
                        </select>
                    </label>
                    <label class="text-xs text-[#64748B]">Tanggal Mulai
                        <input v-model="generateForm.start_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Tanggal Akhir
                        <input v-model="generateForm.end_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Jam Operasional Mulai
                        <input v-model="generateForm.day_start_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Jam Operasional Selesai
                        <input v-model="generateForm.day_end_time" type="time" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Interval Slot (menit)
                        <input v-model.number="generateForm.interval_minutes" type="number" min="5" max="240" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="text-xs text-[#64748B]">Maks. Booking Paralel
                        <input v-model.number="generateForm.capacity" type="number" min="1" max="100" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                    </label>
                    <label class="flex items-center gap-2 self-end text-sm text-[#334155]">
                        <input v-model="generateForm.is_bookable" type="checkbox" >
                        Langsung Bisa Dibooking
                    </label>
                </div>

                <button type="button" class="mt-3 w-full rounded-xl bg-[#166534] px-4 py-2 text-sm text-white sm:w-auto" :disabled="saving" @click="submitGenerate">
                    {{ saving ? 'Generating...' : 'Generate Slots' }}
                </button>
            </div>
        </section>

        <section class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
            <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h3 class="text-sm font-semibold text-[#0F172A]">Daftar Slot</h3>
                    <p class="mt-1 text-xs text-[#64748B]">Lihat sisa booking paralel, package yang muat, lalu buka/tutup booking per slot.</p>
                </div>
                <div class="flex gap-2">
                    <button type="button" class="rounded-lg border px-3 py-2 text-xs" style="border-color: #BFDBFE; color: #1D4ED8;" @click="applyTodayFilter">Hari Ini</button>
                    <button type="button" class="rounded-lg border px-3 py-2 text-xs" style="border-color: #CBD5E1; color: #64748B;" @click="resetListFilter">Reset Filter</button>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-2 md:grid-cols-4">
                <label class="text-xs text-[#64748B]">Filter Branch
                    <select v-model="listFilter.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="">All branches</option>
                        <option v-for="branch in branchOptions" :key="`slot-filter-branch-${branch.id}`" :value="String(branch.id)">
                            {{ branch.name }}
                        </option>
                    </select>
                </label>
                <label class="text-xs text-[#64748B]">Filter Tanggal
                    <input v-model="listFilter.slot_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;" >
                </label>
                <label class="text-xs text-[#64748B]">Filter Status
                    <select v-model="listFilter.is_bookable" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #CBD5E1;">
                        <option value="all">Semua</option>
                        <option value="bookable">Bisa Dibooking</option>
                        <option value="blocked">Ditutup</option>
                    </select>
                </label>
                <div class="rounded-xl border px-4 py-3" style="border-color: #E2E8F0; background: #F8FAFC;">
                    <p class="text-xs text-[#94A3B8]">Pilihan Slot</p>
                    <p class="mt-1 text-sm font-semibold text-[#0F172A]">{{ selectedSlotIds.length }} dipilih</p>
                </div>
            </div>

            <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="(card, index) in slotSummaryCards" :key="`slot-summary-card-${index}-${card.label}`" class="rounded-2xl border px-4 py-3" style="border-color: #E2E8F0; background: #FFFFFF;">
                    <p class="text-xs text-[#94A3B8]">{{ card.label }}</p>
                    <p class="mt-1 text-xl font-bold text-[#0F172A]">{{ card.value }}</p>
                    <p class="mt-1 text-xs" :style="{ color: card.tone }">{{ card.helper }}</p>
                </div>
            </div>

            <div class="mb-4 flex flex-wrap items-center gap-2">
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" style="border-color: #BFDBFE; color: #1D4ED8;" :disabled="!filteredRows.length || allFilteredSelected" @click="selectAllFiltered">
                    Pilih Semua Hasil Filter
                </button>
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" style="border-color: #CBD5E1; color: #64748B;" :disabled="!selectedSlotIds.length" @click="clearFilteredSelection">
                    Bersihkan Pilihan
                </button>
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" style="border-color: #86EFAC; color: #166534;" :disabled="saving" @click="applyBulk(true)">
                    Buka Booking Slot Terpilih
                </button>
                <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" style="border-color: #FCA5A5; color: #B91C1C;" :disabled="saving" @click="applyBulk(false)">
                    Tutup Booking Slot Terpilih
                </button>
            </div>

            <div v-if="filteredRows.length" class="space-y-3">
                <article v-for="row in filteredRows" :key="`time-slot-row-${row.id}`" class="rounded-2xl border p-4" :style="{ borderColor: statusTone(row).border, background: '#FFFFFF' }">
                    <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                        <div class="flex items-start gap-3">
                            <input type="checkbox" class="mt-1" :checked="selectedSlotIds.includes(row.id)" @change="toggleSelected(row.id)" >

                            <div class="space-y-3">
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-base font-semibold text-[#0F172A]">{{ row.branch_name }}</p>
                                        <span class="rounded-full px-2.5 py-1 text-[0.68rem] font-semibold" :style="{ background: statusTone(row).bg, color: statusTone(row).text }">
                                            {{ statusTone(row).label }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-sm text-[#64748B]">{{ row.slot_date_text }} · {{ row.start_time_text }} - {{ row.end_time_text }}</p>
                                </div>

                                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                    <div class="rounded-xl px-3 py-2" style="background: #F8FAFC;">
                                        <p class="text-[0.68rem] uppercase tracking-[0.08em] text-[#94A3B8]">Durasi Slot</p>
                                        <p class="mt-1 text-sm font-semibold text-[#0F172A]">{{ formatDuration(row.slot_duration_minutes) }}</p>
                                    </div>
                                    <div class="rounded-xl px-3 py-2" style="background: #F8FAFC;">
                                        <p class="text-[0.68rem] uppercase tracking-[0.08em] text-[#94A3B8]">Booking Paralel</p>
                                        <p class="mt-1 text-sm font-semibold text-[#0F172A]">{{ row.capacity }} max</p>
                                    </div>
                                    <div class="rounded-xl px-3 py-2" style="background: #F8FAFC;">
                                        <p class="text-[0.68rem] uppercase tracking-[0.08em] text-[#94A3B8]">Booking Aktif</p>
                                        <p class="mt-1 text-sm font-semibold text-[#0F172A]">{{ row.active_bookings_count }} aktif · sisa {{ row.remaining_parallel_capacity }}</p>
                                    </div>
                                    <div class="rounded-xl px-3 py-2" style="background: #F8FAFC;">
                                        <p class="text-[0.68rem] uppercase tracking-[0.08em] text-[#94A3B8]">Dampak Package</p>
                                        <p class="mt-1 text-sm font-semibold text-[#0F172A]">{{ row.compatible_packages_count }} package cocok</p>
                                        <p class="mt-1 text-xs text-[#64748B]">Muat hingga {{ row.longest_supported_duration_minutes || 0 }} menit</p>
                                    </div>
                                </div>

                                <div class="rounded-xl border px-3 py-3" style="border-color: #E2E8F0; background: #FCFDFE;">
                                    <p class="text-xs font-semibold uppercase tracking-[0.08em] text-[#94A3B8]">Package Yang Muat</p>
                                    <div v-if="row.compatible_package_names.length" class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="packageName in row.compatible_package_names" :key="`slot-package-${row.id}-${packageName}`" class="rounded-full bg-[#EFF6FF] px-2.5 py-1 text-[0.68rem] font-medium text-[#2563EB]">
                                            {{ packageName }}
                                        </span>
                                        <span v-if="row.compatible_packages_count > row.compatible_package_names.length" class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-medium text-[#64748B]">
                                            +{{ row.compatible_packages_count - row.compatible_package_names.length }} lainnya
                                        </span>
                                    </div>
                                    <p v-else class="mt-2 text-xs text-[#B91C1C]">Tidak ada package aktif yang muat pada rentang slot ini.</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 xl:w-[320px] xl:grid-cols-1">
                            <div class="grid grid-cols-2 gap-2">
                                <label class="text-xs text-[#64748B]">Jam Mulai
                                    <input v-model="slotDraftFor(row).start_time" type="time" class="mt-1 w-full rounded border px-2 py-2 text-xs" style="border-color: #CBD5E1;" >
                                </label>
                                <label class="text-xs text-[#64748B]">Jam Selesai
                                    <input v-model="slotDraftFor(row).end_time" type="time" class="mt-1 w-full rounded border px-2 py-2 text-xs" style="border-color: #CBD5E1;" >
                                </label>
                            </div>

                            <label class="text-xs text-[#64748B]">Maks. Booking Paralel
                                <input v-model.number="slotDraftFor(row).capacity" type="number" min="1" max="100" class="mt-1 w-full rounded border px-2 py-2 text-xs" style="border-color: #CBD5E1;" >
                            </label>

                            <label class="flex items-center gap-2 text-xs text-[#475569]">
                                <input v-model="slotDraftFor(row).is_bookable" type="checkbox" >
                                Bisa Dibooking
                            </label>

                            <div class="flex gap-2">
                                <button type="button" class="flex-1 rounded bg-[#15803D] px-3 py-2 text-xs text-white" :disabled="saving" @click="submitUpdate(row.id)">Simpan Perubahan</button>
                                <button type="button" class="rounded bg-[#DC2626] px-3 py-2 text-xs text-white" :disabled="deletingTimeSlotId === row.id" @click="submitDelete(row.id)">
                                    {{ deletingTimeSlotId === row.id ? 'Deleting...' : 'Hapus' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div v-else class="rounded-2xl border border-dashed px-4 py-10 text-center text-sm text-[#94A3B8]" style="border-color: #CBD5E1; background: #F8FAFC;">
                Tidak ada time slot yang cocok dengan filter saat ini.
            </div>
        </section>
    </div>
</template>
