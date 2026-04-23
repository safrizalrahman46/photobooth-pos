<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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
    paymentSettings: {
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
    site: {
        type: Object,
        default: () => ({}),
    },
    navigation: {
        type: Array,
        default: () => [],
    },
    csrfToken: {
        type: String,
        required: true,
    },
});

const asString = (value) => (value === null || value === undefined ? '' : String(value));
const isDisabled = (value) => value === false || value === 0 || value === '0';

const onlinePaymentEnabled = computed(() => !isDisabled(props.paymentSettings.online_payment_enabled));
const fullPaymentEnabled = computed(() => !isDisabled(props.paymentSettings.full_payment_enabled));
const dp50PaymentEnabled = computed(() => !isDisabled(props.paymentSettings.dp50_enabled));
const transferReference = ref(asString(props.oldValues.transfer_reference || ''));

const paymentOptions = computed(() => {
    const options = [];

    if (fullPaymentEnabled.value) {
        options.push('full');
    }

    if (dp50PaymentEnabled.value) {
        options.push('dp50');
    }

    return options;
});

const defaultPaymentType = computed(() => {
    const candidate = asString(props.oldValues.payment_type);

    if (paymentOptions.value.includes(candidate)) {
        return candidate;
    }

    return paymentOptions.value[0] || 'full';
});

const paymentType = ref(defaultPaymentType.value);
const processing = ref(false);
const countdown = ref(600);

watch(paymentOptions, (options) => {
    if (!options.includes(paymentType.value)) {
        paymentType.value = options[0] || 'full';
    }
});

let countdownTimer = null;

const packageAccentTokens = ['#2563eb', '#ec4899', '#22c55e', '#f59e0b', '#0ea5e9', '#8b5cf6'];

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

const addonsPayloadJson = computed(() => JSON.stringify(selectedAddons.value));

const addonTotal = computed(() => {
    return selectedAddons.value.reduce((total, addon) => total + (addon.price * addon.qty), 0);
});

const totalPrice = computed(() => Number(props.package?.base_price || 0) + addonTotal.value);
const payNowAmount = computed(() => {
    if (paymentType.value === 'dp50') {
        return Math.round(totalPrice.value * 0.5);
    }

    return totalPrice.value;
});

const remainingAmount = computed(() => Math.max(totalPrice.value - payNowAmount.value, 0));

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

const submitLabel = computed(() => {
    return 'Kirim Booking & Bukti Transfer';
});

const payNowLabel = computed(() => {
    return paymentType.value === 'dp50' ? 'Bayar Sekarang (DP 50%)' : 'Bayar Sekarang';
});

const paymentHint = computed(() => {
    return 'Transfer ke QRIS BRI personal owner, lalu upload bukti transfer di bawah ini untuk verifikasi admin.';
});

const canSubmit = computed(() => paymentOptions.value.includes(paymentType.value));

const countdownLabel = computed(() => {
    const min = String(mins.value).padStart(2, '0');
    const sec = String(secs.value).padStart(2, '0');
    return `${min}:${sec} tersisa`;
});

