<script setup>
import { computed, reactive, ref } from 'vue';
import {
    BellRing,
    CheckCircle2,
    Clock3,
    Plus,
    RefreshCw,
    RotateCcw,
    SkipForward,
    Trash2,
    UserPlus,
} from 'lucide-vue-next';
import AdminModal from '../components/AdminModal.vue';

const props = defineProps({
    queueStats: { type: Object, default: () => ({}) },
    currentQueue: { type: Object, default: null },
    waitingQueue: { type: Array, default: () => [] },
    queueProgressStyle: { type: Object, default: () => ({ width: '0%' }) },
    queueRemainingText: { type: String, default: '00:00' },
    queueSessionDurationText: { type: String, default: '00:00' },
    resolveQueueStatus: { type: Function, required: true },
    queueLoading: { type: Boolean, default: false },
    queueActionLoading: { type: Boolean, default: false },
    queueProcessingTicketId: { type: Number, default: null },
    queueError: { type: String, default: '' },
    branchOptions: { type: Array, default: () => [] },
    bookingOptions: { type: Array, default: () => [] },
    defaultBranchId: { type: Number, default: null },
    viewBranchId: { type: [Number, String], default: null },
});

const emit = defineEmits(['refresh-queue', 'set-view-branch', 'call-next', 'transition-ticket', 'add-booking', 'add-walk-in']);

const addModalOpen = ref(false);
const localError = ref('');
const queueSourceType = ref('walk_in');

const bookingForm = reactive({
    booking_id: null,
});

const queueStatusMeta = {
    waiting: {
        label: 'Menunggu',
        bg: '#EFF6FF',
        color: '#1D4ED8',
        nextAction: 'Panggil',
    },
    called: {
        label: 'Dipanggil',
        bg: '#FEF3C7',
        color: '#B45309',
        nextAction: 'Tandai Hadir',
    },
    checked_in: {
        label: 'Hadir',
        bg: '#DCFCE7',
        color: '#047857',
        nextAction: 'Mulai Sesi',
    },
    in_session: {
        label: 'Sesi Berjalan',
        bg: '#EDE9FE',
        color: '#6D28D9',
        nextAction: 'Selesaikan Sesi',
    },
    skipped: {
        label: 'Dilewati',
        bg: '#FEE2E2',
        color: '#B91C1C',
        nextAction: 'Panggil Ulang',
    },
    finished: {
        label: 'Selesai',
        bg: '#D1FAE5',
        color: '#047857',
        nextAction: '',
    },
    cancelled: {
        label: 'Dibatalkan',
        bg: '#F1F5F9',
        color: '#64748B',
        nextAction: '',
    },
};

const walkInForm = reactive({
    branch_id: null,
    queue_date: '',
    customer_name: '',
    customer_phone: '',
});

const todayIso = () => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const selectedViewBranchId = computed(() => {
    const id = Number(props.viewBranchId || 0);

    return id > 0 ? id : null;
});

const activeBranchId = computed(() => {
    const fromForm = Number(walkInForm.branch_id || 0);
    const fromView = Number(selectedViewBranchId.value || 0);
    const fromDefault = Number(props.defaultBranchId || 0);
    const fromOptions = Number(props.branchOptions?.[0]?.id || 0);

    return fromForm || fromView || fromDefault || fromOptions || null;
});

const branchNameMap = computed(() => {
    const map = new Map();

    (props.branchOptions || []).forEach((branch) => {
        const id = Number(branch?.id || 0);
        const name = String(branch?.name || '').trim();

        if (id > 0 && name) {
            map.set(id, name);
        }
    });

    return map;
});

const resolveBranchName = (branchId) => {
    const id = Number(branchId || 0);

    if (!id) {
        return '-';
    }

    return branchNameMap.value.get(id) || `Cabang #${id}`;
};

const activeBranchName = computed(() => {
    return resolveBranchName(activeBranchId.value);
});

const onViewBranchChange = (event) => {
    const rawValue = String(event?.target?.value || '').trim();
    const parsed = Number(rawValue || 0);
    const normalized = parsed > 0 ? parsed : null;

    emit('set-view-branch', normalized);
};

const refreshQueue = () => {
    emit('refresh-queue', {
        branch_id: selectedViewBranchId.value,
    });
};

