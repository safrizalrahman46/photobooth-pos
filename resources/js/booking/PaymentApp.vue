<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import PublicBookingNavbar from './PublicBookingNavbar.vue';

const props = defineProps({
    bookingPayload: {
        type: Object,
        required: true,
    },
    branch: {
        type: Object,
        default: () => null,
    },
    package: {
        type: Object,
        default: () => null,
    },
    designCatalog: {
        type: Object,
        default: () => null,
    },
    oldValues: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
    },
    csrfToken: {
        type: String,
        required: true,
    },
});

const asString = (value) => (value === null || value === undefined ? '' : String(value));

const normalizePaymentType = (value) => {
    const normalized = asString(value).toLowerCase();

    if (normalized === 'full' || normalized === 'dp50') {
        return normalized;
    }

    return 'dp50';
};

const paymentType = ref(normalizePaymentType(props.oldValues.payment_type || 'dp50'));
const processing = ref(false);
const countdown = ref(600);

let countdownTimer = null;

const packageAccentTokens = ['#2563eb', '#ec4899', '#22c55e', '#f59e0b', '#0ea5e9', '#8b5cf6'];

const selectedAddOnsForSubmit = computed(() => {
    const source = Array.isArray(props.bookingPayload?.add_ons) ? props.bookingPayload.add_ons : [];

    return source
        .map((item) => ({
            add_on_id: Number(item?.add_on_id || 0),
            qty: Number(item?.qty || 0),
        }))
        .filter((item) => item.add_on_id > 0 && item.qty > 0);
});

const selectedAddons = computed(() => {
    if (!Array.isArray(props.bookingPayload?.addons)) {
        return [];
    }

    return props.bookingPayload.addons
        .filter((item) => item && item.label)
        .map((item) => ({
            id: asString(item.id || item.label),
            label: asString(item.label),
            qty: Number(item.qty || 1),
            price: Number(item.price || 0),
        }));
});

const addOnTotal = computed(() => {
    return selectedAddons.value.reduce((sum, item) => sum + (item.price * item.qty), 0);
});

const totalPrice = computed(() => {
    const payloadTotal = Number(props.bookingPayload?.total_amount || 0);

    if (payloadTotal > 0) {
        return payloadTotal;
    }

    return Number(props.package?.base_price || 0) + addOnTotal.value;
});

const dpAmount = computed(() => {
    const total = Number(totalPrice.value || 0);

    return Math.round((total * 0.5) * 100) / 100;
});

const amountToPay = computed(() => {
    return paymentType.value === 'dp50' ? dpAmount.value : totalPrice.value;
});

const remainingAfterCurrentPayment = computed(() => {
    return Math.max(Number(totalPrice.value || 0) - Number(amountToPay.value || 0), 0);
});

const packageColor = computed(() => {
    if (props.package?.color) {
        return props.package.color;
    }

    const source = asString(props.package?.name || props.package?.id || 'PK');
    const sum = source.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
    return packageAccentTokens[sum % packageAccentTokens.length];
});

const mins = computed(() => Math.floor(countdown.value / 60));
const secs = computed(() => countdown.value % 60);
const isCountdownDanger = computed(() => countdown.value < 120);

const formatRupiah = (value) => {
    const amount = Number(value || 0);
    return `Rp ${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number.isNaN(amount) ? 0 : amount)}`;
};

