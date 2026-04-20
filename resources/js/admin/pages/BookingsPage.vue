<script setup>
import { computed, reactive, ref, watch } from 'vue';
import { CheckCircle2, Eye, Pencil, Plus, RefreshCw, Search, Trash2, Wallet } from 'lucide-vue-next';

const props = defineProps({
    search: { type: String, default: '' },
    filterStatus: { type: String, default: 'all' },
    filterTabs: { type: Array, default: () => [] },
    panelBookingsUrl: { type: String, default: '/admin/bookings' },
    normalizedRows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    bookingError: { type: String, default: '' },
    bookingOptions: {
        type: Object,
        default: () => ({
            branches: [],
            packages: [],
            designs: [],
            payment_methods: [],
        }),
    },
    defaultBranchId: { type: [Number, String, null], default: null },
    availabilityUrl: { type: String, default: '/booking/availability' },
    deletingBookingId: { type: [Number, String, null], default: null },
    processingBookingId: { type: [Number, String, null], default: null },
    bookingResultCaption: { type: String, default: '' },
    canGoPrev: { type: Boolean, default: false },
    canGoNext: { type: Boolean, default: false },
    pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1 }) },
    resolveBookingStatus: { type: Function, required: true },
});

const emit = defineEmits([
    'update:search',
    'set-filter-status',
    'go-prev-page',
    'go-next-page',
    'refresh-bookings',
    'create-booking',
    'update-booking',
    'delete-booking',
    'confirm-booking',
    'confirm-booking-payment',
]);

const bookingModalOpen = ref(false);
const bookingModalMode = ref('create');
const editingBookingId = ref(null);
const bookingDetailModalOpen = ref(false);
const bookingDetail = ref(null);
const paymentModalOpen = ref(false);
const paymentTargetId = ref(null);
const paymentTargetCode = ref('');
const localError = ref('');
const slotLoading = ref(false);
const slotLookupError = ref('');
const availableSlots = ref([]);

const bookingForm = reactive({
    branch_id: '',
    package_id: '',
    design_catalog_id: '',
    add_ons: [],
    customer_name: '',
    customer_phone: '',
    customer_email: '',
    booking_date: '',
    booking_time: '',
    notes: '',
});

const paymentForm = reactive({
    method: '',
    amount: '',
    reference_no: '',
    notes: '',
});

const branchOptions = computed(() => Array.isArray(props.bookingOptions?.branches) ? props.bookingOptions.branches : []);
const packageOptions = computed(() => Array.isArray(props.bookingOptions?.packages) ? props.bookingOptions.packages : []);
const designOptions = computed(() => Array.isArray(props.bookingOptions?.designs) ? props.bookingOptions.designs : []);
const paymentMethods = computed(() => Array.isArray(props.bookingOptions?.payment_methods) ? props.bookingOptions.payment_methods : []);
const addOnOptions = computed(() => Array.isArray(props.bookingOptions?.add_ons) ? props.bookingOptions.add_ons : []);

const resolvedDefaultBranchId = computed(() => {
    const fromProps = Number(props.defaultBranchId || 0);

    if (fromProps > 0) {
        return fromProps;
    }

    const fromOptions = Number(branchOptions.value[0]?.id || 0);

    return fromOptions > 0 ? fromOptions : null;
});

const selectedBranchName = computed(() => {
    const selectedId = Number(bookingForm.branch_id || 0);

    if (!selectedId) {
        return '-';
    }

    const branch = branchOptions.value.find((item) => Number(item?.id || 0) === selectedId);

    return String(branch?.name || '-');
});

const formatCurrency = (value) => `Rp ${Number(value || 0).toLocaleString('id-ID')}`;

const bookingDetailAddOns = computed(() => {
    const source = Array.isArray(bookingDetail.value?.add_ons) ? bookingDetail.value.add_ons : [];

    return source.map((item) => ({
        label: String(item.label || '-'),
        qty: Number(item.qty || 0),
        line_total: Number(item.line_total || 0),
    }));
});

