<script setup>
import { computed, reactive, ref } from 'vue';
import {
    CheckCircle2,
    ChevronDown,
    ChevronUp,
    Clock3,
    Plus,
    RefreshCw,
    RotateCcw,
    SkipForward,
    Trash2,
    UserPlus,
} from 'lucide-vue-next';

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
});

const emit = defineEmits(['refresh-queue', 'call-next', 'transition-ticket', 'add-booking', 'add-walk-in']);

const addModalOpen = ref(false);
const localError = ref('');
const queueSourceType = ref('walk_in');

const bookingForm = reactive({
    booking_id: null,
});

const walkInForm = reactive({
    branch_id: null,
    queue_date: '',
    customer_name: '',
    customer_phone: '',
});

const todayIso = () => {
    return new Date().toISOString().slice(0, 10);
};

const activeBranchId = computed(() => {
    const fromForm = Number(walkInForm.branch_id || 0);
    const fromDefault = Number(props.defaultBranchId || 0);
    const fromOptions = Number(props.branchOptions?.[0]?.id || 0);

    return fromForm || fromDefault || fromOptions || null;
});

const hasBookingOptions = computed(() => {
    return Array.isArray(props.bookingOptions) && props.bookingOptions.length > 0;
});

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

const canProcessTicket = (ticketId) => {
    if (!props.queueActionLoading) {
        return false;
    }

    return Number(ticketId || 0) === Number(props.queueProcessingTicketId || 0);
};

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

const fallbackPreviousStatus = (status) => {
    const current = String(status || '').toLowerCase();

    if (current === 'called') {
        return 'waiting';
    }

    if (current === 'checked_in') {
        return 'called';
    }

    if (current === 'in_session') {
        return 'checked_in';
    }

    return '';
};

const queueCallNext = async () => {
    if (!activeBranchId.value) {
        localError.value = 'Please select a branch before calling next queue.';
        return;
    }

    localError.value = '';
    await emit('call-next', {
        branch_id: activeBranchId.value,
        queue_date: todayIso(),
    });
};

const completeCurrent = async () => {
    if (!props.currentQueue?.ticket_id) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: props.currentQueue.ticket_id,
        status: 'finished',
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
    const nextStatus = String(ticket?.next_status || fallbackNextStatus(ticket?.status || ''));

    if (!ticket?.ticket_id || !nextStatus) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: ticket.ticket_id,
        status: nextStatus,
    });
};