const handleSubmit = (event) => {
    if (!canSubmit.value) {
        event?.preventDefault?.();
        return;
    }

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
        <PublicBookingNavbar :routes="props.routes" :site="props.site" :navigation="props.navigation" />

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

                <h1 class="mb-2 text-[#1F2937]" style="font-size: 1.5rem; font-weight: 700;">Konfirmasi Pembayaran QR</h1>
                <p class="mb-6 text-sm text-gray-500">Pilih skema pembayaran: lunas atau DP 50%</p>

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
                        <p class="text-sm text-gray-500">Selesaikan proses booking sebelum waktu habis</p>
                    </div>
                </div>

                <div class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                    <div class="border-b border-slate-300 px-6 pb-6 pt-6">
                        <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg>
                            Ringkasan Booking
                        </h2>
                    </div>

                    <div class="space-y-3 p-6">
                        <div class="flex justify-between text-sm" v-if="props.package">
                            <span class="flex items-center gap-1.5 text-gray-500">Paket</span>
                            <span class="rounded-full px-2 py-0.5 text-xs text-white" :style="{ backgroundColor: packageColor, fontWeight: 500 }">
                                {{ props.package.name }}
                            </span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tanggal</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ displayDate }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Waktu</span>
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

                            <div class="flex justify-between border-t border-gray-100 pt-2 text-sm">
                                <span class="text-gray-600" style="font-weight: 500;">Total Tagihan</span>
                                <span class="text-[#1F2937]" style="font-weight: 700;">{{ formatRupiah(totalPrice) }}</span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-600">{{ payNowLabel }}</span>
                                <span class="text-[#1F2937]" style="font-weight: 700;">{{ formatRupiah(payNowAmount) }}</span>
                            </div>

                            <div v-if="paymentType === 'dp50'" class="flex justify-between text-sm">
                                <span class="text-gray-500">Sisa Pelunasan</span>
                                <span class="text-[#1F2937]">{{ formatRupiah(remainingAmount) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="paymentOptions.length" class="mb-6 grid gap-3" :class="paymentOptions.length > 1 ? 'grid-cols-2' : 'grid-cols-1'">
                    <button
                        v-if="fullPaymentEnabled"
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all duration-200"
                        :class="paymentType === 'full'
                            ? 'border-[#2563EB] bg-[#2563EB]/9 shadow-sm'
                            : 'border-slate-300 bg-white hover:border-slate-400'"
                        @click="paymentType = 'full'"
                    >
                        <p class="text-sm" :class="paymentType === 'full' ? 'text-[#2563EB]' : 'text-[#1F2937]'" style="font-weight: 600;">QR Lunas</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ formatRupiah(totalPrice) }}</p>
                    </button>

                    <button
                        v-if="dp50PaymentEnabled"
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all duration-200"
                        :class="paymentType === 'dp50'
                            ? 'border-[#2563EB] bg-[#2563EB]/5 shadow-sm'
                            : 'border-slate-300 bg-white hover:border-slate-400'"
                        @click="paymentType = 'dp50'"
                    >
                        <p class="text-sm" :class="paymentType === 'dp50' ? 'text-[#2563EB]' : 'text-[#1F2937]'" style="font-weight: 600;">QR DP 50%</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ formatRupiah(Math.round(totalPrice * 0.5)) }}</p>
                    </button>
                </div>

                <div v-else class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    Metode pembayaran tidak tersedia saat ini. Silakan hubungi admin studio.
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
                            Pembayaran QR
                        </h3>
                    </div>

                    <div class="flex flex-col items-center p-6 text-center">
                        <div class="mb-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 700;">{{ formatRupiah(payNowAmount) }}</div>
                        <p class="max-w-sm text-sm text-gray-500">{{ paymentHint }}</p>
                    </div>
                </div>

                <form :action="props.routes.store" method="post" enctype="multipart/form-data" @submit="handleSubmit">
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
                    <input type="hidden" name="payment_type" :value="paymentType">
                    <input type="hidden" name="addons_payload" :value="addonsPayloadJson">

                    <div class="mb-4 rounded-xl border border-slate-200 bg-white p-4">
                        <label class="mb-2 block text-sm text-[#1F2937]" style="font-weight: 600;">Nomor Referensi Transfer (opsional)</label>
                        <input
                            v-model="transferReference"
                            type="text"
                            name="transfer_reference"
                            placeholder="Contoh: 1234567890"
                            class="mb-3 h-11 w-full rounded-lg border border-slate-300 px-3 text-sm text-[#1F2937] outline-none focus:border-[#2563EB]"
                        >

                        <label class="mb-2 block text-sm text-[#1F2937]" style="font-weight: 600;">Upload Bukti Transfer <span class="text-red-500">*</span></label>
                        <input
                            type="file"
                            name="transfer_proof"
                            required
                            accept=".jpg,.jpeg,.png,.webp,.pdf"
                            class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-[#1F2937] file:mr-3 file:rounded-md file:border-0 file:bg-[#2563EB] file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                        >
                        <p class="mt-2 text-xs text-slate-500">Format: JPG, PNG, WEBP, atau PDF (maks. 5MB).</p>
                    </div>

                    <button
                        type="submit"
                        class="h-12 w-full rounded-xl bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/20 transition hover:bg-[#2563EB]/90"
                        :disabled="processing || !canSubmit"
                        :class="processing || !canSubmit ? 'cursor-not-allowed opacity-80' : ''"
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