const hasBookingOptions = computed(() => {
    return Array.isArray(props.bookingOptions) && props.bookingOptions.length > 0;
});

const currentTicketId = computed(() => Number(props.currentQueue?.ticket_id || 0));

const normalizedQueueItems = computed(() => {
    if (!Array.isArray(props.waitingQueue)) {
        return [];
    }

    return props.waitingQueue.map((ticket, index) => ({
        ...ticket,
        ticket_id: Number(ticket?.ticket_id || 0),
        queue_number: Number(ticket?.queue_number || index + 1),
        status: String(ticket?.status || 'waiting').toLowerCase(),
        package_name: String(ticket?.package_name || '-'),
    }));
});

const waitingTickets = computed(() => normalizedQueueItems.value.filter((ticket) => {
    if (ticket.ticket_id && ticket.ticket_id === currentTicketId.value) {
        return false;
    }

    return ['waiting', 'skipped'].includes(ticket.status);
}));

const processingTickets = computed(() => normalizedQueueItems.value.filter((ticket) => {
    if (ticket.ticket_id && ticket.ticket_id === currentTicketId.value) {
        return false;
    }

    return ['called', 'checked_in', 'in_session'].includes(ticket.status);
}));

const hasCallableTicket = computed(() => waitingTickets.value.some((ticket) => ticket.status === 'waiting'));

const selectedBooking = computed(() => {
    const bookingId = Number(bookingForm.booking_id || 0);

    if (!bookingId || !hasBookingOptions.value) {
        return null;
    }

    return props.bookingOptions.find((item) => Number(item?.id || 0) === bookingId) || null;
});

const resetWalkInForm = () => {
    walkInForm.branch_id = activeBranchId.value;
    walkInForm.queue_date = todayIso();
    walkInForm.customer_name = '';
    walkInForm.customer_phone = '';
};

const resetBookingForm = () => {
    bookingForm.booking_id = null;
};

const resetQueueForm = () => {
    queueSourceType.value = hasBookingOptions.value ? 'booking' : 'walk_in';
    resetBookingForm();
    resetWalkInForm();
    localError.value = '';
};

const openAddModal = () => {
    resetQueueForm();
    addModalOpen.value = true;
};

const closeAddModal = () => {
    addModalOpen.value = false;
    localError.value = '';
};

const setQueueSourceType = (type) => {
    if (type === 'booking' && !hasBookingOptions.value) {
        return;
    }

    queueSourceType.value = type;
    localError.value = '';
};

const canProcessTicket = (ticketId) => {
    if (!props.queueActionLoading) {
        return false;
    }

    return Number(ticketId || 0) === Number(props.queueProcessingTicketId || 0);
};

const queueStatusLabel = (status) => {
    const normalized = String(status || '').trim().toLowerCase();

    if (!normalized) {
        return 'Status Berikutnya';
    }

    if (queueStatusMeta[normalized]?.label) {
        return queueStatusMeta[normalized].label;
    }

    const resolved = props.resolveQueueStatus?.(normalized);
    const mappedLabel = String(resolved?.label || '').trim();

    if (mappedLabel) {
        return mappedLabel;
    }

    return normalized
        .split('_')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};

const queueStatusStyle = (status) => {
    const normalized = String(status || '').toLowerCase();
    const meta = queueStatusMeta[normalized];

    if (meta) {
        return {
            background: meta.bg,
            color: meta.color,
        };
    }

    const resolved = props.resolveQueueStatus?.(normalized);

    return {
        background: resolved?.bg || '#F8FAFC',
        color: resolved?.color || '#64748B',
    };
};

const queuePackageLabel = (ticket) => {
    const packageName = String(ticket?.package_name || '').trim();

    if (packageName && packageName !== '-') {
        return packageName;
    }

    return String(ticket?.source_type || '').toLowerCase() === 'walk_in' ? 'Walk-in' : '-';
};

const nextStatusForTicket = (ticket) => {
    return String(ticket?.next_status || fallbackNextStatus(ticket?.status || '') || '');
};

const nextActionLabel = (ticket) => {
    const status = String(ticket?.status || '').toLowerCase();
    const nextStatus = nextStatusForTicket(ticket);

    if (!nextStatus) {
        return 'Tidak Ada Aksi';
    }

    if (status === 'skipped' && nextStatus === 'called') {
        return 'Panggil Ulang';
    }

    return queueStatusMeta[status]?.nextAction || `Lanjut ke ${queueStatusLabel(nextStatus)}`;
};