const demoteTicket = async (ticket) => {
    const previousStatus = String(ticket?.previous_status || fallbackPreviousStatus(ticket?.status || ''));

    if (!ticket?.ticket_id || !previousStatus) {
        return;
    }

    await emit('transition-ticket', {
        ticketId: ticket.ticket_id,
        status: previousStatus,
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
        localError.value = 'Branch is required.';
        return;
    }

    if (!customerName) {
        localError.value = 'Customer name is required.';
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
        localError.value = 'Please select a booking.';
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
        <section>
            <h2 class="text-[2rem] font-semibold text-[#0F172A]">Queue Management</h2>
            <p class="text-base text-[#64748B]">Live session queue control</p>
        </section>

        <p v-if="queueError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ queueError }}
        </p>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">In Queue</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#2563EB]">{{ queueStats.in_queue || 0 }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Now Serving</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#7C3AED]">{{ queueStats.in_session || 0 }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Waiting</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#D97706]">{{ queueStats.waiting || 0 }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Completed Today</p>
                <p class="mt-1 text-[2.05rem] font-bold text-[#059669]">{{ queueStats.completed_today || 0 }}</p>
            </article>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-12">
            <section class="overflow-hidden rounded-2xl border xl:col-span-5" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <div class="px-5 pb-4 pt-4" style="background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 58%, #3B82F6 100%);">
                    <div class="flex items-center justify-between text-[0.72rem] uppercase tracking-[0.08em] text-white/85">
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Now Serving</span>
                        <Clock3 class="h-3.5 w-3.5" />
                    </div>

                    <template v-if="currentQueue">
                        <p class="mt-3 text-[4rem] font-extrabold leading-none text-white">{{ currentQueue.queue_code }}</p>
                        <p class="mt-2 text-xl font-medium text-white">{{ currentQueue.customer_name }}</p>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="rounded-full px-2.5 py-1 text-xs" style="background: rgba(255,255,255,0.22); color: #FFFFFF;">{{ currentQueue.package_name }}</span>
                            <span class="rounded-full px-2.5 py-1 text-xs" style="background: rgba(15,23,42,0.25); color: #FFFFFF;">{{ currentQueue.status_label }}</span>
                        </div>
                    </template>

                    <template v-else>
                        <p class="mt-5 text-lg font-semibold text-white">No active session</p>
                        <p class="mt-1 text-sm text-white/85">Click Call Next to start the next ticket.</p>
                    </template>
                </div>

                <div class="space-y-4 p-4">
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm text-[#64748B]">
                            <span>Session Progress</span>
                            <span class="font-semibold text-[#0F172A]">{{ queueRemainingText }} remaining</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full" style="background: #E2E8F0;">
                            <div class="h-full rounded-full transition-all duration-700" :style="queueProgressStyle"></div>
                        </div>
                        <div class="mt-1 flex items-center justify-between text-xs text-[#94A3B8]">
                            <span>Start</span>
                            <span>{{ queueSessionDurationText }}</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl px-3 py-2.5 text-sm font-semibold"
                            style="background: #059669; color: #FFFFFF;"
                            :disabled="queueActionLoading || !currentQueue?.ticket_id"
                            @click="completeCurrent"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Complete
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border px-3 py-2.5 text-sm font-semibold"
                            style="border-color: #FDE68A; background: #FFFBEB; color: #D97706;"
                            :disabled="queueActionLoading || !currentQueue?.ticket_id"
                            @click="skipCurrent"
                        >
                            <SkipForward class="h-4 w-4" />
                            Skip
                        </button>
                    </div>

                    <button
                        type="button"
                        class="inline-flex w-full items-center justify-center gap-1 rounded-xl border px-3 py-2.5 text-sm font-semibold"
                        style="border-color: #CBD5E1; background: #F8FAFC; color: #64748B;"
                        :disabled="queueLoading || queueActionLoading"
                        @click="emit('refresh-queue')"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Reset Timer
                    </button>
                </div>
            </section>

            <section class="overflow-hidden rounded-2xl border xl:col-span-7" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4" style="border-color: #E2E8F0; background: #F8FAFC;">
                    <div>
                        <h3 class="text-[1.7rem] font-semibold leading-none text-[#0F172A]">Waiting List</h3>
                        <p class="mt-1 text-sm text-[#64748B]">{{ waitingQueue.length }} in queue</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl border px-3 py-2 text-sm font-semibold"
                            style="border-color: #DBEAFE; background: #EFF6FF; color: #1D4ED8;"
                            :disabled="queueLoading || queueActionLoading"
                            @click="emit('refresh-queue')"
                        >
                            <RefreshCw class="h-4 w-4" :class="queueLoading ? 'animate-spin' : ''" />
                            Refresh
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center justify-center gap-1 rounded-xl px-3.5 py-2 text-sm font-semibold"
                            style="background: #2563EB; color: #FFFFFF;"
                            :disabled="queueActionLoading"
                            @click="openAddModal"
                        >
                            <Plus class="h-4 w-4" />
                            Add to Queue
                        </button>
                    </div>
                </header>

                <div class="divide-y" style="border-color: #F1F5F9;">
                    <article
                        v-for="(ticket, index) in waitingQueue"
                        :key="`queue-waiting-${ticket.ticket_id || ticket.queue_code}-${index}`"
                        class="flex items-center justify-between gap-3 px-4 py-3"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-semibold" style="background: #3B82F6; color: #FFFFFF;">
                                {{ ticket.queue_number || index + 1 }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-lg font-semibold leading-tight text-[#1E293B]">{{ ticket.queue_code }}</p>
                                <p class="truncate text-[1.05rem] text-[#334155]">{{ ticket.customer_name }}</p>
                                <p class="text-sm text-[#94A3B8]">Added at {{ ticket.added_at || '-' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium" :style="{ background: resolveQueueStatus(ticket.status).bg, color: resolveQueueStatus(ticket.status).color }">
                                    {{ ticket.package_name }}
                                </span>
                                <p class="mt-1 text-xs uppercase tracking-wide text-[#94A3B8]">{{ ticket.status_label }}</p>
                            </div>

                            <div class="flex flex-col gap-1">
                                <button
                                    type="button"
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-md border"
                                    style="border-color: #CBD5E1; color: #64748B;"
                                    :disabled="queueActionLoading || canProcessTicket(ticket.ticket_id) || !ticket.next_status"
                                    @click="promoteTicket(ticket)"
                                >
                                    <ChevronUp class="h-3.5 w-3.5" />
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex h-6 w-6 items-center justify-center rounded-md border"
                                    style="border-color: #CBD5E1; color: #64748B;"
                                    :disabled="queueActionLoading || canProcessTicket(ticket.ticket_id) || !ticket.previous_status"
                                    @click="demoteTicket(ticket)"
                                >
                                    <ChevronDown class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            <button
                                type="button"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg border"
                                style="border-color: #FECACA; color: #EF4444;"
                                :disabled="queueActionLoading || canProcessTicket(ticket.ticket_id)"
                                @click="cancelTicket(ticket)"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </article>

                    <div v-if="!waitingQueue.length" class="px-5 py-12 text-center">
                        <UserPlus class="mx-auto mb-2 h-7 w-7 text-[#94A3B8]" />
                        <p class="text-sm text-[#94A3B8]">Queue waiting list is currently empty.</p>
                        <button
                            type="button"
                            class="mt-3 rounded-xl px-3 py-2 text-sm font-semibold"
                            style="background: #2563EB; color: #FFFFFF;"
                            :disabled="queueActionLoading"
                            @click="queueCallNext"
                        >
                            Call Next
                        </button>
                    </div>
                </div>
            </section>
        </div>

        <div class="flex items-center gap-2">
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-xl border px-3 py-2 text-sm font-semibold"
                style="border-color: #DBEAFE; background: #EFF6FF; color: #1D4ED8;"
                :disabled="queueActionLoading || queueLoading"
                @click="queueCallNext"
            >
                <Plus class="h-4 w-4" />
                Call Next Queue
            </button>
            <span class="text-xs text-[#94A3B8]">Branch: {{ activeBranchId || '-' }}</span>
        </div>

        <div v-if="addModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">Add to Queue</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeAddModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="space-y-3">
                    <label class="text-sm text-[#475569]">
                        Queue Source
                        <select v-model="queueSourceType" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="booking">Booking</option>
                            <option value="walk_in">Walk In</option>
                        </select>
                    </label>

                    <div v-if="queueSourceType === 'booking'" class="space-y-3">
                        <label class="text-sm text-[#475569]">
                            Booking
                            <select v-model="bookingForm.booking_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option :value="null">Select booking</option>
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

                    <div v-else class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        <label class="text-sm text-[#475569]">
                            Branch
                            <select v-model="walkInForm.branch_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                <option :value="null">Select branch</option>
                                <option v-for="branch in branchOptions" :key="`queue-branch-${branch.id}`" :value="branch.id">
                                    {{ branch.name }}
                                </option>
                            </select>
                        </label>

                        <label class="text-sm text-[#475569]">
                            Queue Date
                            <input v-model="walkInForm.queue_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                        </label>

                        <label class="text-sm text-[#475569] md:col-span-2">
                            Customer Name
                            <input v-model="walkInForm.customer_name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                        </label>

                        <label class="text-sm text-[#475569] md:col-span-2">
                            Phone (optional)
                            <input v-model="walkInForm.customer_phone" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                        </label>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeAddModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #2563EB; color: #FFFFFF;"
                        :disabled="queueActionLoading"
                        @click="submitQueue"
                    >
                        {{ queueActionLoading ? 'Saving...' : 'Add Queue' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