const bookingDetailAddOnsTotal = computed(() => {
    const payloadTotal = Number(bookingDetail.value?.add_ons_total || 0);

    if (payloadTotal > 0) {
        return payloadTotal;
    }

    return bookingDetailAddOns.value.reduce((sum, item) => sum + Number(item.line_total || 0), 0);
});

const toHourMinute = (value) => String(value || '').slice(0, 5);

const normalizedSlotOptions = computed(() => {
    return availableSlots.value.map((slot) => ({
        slot_id: Number(slot.slot_id || 0),
        start_time: toHourMinute(slot.start_time),
        end_time: toHourMinute(slot.end_time),
        start_label: String(slot.start_label || toHourMinute(slot.start_time)),
        end_label: String(slot.end_label || toHourMinute(slot.end_time)),
        remaining_slots: Number(slot.remaining_slots || 0),
        is_available: Boolean(slot.is_available),
    }));
});

const filteredDesignOptions = computed(() => {
    const packageId = Number(bookingForm.package_id || 0);

    if (!packageId) {
        return designOptions.value;
    }

    return designOptions.value.filter((design) => Number(design.package_id || 0) === packageId);
});

const filteredAddOnOptions = computed(() => {
    const packageId = Number(bookingForm.package_id || 0);

    return addOnOptions.value
        .filter((item) => {
            const addOnPackageId = item.package_id ? Number(item.package_id) : null;

            return addOnPackageId === null || !packageId || addOnPackageId === packageId;
        })
        .map((item) => ({
            id: Number(item.id || 0),
            package_id: item.package_id ? Number(item.package_id) : null,
            name: String(item.name || '-'),
            price: Number(item.price || 0),
            max_qty: Math.max(1, Number(item.max_qty || 1)),
            price_text: String(item.price_text || formatCurrency(item.price || 0)),
        }));
});

const addOnMaxQtyMap = computed(() => {
    const map = new Map();

    for (const item of filteredAddOnOptions.value) {
        map.set(Number(item.id || 0), Math.max(1, Number(item.max_qty || 1)));
    }

    return map;
});

const selectedAddOnQtyMap = computed(() => {
    const map = new Map();

    for (const item of bookingForm.add_ons) {
        const addOnId = Number(item?.add_on_id || 0);
        const qty = Number(item?.qty || 0);

        if (addOnId > 0 && qty > 0) {
            map.set(addOnId, qty);
        }
    }

    return map;
});

const toggleAddOnSelection = (addOnId, checked) => {
    const id = Number(addOnId || 0);

    if (!id) {
        return;
    }

    const current = bookingForm.add_ons.filter((item) => Number(item.add_on_id || 0) !== id);

    if (checked) {
        current.push({ add_on_id: id, qty: 1 });
    }

    bookingForm.add_ons = current;
};

const updateAddOnQty = (addOnId, rawQty) => {
    const id = Number(addOnId || 0);
    const maxQty = Math.max(1, Number(addOnMaxQtyMap.value.get(id) || 1));
    const qty = Math.max(1, Math.min(maxQty, Number(rawQty || 1)));

    if (!id) {
        return;
    }

    const current = bookingForm.add_ons.filter((item) => Number(item.add_on_id || 0) !== id);
    current.push({ add_on_id: id, qty });

    bookingForm.add_ons = current;
};