const currentPrimaryAction = computed(() => {
    const ticket = props.currentQueue;

    if (!ticket?.ticket_id) {
        return null;
    }

    const status = String(ticket.status || '').toLowerCase();
    const nextStatus = fallbackNextStatus(status);

    if (!nextStatus) {
        return null;
    }

    return {
        status: nextStatus,
        label: queueStatusMeta[status]?.nextAction || `Lanjut ke ${queueStatusLabel(nextStatus)}`,
    };
});

const canSkipCurrent = computed(() => {
    const status = String(props.currentQueue?.status || '').toLowerCase();

    return ['called', 'checked_in'].includes(status);
});

const fallbackNextStatus = (status) => {
    const current = String(status || '').toLowerCase();

    if (current === 'waiting') {
        return 'called';
    }

    if (current === 'called') {
        return 'checked_in';
    }

    if (current === 'checked_in') {
        return 'in_session';
    }

    if (current === 'in_session') {
        return 'finished';
    }

    return '';
};

const queueCallNext = async () => {
    if (!activeBranchId.value) {
        localError.value = 'Pilih branch terlebih dahulu sebelum pemanggilan antrean.';
        return;
    }

    localError.value = '';
    await emit('call-next', {
        branch_id: activeBranchId.value,
        queue_date: todayIso(),
    });
};

const runCurrentPrimaryAction = async () => {
    if (!props.currentQueue?.ticket_id || !currentPrimaryAction.value?.status) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: props.currentQueue.ticket_id,
        status: currentPrimaryAction.value.status,
    });
};

const skipCurrent = async () => {
    if (!props.currentQueue?.ticket_id) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: props.currentQueue.ticket_id,
        status: 'skipped',
    });
};

const promoteTicket = async (ticket) => {
    const nextStatus = nextStatusForTicket(ticket);

    if (!ticket?.ticket_id || !nextStatus) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: ticket.ticket_id,
        status: nextStatus,
    });
};

const cancelTicket = async (ticket) => {
    if (!ticket?.ticket_id) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: ticket.ticket_id,
        status: 'cancelled',
    });
};

const submitWalkIn = async () => {
    const customerName = String(walkInForm.customer_name || '').trim();
    const branchId = Number(walkInForm.branch_id || activeBranchId.value || 0);

    if (!branchId) {
        localError.value = 'Cabang wajib dipilih.';
        return;
    }

    if (!customerName) {
        localError.value = 'Nama pelanggan wajib diisi.';
        return;
    }

    localError.value = '';

    try {
        await emit('add-walk-in', {
            branch_id: branchId,
            queue_date: String(walkInForm.queue_date || todayIso()),
            customer_name: customerName,
            customer_phone: String(walkInForm.customer_phone || '').trim() || null,
        });
        closeAddModal();
    } catch {
        // Parent handles server error message.
    }
};

const submitBooking = async () => {
    const bookingId = Number(bookingForm.booking_id || 0);

    if (!bookingId) {
        localError.value = 'Pilih booking terlebih dahulu.';
        return;
    }

    localError.value = '';

    try {
        await emit('add-booking', {
            booking_id: bookingId,
        });
        closeAddModal();
    } catch {
        // Parent handles server error message.
    }
};

const submitQueue = async () => {
    if (queueSourceType.value === 'booking') {
        await submitBooking();
        return;
    }

    await submitWalkIn();
};
</script>