const displayDate = computed(() => {
    const value = asString(props.bookingPayload.booking_date);

    if (!value) {
        return '-';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(date);
});

const displayTime = computed(() => {
    const value = asString(props.bookingPayload.booking_time);

    if (!value) {
        return '-';
    }

    return `${value} WIB`;
});

const submitLabel = computed(() => 'Konfirmasi Pembayaran');

const countdownLabel = computed(() => {
    const min = String(mins.value).padStart(2, '0');
    const sec = String(secs.value).padStart(2, '0');
    return `${min}:${sec} tersisa`;
});

const handleSubmit = () => {
    processing.value = true;
};

onMounted(() => {
    countdownTimer = window.setInterval(() => {
        if (countdown.value <= 0) {
            window.clearInterval(countdownTimer);
            countdownTimer = null;
            return;
        }

        countdown.value -= 1;
    }, 1000);
});

onBeforeUnmount(() => {
    if (countdownTimer) {
        window.clearInterval(countdownTimer);
        countdownTimer = null;
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" />

        <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute top-10 right-10 h-48 w-48 rounded-full bg-[#2563EB]/5" />
                <div class="absolute bottom-20 left-10 h-32 w-32 rotate-12 rounded-xl bg-[#60A5FA]/5" />
            </div>

            <div class="relative mx-auto max-w-lg px-4 py-8">
                <a
                    :href="props.routes.back"
                    class="mb-6 inline-flex items-center gap-1.5 text-sm text-gray-500 transition-colors hover:text-[#2563EB]"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 12H5" />
                        <path d="M12 19l-7-7 7-7" />
                    </svg>
                    Kembali
                </a>

                <h1 class="mb-2 text-[#1F2937]" style="font-size: 1.5rem; font-weight: 700;">Selesaikan Pembayaran</h1>
                <p class="mb-6 text-sm text-gray-500">Lakukan pembayaran untuk konfirmasi booking</p>

                <div v-if="props.errors.length" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <ul class="space-y-1">
                        <li v-for="(message, index) in props.errors" :key="`server-${index}`">
                            {{ message }}
                        </li>
                    </ul>
                </div>

                <div
                    class="mb-6 flex items-center gap-3 rounded-xl p-4"
                    :class="isCountdownDanger
                        ? 'border border-[#EF4444]/20 bg-[#EF4444]/10'
                        : 'border border-[#F59E0B]/20 bg-[#F59E0B]/10'"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" :class="isCountdownDanger ? 'text-[#EF4444]' : 'text-[#F59E0B]'" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                    <div>
                        <p class="text-sm" :class="isCountdownDanger ? 'text-[#EF4444]' : 'text-[#F59E0B]'" style="font-weight: 600;">
                            {{ countdownLabel }}
                        </p>
                        <p class="text-sm text-gray-500">Selesaikan sebelum waktu habis</p>
                    </div>
                </div>

                <div class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                    <div class="border-b border-slate-300 px-6 pb-6 pt-6">
                        <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg>
                            Ringkasan Pembayaran
                        </h2>
                    </div>

                    <div class="space-y-3 p-6">
                        <div class="flex justify-between text-sm" v-if="props.package">
                            <span class="flex items-center gap-1.5 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8l7-5h8l7 5z" />
                                    <circle cx="12" cy="13" r="4" />
                                </svg>
                                Paket
                            </span>
                            <span class="rounded-full px-2 py-0.5 text-xs text-white" :style="{ backgroundColor: packageColor, fontWeight: 500 }">
                                {{ props.package.name }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="flex items-center gap-1.5 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                    <line x1="16" y1="2" x2="16" y2="6" />
                                    <line x1="8" y1="2" x2="8" y2="6" />
                                    <line x1="3" y1="10" x2="21" y2="10" />
                                </svg>
                                Tanggal
                            </span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ displayDate }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="flex items-center gap-1.5 text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="12" cy="12" r="10" />
                                    <path d="M12 6v6l4 2" />
                                </svg>
                                Waktu
                            </span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ displayTime }}</span>
                        </div>

                        <div class="space-y-1.5 border-t border-dashed border-slate-300 pt-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Paket {{ props.package?.name || '-' }}</span>
                                <span class="text-[#1F2937]">{{ formatRupiah(props.package?.base_price || 0) }}</span>
                            </div>

                            <div
                                v-for="addon in selectedAddons"
                                :key="addon.id"
                                class="flex justify-between text-sm"
                            >
                                <span class="text-gray-500">{{ addon.label }} x{{ addon.qty }}</span>
                                <span class="text-[#1F2937]">{{ formatRupiah(addon.price * addon.qty) }}</span>
                            </div>

                            <div class="flex justify-between border-t border-gray-100 pt-2">
                                <span class="text-gray-600" style="font-weight: 500;">
                                    {{ paymentType === 'dp50' ? 'DP 50%' : 'Total' }}
                                </span>
                                <span class="text-[#1F2937]" style="font-size: 1.25rem; font-weight: 700;">
                                    {{ formatRupiah(amountToPay) }}
                                </span>
                            </div>

                            <div v-if="paymentType === 'dp50'" class="flex justify-between text-xs text-gray-500">
                                <span>Sisa Pelunasan di Studio</span>
                                <span>{{ formatRupiah(remainingAfterCurrentPayment) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-6 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all duration-200"
                        :class="paymentType === 'full'
                            ? 'border-[#2563EB] bg-[#2563EB]/9 shadow-sm'
                            : 'border-slate-300 bg-white hover:border-slate-400'"
                        @click="paymentType = 'full'"
                    >
                        <p class="text-sm" :class="paymentType === 'full' ? 'text-[#2563EB]' : 'text-[#1F2937]'" style="font-weight: 600;">Bayar Penuh</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ formatRupiah(totalPrice) }}</p>
                    </button>

                    <button
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all duration-200"
                        :class="paymentType === 'dp50'
                            ? 'border-[#2563EB] bg-[#2563EB]/5 shadow-sm'
                            : 'border-slate-300 bg-white hover:border-slate-400'"
                        @click="paymentType = 'dp50'"
                    >
                        <p class="text-sm" :class="paymentType === 'dp50' ? 'text-[#2563EB]' : 'text-[#1F2937]'" style="font-weight: 600;">DP 50%</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ formatRupiah(dpAmount) }} dibayar sekarang</p>
                    </button>
                </div>

                <div class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                    <div class="border-b border-slate-300 px-6 pb-6 pt-6">
                        <h3 class="flex items-center gap-2 text-[#1F2937]" style="font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="5" height="5" />
                                <rect x="16" y="3" width="5" height="5" />
                                <rect x="3" y="16" width="5" height="5" />
                                <path d="M21 16h-3v3" />
                                <path d="M21 21h-3" />
                                <path d="M14 14h1" />
                                <path d="M10 3h4" />
                                <path d="M10 7h4" />
                                <path d="M3 10h4" />
                                <path d="M7 14h4" />
                                <path d="M10 18h1" />
                            </svg>
                            Scan QRIS untuk Bayar
                        </h3>
                    </div>

                    <div class="flex flex-col items-center p-6">
                        <div class="mb-4 flex h-52 w-52 items-center justify-center rounded-2xl border border-gray-100 bg-gray-50">
                            <div class="grid grid-cols-5 gap-1">
                                <div
                                    v-for="index in 25"
                                    :key="`qr-grid-${index}`"
                                    class="h-8 w-8 rounded-sm"
                                    :class="[1, 2, 3, 5, 6, 7, 9, 11, 15, 17, 19, 20, 21, 23, 24, 25].includes(index)
                                        ? 'bg-[#1F2937]'
                                        : 'bg-white'"
                                />
                            </div>
                        </div>
                        <p class="mb-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 700;">{{ formatRupiah(amountToPay) }}</p>
                        <p v-if="paymentType === 'dp50'" class="mb-2 text-xs text-gray-500">Sisa pelunasan: {{ formatRupiah(remainingAfterCurrentPayment) }}</p>
                        <div class="flex items-center gap-1.5 text-xs text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 13c0 5-3.5 7.5-8 9-4.5-1.5-8-4-8-9V6l8-4 8 4z" />
                                <path d="M9 12l2 2 4-4" />
                            </svg>
                            Pembayaran aman via QRIS
                        </div>
                    </div>
                </div>

                <form :action="props.routes.store" method="post" @submit="handleSubmit">
                    <input type="hidden" name="_token" :value="props.csrfToken">
                    <input type="hidden" name="branch_id" :value="props.bookingPayload.branch_id">
                    <input type="hidden" name="package_id" :value="props.bookingPayload.package_id">
                    <input type="hidden" name="design_catalog_id" :value="props.bookingPayload.design_catalog_id || ''">
                    <input type="hidden" name="booking_date" :value="props.bookingPayload.booking_date">
                    <input type="hidden" name="booking_time" :value="props.bookingPayload.booking_time">
                    <input type="hidden" name="customer_name" :value="props.bookingPayload.customer_name">
                    <input type="hidden" name="customer_phone" :value="props.bookingPayload.customer_phone">
                    <input type="hidden" name="customer_email" :value="props.bookingPayload.customer_email || ''">
                    <input type="hidden" name="notes" :value="props.bookingPayload.notes || ''">
                    <template v-for="(addon, index) in selectedAddOnsForSubmit" :key="`submit-payment-addon-${addon.add_on_id}`">
                        <input type="hidden" :name="`add_ons[${index}][add_on_id]`" :value="addon.add_on_id">
                        <input type="hidden" :name="`add_ons[${index}][qty]`" :value="addon.qty">
                    </template>
                    <input type="hidden" name="payment_type" :value="paymentType">

                    <button
                        type="submit"
                        class="h-12 w-full rounded-xl bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/20 transition hover:bg-[#2563EB]/90"
                        :disabled="processing"
                        :class="processing ? 'cursor-not-allowed opacity-80' : ''"
                    >
                        <span v-if="processing" class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                            Memproses...
                        </span>
                        <span v-else>{{ submitLabel }}</span>
                    </button>
                </form>

                <div class="h-20 md:hidden" />
            </div>
        </div>
    </div>
</template>