const fetchAvailableSlots = async () => {
    const branchId = Number(bookingForm.branch_id || 0);
    const packageId = Number(bookingForm.package_id || 0);
    const date = String(bookingForm.booking_date || '');

    if (!props.availabilityUrl || !branchId || !packageId || !date) {
        availableSlots.value = [];
        slotLookupError.value = '';
        bookingForm.booking_time = '';
        return;
    }

    slotLoading.value = true;
    slotLookupError.value = '';

    try {
        const params = new URLSearchParams({
            branch_id: String(branchId),
            package_id: String(packageId),
            date,
        });

        if (bookingModalMode.value === 'edit' && editingBookingId.value) {
            params.set('booking_id', String(editingBookingId.value));
        }

        const response = await fetch(`${props.availabilityUrl}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const payload = await response.json();
        const slots = Array.isArray(payload?.data) ? payload.data : [];

        availableSlots.value = slots;

        const selected = toHourMinute(bookingForm.booking_time);
        const hasSelected = normalizedSlotOptions.value.some((slot) => slot.start_time === selected && slot.is_available);

        if (!hasSelected) {
            const firstAvailable = normalizedSlotOptions.value.find((slot) => slot.is_available);
            bookingForm.booking_time = firstAvailable ? firstAvailable.start_time : '';
        }
    } catch (error) {
        availableSlots.value = [];
        bookingForm.booking_time = '';
        slotLookupError.value = error instanceof Error ? error.message : 'Failed to load slot availability.';
    } finally {
        slotLoading.value = false;
    }
};

const resetBookingForm = () => {
    const now = new Date();
    const date = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

    bookingForm.branch_id = resolvedDefaultBranchId.value ? String(resolvedDefaultBranchId.value) : '';
    bookingForm.package_id = '';
    bookingForm.design_catalog_id = '';
    bookingForm.add_ons = [];
    bookingForm.customer_name = '';
    bookingForm.customer_phone = '';
    bookingForm.customer_email = '';
    bookingForm.booking_date = date;
    bookingForm.booking_time = '';
    bookingForm.notes = '';

    editingBookingId.value = null;
    localError.value = '';
    slotLookupError.value = '';
    availableSlots.value = [];
};

const openCreateBookingModal = () => {
    resetBookingForm();
    bookingModalMode.value = 'create';
    bookingModalOpen.value = true;
};

const openEditBookingModal = (row) => {
    bookingModalMode.value = 'edit';
    editingBookingId.value = Number(row.record_id || 0);

    bookingForm.branch_id = row.branch_id
        ? String(row.branch_id)
        : (resolvedDefaultBranchId.value ? String(resolvedDefaultBranchId.value) : '');
    bookingForm.package_id = row.package_id ? String(row.package_id) : '';
    bookingForm.design_catalog_id = row.design_catalog_id ? String(row.design_catalog_id) : '';
    bookingForm.add_ons = Array.isArray(row.add_ons)
        ? row.add_ons
            .map((item) => ({
                add_on_id: Number(item.add_on_id || 0),
                qty: Math.max(1, Number(item.qty || 1)),
            }))
            .filter((item) => item.add_on_id > 0)
        : [];
    bookingForm.customer_name = String(row.name || '');
    bookingForm.customer_phone = String(row.customer_phone || '');
    bookingForm.customer_email = String(row.customer_email || '');
    bookingForm.booking_date = String(row.booking_date_iso || '');
    bookingForm.booking_time = String(row.start_time || row.time || '10:00');
    bookingForm.notes = String(row.notes || '');

    localError.value = '';
    bookingModalOpen.value = true;
    fetchAvailableSlots();
};

const closeBookingModal = () => {
    bookingModalOpen.value = false;
    localError.value = '';
    slotLookupError.value = '';
    availableSlots.value = [];
};

const openBookingDetailModal = (row) => {
    bookingDetail.value = {
        ...row,
        add_ons: Array.isArray(row.add_ons) ? row.add_ons : [],
    };
    bookingDetailModalOpen.value = true;
};

const closeBookingDetailModal = () => {
    bookingDetailModalOpen.value = false;
    bookingDetail.value = null;
};

const validateBookingForm = () => {
    if (!bookingForm.branch_id) {
        localError.value = 'Default branch is not configured in settings.';
        return false;
    }

    if (!bookingForm.package_id) {
        localError.value = 'Package is required.';
        return false;
    }

    if (!String(bookingForm.customer_name || '').trim()) {
        localError.value = 'Customer name is required.';
        return false;
    }

    if (!String(bookingForm.customer_phone || '').trim()) {
        localError.value = 'Customer phone is required.';
        return false;
    }

    if (!bookingForm.booking_date || !bookingForm.booking_time) {
        localError.value = 'Booking date and time are required.';
        return false;
    }

    const selectedSlot = normalizedSlotOptions.value.find((slot) => slot.start_time === toHourMinute(bookingForm.booking_time));

    if (!selectedSlot || !selectedSlot.is_available) {
        localError.value = 'Please select an available slot based on opening hours and package duration.';
        return false;
    }

    localError.value = '';
    return true;
};

const submitBookingForm = async () => {
    if (!validateBookingForm()) {
        return;
    }

    const payload = {
        branch_id: Number(bookingForm.branch_id),
        package_id: Number(bookingForm.package_id),
        design_catalog_id: bookingForm.design_catalog_id ? Number(bookingForm.design_catalog_id) : null,
        add_ons: bookingForm.add_ons
            .map((item) => ({
                add_on_id: Number(item.add_on_id || 0),
                qty: Number(item.qty || 0),
            }))
            .filter((item) => item.add_on_id > 0 && item.qty > 0),
        customer_name: String(bookingForm.customer_name || '').trim(),
        customer_phone: String(bookingForm.customer_phone || '').trim(),
        customer_email: String(bookingForm.customer_email || '').trim(),
        booking_date: String(bookingForm.booking_date || ''),
        booking_time: String(bookingForm.booking_time || ''),
        notes: String(bookingForm.notes || '').trim(),
    };

    try {
        if (bookingModalMode.value === 'create') {
            await emit('create-booking', payload);
        } else {
            await emit('update-booking', {
                id: editingBookingId.value,
                payload,
            });
        }

        bookingModalOpen.value = false;
    } catch {
        // Parent handles server error message.
    }
};

const requestDeleteBooking = async (row) => {
    const confirmed = window.confirm(`Delete booking ${row.booking_code}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    try {
        await emit('delete-booking', Number(row.record_id || 0));
    } catch {
        // Parent handles server error message.
    }
};

const requestConfirmBooking = async (row) => {
    const confirmed = window.confirm(`Confirm booking ${row.booking_code}?`);

    if (!confirmed) {
        return;
    }

    try {
        await emit('confirm-booking', {
            id: Number(row.record_id || 0),
            reason: 'Confirmed from booking page',
        });
    } catch {
        // Parent handles server error message.
    }
};

const openPaymentModal = (row) => {
    paymentTargetId.value = Number(row.record_id || 0);
    paymentTargetCode.value = String(row.booking_code || row.id || '-');
    paymentForm.method = String(paymentMethods.value[0]?.value || 'cash');
    paymentForm.amount = String(row.remaining_amount || row.total_amount || '');
    paymentForm.reference_no = '';
    paymentForm.notes = 'Payment confirmed from booking page';
    localError.value = '';
    paymentModalOpen.value = true;
};

const closePaymentModal = () => {
    paymentModalOpen.value = false;
    localError.value = '';
};

const submitPaymentConfirmation = async () => {
    if (!paymentTargetId.value) {
        return;
    }

    const amount = Number(paymentForm.amount || 0);

    if (!paymentForm.method) {
        localError.value = 'Payment method is required.';
        return;
    }

    if (amount <= 0) {
        localError.value = 'Payment amount must be greater than zero.';
        return;
    }

    try {
        await emit('confirm-booking-payment', {
            id: paymentTargetId.value,
            payload: {
                method: String(paymentForm.method),
                amount,
                reference_no: String(paymentForm.reference_no || '').trim(),
                notes: String(paymentForm.notes || '').trim(),
            },
        });

        paymentModalOpen.value = false;
    } catch {
        // Parent handles server error message.
    }
};

watch(
    () => [
        bookingForm.package_id,
        addOnOptions.value,
    ],
    () => {
        const packageId = Number(bookingForm.package_id || 0);

        bookingForm.add_ons = bookingForm.add_ons.filter((selected) => {
            const addOnId = Number(selected?.add_on_id || 0);
            const option = addOnOptions.value.find((item) => Number(item.id || 0) === addOnId);

            if (!option) {
                return false;
            }

            const addOnPackageId = option.package_id ? Number(option.package_id) : null;

            return addOnPackageId === null || !packageId || addOnPackageId === packageId;
        });
    },
    { deep: true },
);

watch(
    () => [
        bookingModalOpen.value,
        bookingForm.branch_id,
        bookingForm.package_id,
        bookingForm.booking_date,
        editingBookingId.value,
    ],
    () => {
        if (!bookingModalOpen.value) {
            return;
        }

        fetchAvailableSlots();
    },
);

watch(
    resolvedDefaultBranchId,
    (nextBranchId) => {
        if (!bookingModalOpen.value) {
            return;
        }

        if (bookingModalMode.value === 'edit' && bookingForm.branch_id) {
            return;
        }

        bookingForm.branch_id = nextBranchId ? String(nextBranchId) : '';
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(135deg, #0F766E 0%, #0EA5A5 62%, #14B8A6 100%); box-shadow: 0 6px 24px rgba(15,118,110,0.26);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-8 top-0 h-28 w-28 rounded-full" style="background: rgba(240,253,250,0.2);"></div>
                <div class="absolute right-6 top-5 h-10 w-10 rounded-full" style="background: rgba(204,251,241,0.24);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Bookings</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.78);">Real-time reservation monitoring with CRUD and booking/payment confirmations.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold"
                        style="border-color: rgba(255,255,255,0.34); background: rgba(255,255,255,0.1); color: #FFFFFF;"
                        :disabled="loading"
                        @click="emit('refresh-bookings')"
                    >
                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                        Refresh
                    </button>
                    <button type="button" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #0F766E;" @click="openCreateBookingModal">
                        <Plus class="mr-1 inline h-3.5 w-3.5" />
                        New Booking
                    </button>
                </div>
            </div>
        </section>

        <p v-if="bookingError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ bookingError }}
        </p>

        <div class="overflow-hidden rounded-2xl border" style="background: #FFFFFF; border-color: #CCFBF1; box-shadow: 0 1px 3px rgba(15,118,110,0.08), 0 8px 20px rgba(15,118,110,0.08);">
            <div class="border-b p-6" style="border-color: #CCFBF1; background: #F0FDFA;">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" />
                        <input
                            :value="search"
                            type="text"
                            placeholder="Search by customer name or booking ID..."
                            class="w-full rounded-lg border py-2 pl-9 pr-4 text-sm"
                            style="background: #F8FAFC; border-color: #EEF2FF;"
                            @input="emit('update:search', $event.target.value)"
                        >
                    </div>
                </div>

                <div class="mt-3 flex gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="tab in filterTabs"
                        :key="`booking-module-filter-tab-${tab.key}`"
                        type="button"
                        class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs"
                        :style="{
                            background: filterStatus === tab.key ? '#2563EB' : '#F8FAFC',
                            color: filterStatus === tab.key ? '#FFFFFF' : '#64748B',
                            border: `1px solid ${filterStatus === tab.key ? '#2563EB' : '#EEF2FF'}`,
                        }"
                        @click="emit('set-filter-status', tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #CCFBF1; background: #F0FDFA;">
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Booking ID</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Customer</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Package</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Date and Time</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Amount</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Payment</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Status</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in normalizedRows" :key="`booking-module-row-${row.id}`" style="border-bottom: 1px solid #F8FAFC;">
                            <td class="px-5 py-3.5"><span class="text-sm font-semibold text-[#2563EB]">{{ row.booking_code }}</span></td>
                            <td class="px-5 py-3.5 text-sm text-[#1F2937]">
                                <p>{{ row.name }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ row.customer_phone || '-' }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-[#374151]">
                                <p>{{ row.pkg }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ row.design_name || '-' }}</p>
                                <p class="text-xs text-[#0F766E]">Add-ons: {{ Number(row.add_ons_count || 0) }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <p class="text-sm text-[#1F2937]">{{ row.date }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ row.time }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-[#1F2937]">
                                <p class="font-semibold">{{ row.amount_text }}</p>
                                <p class="text-xs text-[#94A3B8]">Paid: Rp {{ Number(row.paid_amount || 0).toLocaleString('id-ID') }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-[#64748B]">
                                <p>{{ row.payment }}</p>
                                <p class="uppercase">{{ row.payment_status }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs"
                                    :style="{ background: resolveBookingStatus(row.status).bg, color: resolveBookingStatus(row.status).color }"
                                >
                                    {{ resolveBookingStatus(row.status).label }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-semibold"
                                        style="border-color: #99F6E4; color: #0F766E;"
                                        @click="openBookingDetailModal(row)"
                                    >
                                        <Eye class="h-3 w-3" />
                                        Detail
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-semibold"
                                        style="border-color: #BFDBFE; color: #2563EB;"
                                        @click="openEditBookingModal(row)"
                                    >
                                        <Pencil class="h-3 w-3" />
                                        Edit
                                    </button>
                                    <button
                                        v-if="row.can_confirm_booking"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-semibold"
                                        style="border-color: #A7F3D0; color: #059669;"
                                        :disabled="Number(processingBookingId || 0) === Number(row.record_id)"
                                        @click="requestConfirmBooking(row)"
                                    >
                                        <CheckCircle2 class="h-3 w-3" />
                                        Confirm
                                    </button>
                                    <button
                                        v-if="row.can_confirm_payment"
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-semibold"
                                        style="border-color: #FDE68A; color: #D97706;"
                                        :disabled="Number(processingBookingId || 0) === Number(row.record_id)"
                                        @click="openPaymentModal(row)"
                                    >
                                        <Wallet class="h-3 w-3" />
                                        Pay
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex items-center gap-1 rounded-lg border px-2 py-1 text-[11px] font-semibold"
                                        style="border-color: #FECACA; color: #EF4444;"
                                        :disabled="Number(deletingBookingId || 0) === Number(row.record_id)"
                                        @click="requestDeleteBooking(row)"
                                    >
                                        <Trash2 class="h-3 w-3" />
                                        Delete
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-[#94A3B8]">Loading bookings...</td>
                        </tr>
                        <tr v-else-if="!normalizedRows.length">
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No bookings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 p-4" style="border-top: 1px solid #F1F5F9;">
                <p class="text-xs text-[#94A3B8]">{{ bookingResultCaption }}</p>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs text-gray-600"
                        :class="canGoPrev ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                        :disabled="!canGoPrev || loading"
                        @click="emit('go-prev-page')"
                    >
                        Previous
                    </button>
                    <span class="text-xs text-[#94A3B8]">Page {{ pagination.current_page }} / {{ Math.max(pagination.last_page, 1) }}</span>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs text-gray-600"
                        :class="canGoNext ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                        :disabled="!canGoNext || loading"
                        @click="emit('go-next-page')"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>

        <div v-if="bookingModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-2xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">{{ bookingModalMode === 'create' ? 'Create Booking' : 'Edit Booking' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeBookingModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <div class="text-sm text-[#475569]">
                        Branch
                        <div class="mt-1 rounded-lg border px-3 py-2" style="border-color: #E2E8F0; background: #F8FAFC;">
                            <p class="text-sm text-[#0F172A]">{{ selectedBranchName }}</p>
                            <p class="text-xs text-[#64748B]">Managed from Settings page.</p>
                        </div>
                    </div>

                    <label class="text-sm text-[#475569]">
                        Package
                        <select v-model="bookingForm.package_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="">Select package</option>
                            <option v-for="pkg in packageOptions" :key="`booking-package-${pkg.id}`" :value="String(pkg.id)">{{ pkg.name }}</option>
                        </select>
                    </label>

                    <label class="text-sm text-[#475569]">
                        Design
                        <select v-model="bookingForm.design_catalog_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="">No design</option>
                            <option v-for="design in filteredDesignOptions" :key="`booking-design-${design.id}`" :value="String(design.id)">{{ design.name }}</option>
                        </select>
                    </label>

                    <div class="text-sm text-[#475569] md:col-span-2">
                        <p class="mb-1">Add-ons</p>
                        <div v-if="filteredAddOnOptions.length" class="space-y-2 rounded-lg border p-3" style="border-color: #E2E8F0; background: #F8FAFC;">
                            <div
                                v-for="addOn in filteredAddOnOptions"
                                :key="`booking-addon-${addOn.id}`"
                                class="flex flex-wrap items-center justify-between gap-2 rounded-lg border px-3 py-2"
                                style="border-color: #E2E8F0; background: #FFFFFF;"
                            >
                                <label class="flex items-center gap-2 text-sm text-[#334155]">
                                    <input
                                        type="checkbox"
                                        class="rounded border"
                                        :checked="selectedAddOnQtyMap.has(addOn.id)"
                                        @change="toggleAddOnSelection(addOn.id, $event.target.checked)"
                                    >
                                    <span>{{ addOn.name }} <span class="text-xs text-[#64748B]">({{ addOn.price_text }})</span></span>
                                </label>

                                <label v-if="selectedAddOnQtyMap.has(addOn.id)" class="flex items-center gap-2 text-xs text-[#64748B]">
                                    Qty
                                    <input
                                        type="number"
                                        min="1"
                                        max="99"
                                        class="w-20 rounded-lg border px-2 py-1 text-sm"
                                        style="border-color: #CBD5E1;"
                                        :value="selectedAddOnQtyMap.get(addOn.id)"
                                        @input="updateAddOnQty(addOn.id, $event.target.value)"
                                    >
                                </label>
                            </div>
                        </div>
                        <p v-else class="rounded-lg border px-3 py-2 text-xs text-[#64748B]" style="border-color: #E2E8F0; background: #F8FAFC;">
                            No active add-ons for selected package.
                        </p>
                    </div>

                    <label class="text-sm text-[#475569]">
                        Customer Name
                        <input v-model="bookingForm.customer_name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Customer Phone
                        <input v-model="bookingForm.customer_phone" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Customer Email
                        <input v-model="bookingForm.customer_email" type="email" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Booking Date
                        <input v-model="bookingForm.booking_date" type="date" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Booking Time
                        <select v-model="bookingForm.booking_time" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="">Select available slot</option>
                            <option
                                v-for="slot in normalizedSlotOptions"
                                :key="`slot-option-${slot.slot_id}-${slot.start_time}`"
                                :value="slot.start_time"
                                :disabled="!slot.is_available"
                            >
                                {{ slot.start_label }} - {{ slot.end_label }}
                                {{ slot.is_available ? `(${slot.remaining_slots} left)` : '(full / unavailable)' }}
                            </option>
                        </select>
                        <p v-if="slotLoading" class="mt-1 text-xs text-[#64748B]">Loading available slots...</p>
                        <p v-else-if="slotLookupError" class="mt-1 text-xs text-[#B91C1C]">{{ slotLookupError }}</p>
                        <p v-else-if="bookingForm.branch_id && bookingForm.package_id && bookingForm.booking_date && !normalizedSlotOptions.some((slot) => slot.is_available)" class="mt-1 text-xs text-[#D97706]">
                            No available slot for selected date/package. Check opening-hour settings or choose another date.
                        </p>
                    </label>
                </div>

                <label class="mt-3 block text-sm text-[#475569]">
                    Notes
                    <textarea v-model="bookingForm.notes" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></textarea>
                </label>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeBookingModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #0F766E; color: #FFFFFF;"
                        :disabled="saving"
                        @click="submitBookingForm"
                    >
                        {{ saving ? 'Saving...' : (bookingModalMode === 'create' ? 'Create Booking' : 'Save Changes') }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="paymentModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">Confirm Payment - {{ paymentTargetCode }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closePaymentModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="text-sm text-[#475569]">
                        Method
                        <select v-model="paymentForm.method" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="">Select method</option>
                            <option v-for="method in paymentMethods" :key="`pay-method-${method.value}`" :value="method.value">{{ method.label }}</option>
                        </select>
                    </label>

                    <label class="text-sm text-[#475569]">
                        Amount
                        <input v-model="paymentForm.amount" type="number" min="1" step="1000" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569] md:col-span-2">
                        Reference Number
                        <input v-model="paymentForm.reference_no" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>
                </div>

                <label class="mt-3 block text-sm text-[#475569]">
                    Notes
                    <textarea v-model="paymentForm.notes" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></textarea>
                </label>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closePaymentModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #0F766E; color: #FFFFFF;"
                        :disabled="saving"
                        @click="submitPaymentConfirmation"
                    >
                        {{ saving ? 'Saving...' : 'Confirm Payment' }}
                    </button>
                </div>
            </div>
        </div>

        <div v-if="bookingDetailModalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-2xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">Booking Detail - {{ bookingDetail?.booking_code || '-' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeBookingDetailModal">Close</button>
                </div>

                <div class="grid grid-cols-1 gap-2 text-sm text-[#334155] md:grid-cols-2">
                    <p><span class="font-semibold text-[#0F172A]">Customer:</span> {{ bookingDetail?.name || '-' }}</p>
                    <p><span class="font-semibold text-[#0F172A]">Phone:</span> {{ bookingDetail?.customer_phone || '-' }}</p>
                    <p><span class="font-semibold text-[#0F172A]">Package:</span> {{ bookingDetail?.pkg || '-' }}</p>
                    <p><span class="font-semibold text-[#0F172A]">Design:</span> {{ bookingDetail?.design_name || '-' }}</p>
                    <p><span class="font-semibold text-[#0F172A]">Date:</span> {{ bookingDetail?.date || '-' }}</p>
                    <p><span class="font-semibold text-[#0F172A]">Time:</span> {{ bookingDetail?.time || '-' }}</p>
                </div>

                <div class="mt-4 rounded-xl border" style="border-color: #CCFBF1; background: #F8FAFC;">
                    <div class="border-b px-4 py-3" style="border-color: #CCFBF1;">
                        <h4 class="text-sm font-semibold text-[#0F172A]">Add-ons</h4>
                        <p class="text-xs text-[#64748B]">Total item: {{ bookingDetailAddOns.length }}</p>
                    </div>

                    <div v-if="bookingDetailAddOns.length" class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr style="border-bottom: 1px solid #E2E8F0; background: #F0FDFA;">
                                    <th class="px-4 py-2 text-left text-xs uppercase tracking-wider text-[#64748B]">Item</th>
                                    <th class="px-4 py-2 text-right text-xs uppercase tracking-wider text-[#64748B]">Qty</th>
                                    <th class="px-4 py-2 text-right text-xs uppercase tracking-wider text-[#64748B]">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, idx) in bookingDetailAddOns" :key="`booking-detail-addon-${idx}`" style="border-bottom: 1px solid #E2E8F0;">
                                    <td class="px-4 py-2 text-sm text-[#1F2937]">{{ item.label }}</td>
                                    <td class="px-4 py-2 text-right text-sm text-[#475569]">{{ item.qty }}</td>
                                    <td class="px-4 py-2 text-right text-sm font-semibold text-[#1F2937]">{{ formatCurrency(item.line_total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p v-else class="px-4 py-4 text-sm text-[#64748B]">No add-ons applied for this booking.</p>

                    <div class="flex items-center justify-between border-t px-4 py-3 text-sm" style="border-color: #CCFBF1;">
                        <span class="font-semibold text-[#0F172A]">Add-ons Total</span>
                        <span class="font-semibold text-[#0F172A]">{{ formatCurrency(bookingDetailAddOnsTotal) }}</span>
                    </div>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeBookingDetailModal">Close</button>
                </div>
            </div>
        </div>
    </div>
</template>