<template>
    <div class="space-y-5">
        <section class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
            <div>
                <h2 class="text-[2rem] font-semibold text-[#0F172A]">Manajemen Antrean</h2>
                <p class="text-base text-[#64748B]">Panggil, mulai sesi, dan selesaikan pelanggan dari satu halaman.</p>
            </div>
            <div class="rounded-2xl border px-4 py-3 text-sm" style="border-color: #DBEAFE; background: #EFF6FF; color: #1E3A8A;">
                Cabang aktif: <span class="font-semibold">{{ activeBranchName }}</span>
            </div>
        </section>

        <p v-if="queueError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ queueError }}
        </p>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Dalam Antrean</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#2563EB]">{{ queueStats.in_queue || 0 }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Sedang Dilayani</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#7C3AED]">{{ queueStats.in_session || 0 }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Menunggu</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#D97706]">{{ queueStats.waiting || 0 }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Selesai Hari Ini</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#059669]">{{ queueStats.completed_today || 0 }}</p>
            </article>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <section class="overflow-hidden rounded-2xl border xl:col-span-5" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <div class="px-5 pb-4 pt-4" style="background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 58%, #3B82F6 100%);">
                    <div class="flex items-center justify-between text-[0.72rem] uppercase tracking-[0.08em] text-white/85">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Sedang Dilayani</span>
                        <Clock3 class="h-3.5 w-3.5" />
                    </div>

                    <template v-if="currentQueue">
                        <p class="mt-3 text-[4rem] font-extrabold leading-none text-white">{{ currentQueue.queue_code }}</p>
                        <p class="mt-2 text-xl font-medium text-white">{{ currentQueue.customer_name }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs" style="background: rgba(255,255,255,0.22); color: #FFFFFF;">{{ queuePackageLabel(currentQueue) }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs" style="background: rgba(15,23,42,0.25); color: #FFFFFF;">{{ resolveBranchName(currentQueue.branch_id) }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs" style="background: rgba(15,23,42,0.25); color: #FFFFFF;">{{ queueStatusLabel(currentQueue.status) }}</span>
                        </div>
                    </template>

                    <template v-else>
                        <p class="mt-5 text-lg font-semibold text-white">Belum ada sesi aktif</p>
                        <p class="mt-1 text-sm text-white/85">Klik Panggil Berikutnya untuk memulai pelanggan berikutnya.</p>
                    </template>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm text-[#64748B]">
                            <span>Progress Sesi</span>
                            <span class="font-semibold text-[#0F172A]">{{ queueRemainingText }} tersisa</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full" style="background: #E2E8F0;">
                            <div class="h-full rounded-full transition-all duration-700" :style="queueProgressStyle"></div>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs text-[#94A3B8]">
                            <span>Mulai</span>
                            <span>Durasi {{ queueSessionDurationText }}</span>
                        </div>
                    </div>

                    <div v-if="currentQueue?.ticket_id" class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl px-3 py-2.5 text-sm font-semibold"
                            style="background: #059669; color: #FFFFFF;"
                            :disabled="queueActionLoading || !currentPrimaryAction"
                            @click="runCurrentPrimaryAction"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            {{ currentPrimaryAction?.label || 'Tidak Ada Aksi' }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border px-3 py-2.5 text-sm font-semibold"
                            style="border-color: #FDE68A; background: #FFFBEB; color: #D97706;"
                            :disabled="queueActionLoading || !canSkipCurrent"
                            @click="skipCurrent"
                        >
                            <SkipForward class="h-4 w-4" />
                            Lewati
                        </button>
                    </div>

                    <button
                        v-else
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-1 rounded-xl px-3 py-2.5 text-sm font-semibold"
                        style="background: #2563EB; color: #FFFFFF;"
                        :disabled="queueActionLoading || queueLoading || !hasCallableTicket"
                        @click="queueCallNext"
                    >
                        <BellRing class="h-4 w-4" />
                        {{ hasCallableTicket ? 'Panggil Berikutnya' : 'Belum Ada Antrean Menunggu' }}
                    </button>

                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-1 rounded-xl border px-3 py-2.5 text-sm font-semibold"
                        style="border-color: #CBD5E1; background: #F8FAFC; color: #64748B;"
                        :disabled="queueLoading || queueActionLoading"
                        @click="refreshQueue"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Muat Ulang Data
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border xl:col-span-7" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4" style="border-color: #E2E8F0; background: #F8FAFC;">
                    <div>
                        <h3 class="text-[1.7rem] font-semibold leading-none text-[#0F172A]">Antrean Menunggu</h3>
                        <p class="mt-1 text-sm text-[#64748B]">{{ waitingTickets.length }} pelanggan menunggu giliran</p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="text-sm text-[#64748B]">
                            <span class="sr-only">Filter cabang</span>
                            <select
                                :value="selectedViewBranchId || ''"
                                class="rounded-xl border px-3 py-2 text-sm"
                                style="border-color: #DBEAFE; background: #FFFFFF; color: #1E293B;"
                                :disabled="queueLoading || queueActionLoading"
                                @change="onViewBranchChange"
                            >
                                <option value="">Semua Cabang</option>
                                <option v-for="branch in branchOptions" :key="`queue-filter-branch-${branch.id}`" :value="String(branch.id)">
                                    {{ branch.name }}
                                </option>
                            </select>
                        </label>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border px-3 py-2 text-sm font-semibold"
                            style="border-color: #DBEAFE; background: #EFF6FF; color: #1D4ED8;"
                            :disabled="queueLoading || queueActionLoading"
                            @click="refreshQueue"
                        >
                            <RefreshCw class="h-4 w-4" :class="queueLoading ? 'animate-spin' : ''" />
                            Muat Ulang
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl px-3.5 py-2 text-sm font-semibold"
                            style="background: #2563EB; color: #FFFFFF;"
                            :disabled="queueActionLoading"
                            @click="openAddModal"
                        >
                            <Plus class="h-4 w-4" />
                            Tambah Antrean
                        </button>
                    </div>
                </header>

                <div>
                    <div class="divide-y" style="border-color: #F1F5F9;">
                        <article
                            v-for="(ticket, index) in waitingTickets"
                            :key="`queue-waiting-${ticket.ticket_id || ticket.queue_code}-${index}`"
                            class="flex flex-col gap-3 px-4 py-4 sm:flex-row sm:items-center sm:justify-between"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl text-sm font-semibold" style="background: #3B82F6; color: #FFFFFF;">
                                    {{ ticket.queue_number || index + 1 }}
                                </span>
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="text-lg font-semibold leading-tight text-[#1E293B]">{{ ticket.queue_code }}</p>
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :style="queueStatusStyle(ticket.status)">
                                            {{ queueStatusLabel(ticket.status) }}
                                        </span>
                                    </div>
                                    <p class="truncate text-[1.05rem] text-[#334155]">{{ ticket.customer_name }}</p>
                                    <p class="text-sm text-[#64748B]">{{ resolveBranchName(ticket.branch_id) }} | {{ queuePackageLabel(ticket) }}</p>
                                    <p class="text-sm text-[#94A3B8]">Masuk pukul {{ ticket.added_at || '-' }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 sm:justify-end">
                                <button
                                    type="button"
                                    class="inline-flex min-h-[36px] min-w-[118px] items-center justify-center rounded-lg border px-3 py-1.5 text-xs font-semibold"
                                    style="border-color: #BFDBFE; background: #EFF6FF; color: #1D4ED8;"
                                    :disabled="queueActionLoading || canProcessTicket(ticket.ticket_id) || !nextStatusForTicket(ticket)"
                                    @click="promoteTicket(ticket)"
                                >
                                    {{ nextActionLabel(ticket) }}
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg border"
                                    style="border-color: #FECACA; color: #EF4444;"
                                    :disabled="queueActionLoading || canProcessTicket(ticket.ticket_id)"
                                    title="Batalkan tiket antrean"
                                    @click="cancelTicket(ticket)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                        </article>

                        <div v-if="!waitingTickets.length" class="px-5 py-12 text-center">
                            <UserPlus class="mx-auto mb-2 h-7 w-7 text-[#94A3B8]" />
                            <p class="text-sm font-semibold text-[#64748B]">Belum ada pelanggan yang menunggu.</p>
                            <p class="mt-1 text-sm text-[#94A3B8]">Tambahkan walk-in atau pastikan booking hari ini sudah diverifikasi.</p>
                        </div>
                    </div>

                    <div v-if="processingTickets.length" class="border-t px-5 py-4" style="border-color: #E2E8F0; background: #FAFBFC;">
                        <h4 class="text-sm font-semibold uppercase tracking-[0.08em] text-[#64748B]">Sedang Diproses</h4>
                        <div class="mt-3 space-y-2">
                            <article
                                v-for="ticket in processingTickets"
                                :key="`queue-processing-${ticket.ticket_id || ticket.queue_code}`"
                                class="flex flex-col gap-2 rounded-xl border bg-white px-3 py-3 sm:flex-row sm:items-center sm:justify-between"
                                style="border-color: #E2E8F0;"
                            >
                                <div>
                                    <p class="font-semibold text-[#0F172A]">{{ ticket.queue_code }} - {{ ticket.customer_name }}</p>
                                    <p class="text-sm text-[#64748B]">{{ resolveBranchName(ticket.branch_id) }} | {{ queuePackageLabel(ticket) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :style="queueStatusStyle(ticket.status)">
                                        {{ queueStatusLabel(ticket.status) }}
                                    </span>
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold"
                                        style="border-color: #BFDBFE; background: #EFF6FF; color: #1D4ED8;"
                                        :disabled="queueActionLoading || canProcessTicket(ticket.ticket_id) || !nextStatusForTicket(ticket)"
                                        @click="promoteTicket(ticket)"
                                    >
                                        {{ nextActionLabel(ticket) }}
                                    </button>
                                </div>
                            </article>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-xl border px-3 py-2 text-sm font-semibold"
                style="border-color: #DBEAFE; background: #EFF6FF; color: #1D4ED8;"
                :disabled="queueActionLoading || queueLoading || !hasCallableTicket"
                @click="queueCallNext"
            >
                <BellRing class="h-4 w-4" />
                Panggil Berikutnya
            </button>
            <span class="text-xs text-[#94A3B8]">Cabang: {{ activeBranchName }}</span>
        </div>

        <AdminModal :show="addModalOpen" panel-class="max-w-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">Tambah ke Antrean</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeAddModal">Tutup</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-2 rounded-2xl border p-1" style="border-color: #E2E8F0; background: #F8FAFC;">
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50"
                            :style="queueSourceType === 'booking'
                                ? 'background: #2563EB; color: #FFFFFF;'
                                : 'background: transparent; color: #64748B;'"
                            :disabled="!hasBookingOptions"
                            @click="setQueueSourceType('booking')"
                        >
                            Dari Booking
                        </button>
                        <button
                            type="button"
                            class="rounded-xl px-3 py-2 text-sm font-semibold transition"
                            :style="queueSourceType === 'walk_in'
                                ? 'background: #2563EB; color: #FFFFFF;'
                                : 'background: transparent; color: #64748B;'"
                            @click="setQueueSourceType('walk_in')"
                        >
                            Walk-in
                        </button>
                    </div>

                    <div v-if="queueSourceType === 'booking'" class="space-y-3">
                        <label class="text-sm text-[#475569]">
                            Booking
                            <select v-model="bookingForm.booking_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option :value="null">Pilih booking</option>
                                <option v-for="booking in bookingOptions" :key="`queue-booking-${booking.id}`" :value="booking.id">
                                    {{ booking.display_text || `${booking.booking_code} - ${booking.customer_name}` }}
                                </option>
                            </select>
                        </label>

                        <p v-if="!bookingOptions.length" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0; background: #F8FAFC; color: #64748B;">
                            Tidak ada booking yang siap dimasukkan ke antrean hari ini.
                        </p>

                        <div v-else-if="selectedBooking" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #DBEAFE; background: #EFF6FF; color: #1E3A8A;">
                            {{ selectedBooking.customer_name }} | {{ selectedBooking.branch_name }} | {{ selectedBooking.package_name }}
                        </div>
                    </div>

                    <div v-else class="rtp-admin-form-grid">
                        <label class="text-sm text-[#475569]">
                            Cabang
                            <select v-model="walkInForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option :value="null">Pilih cabang</option>
                                <option v-for="branch in branchOptions" :key="`queue-branch-${branch.id}`" :value="branch.id">
                                    {{ branch.name }}
                                </option>
                            </select>
                        </label>

                        <label class="text-sm text-[#475569]">
                            Tanggal Antrean
                            <input v-model="walkInForm.queue_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                        </label>

                        <label class="text-sm text-[#475569] md:col-span-2">
                            Nama Pelanggan
                            <input v-model="walkInForm.customer_name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                        </label>

                        <label class="text-sm text-[#475569] md:col-span-2">
                            Nomor HP (opsional)
                            <input v-model="walkInForm.customer_phone" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                        </label>
                    </div>
                </div>

                <div class="rtp-admin-actions mt-5">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeAddModal">Batal</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #2563EB; color: #FFFFFF;"
                        :disabled="queueActionLoading"
                        @click="submitQueue"
                    >
                        {{ queueActionLoading ? 'Menyimpan...' : 'Tambah Antrean' }}
                    </button>
                </div>
        </AdminModal>
    </div>
</template>
