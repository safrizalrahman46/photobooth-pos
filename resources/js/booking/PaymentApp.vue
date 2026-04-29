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
    csrfToken: {
        type: String,
        required: true,
    },
});

const asString = (value) => (value === null || value === undefined ? '' : String(value));

const manualPaymentEnabled = computed(() => {
    return props.paymentSettings.manual_payment_enabled !== false && props.paymentSettings.manual_payment_enabled !== 0;
});

const qrImageUrl = computed(() => {
    const candidates = [
        props.paymentSettings.qr_image_url,
        props.paymentSettings.qris_image_url,
        props.paymentSettings.qr_url,
    ];

    for (const candidate of candidates) {
        const value = asString(candidate).trim();

        if (value !== '') {
            return value;
        }
    }

    return '';
});

const qrLabel = computed(() => {
    return asString(
        props.paymentSettings.qr_label
        || props.paymentSettings.qr_title
        || 'QR Pembayaran'
    ).trim() || 'QR Pembayaran';
});

const paymentInstructions = computed(() => {
    return asString(
        props.paymentSettings.transfer_instructions
        || props.paymentSettings.instructions
        || 'Scan QR di bawah ini sesuai nominal pilihanmu, lalu unggah bukti pembayaran untuk verifikasi admin.'
    ).trim();
});

const paymentAvailabilityMessage = computed(() => {
    if (!manualPaymentEnabled.value) {
        return 'Metode pembayaran belum tersedia saat ini. Silakan hubungi admin studio.';
    }

    if (qrImageUrl.value === '') {
        return 'Foto QR pembayaran belum diatur. Silakan hubungi admin studio.';
    }

    return '';
});

const defaultPaymentType = 'full';
const paymentType = ref(asString(props.oldValues.payment_type || defaultPaymentType));
const processing = ref(false);
const countdown = ref(600);
const transferProofName = ref('');
const transferProofPreviewUrl = ref('');

let countdownTimer = null;

const packageAccentTokens = ['#2563eb', '#ec4899', '#22c55e', '#f59e0b', '#0ea5e9', '#8b5cf6'];

const selectedAddons = computed(() => {
    if (! Array.isArray(props.bookingPayload?.addons)) {
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

const addonTotal = computed(() => {
    return selectedAddons.value.reduce((total, addon) => total + (addon.price * addon.qty), 0);
});

const totalPrice = computed(() => Number(props.package?.base_price || 0) + addonTotal.value);
const dpAmount = computed(() => Math.round(totalPrice.value * 0.5));
const remainingAfterDp = computed(() => Math.max(totalPrice.value - dpAmount.value, 0));

const addonsPayloadJson = computed(() => JSON.stringify(selectedAddons.value));

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

    if (! value) {
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

    if (! value) {
        return '-';
    }

    return `${value} WIB`;
});

const selectedPaymentLabel = computed(() => {
    return paymentType.value === 'dp50' ? 'DP 50%' : 'Full Lunas';
});

const payableAmount = computed(() => {
    return paymentType.value === 'dp50' ? dpAmount.value : totalPrice.value;
});

const submitLabel = computed(() => {
    if (!manualPaymentEnabled.value) {
        return 'Pembayaran Belum Tersedia';
    }

    return paymentType.value === 'dp50'
        ? 'Kirim Bukti DP 50%'
        : 'Kirim Bukti Full Lunas';
});

const hasTransferProof = computed(() => transferProofName.value !== '');

const canSubmit = computed(() => {
    return manualPaymentEnabled.value
        && qrImageUrl.value !== ''
        && ['full', 'dp50'].includes(paymentType.value)
        && hasTransferProof.value;
});

if (! ['full', 'dp50'].includes(paymentType.value)) {
    paymentType.value = defaultPaymentType;
}

const countdownLabel = computed(() => {
    const min = String(mins.value).padStart(2, '0');
    const sec = String(secs.value).padStart(2, '0');

    return `${min}:${sec} tersisa`;
});

const clearTransferProofPreview = () => {
    if (transferProofPreviewUrl.value.startsWith('blob:')) {
        URL.revokeObjectURL(transferProofPreviewUrl.value);
    }

    transferProofPreviewUrl.value = '';
};

const handleTransferProofChange = (event) => {
    const file = event?.target?.files?.[0] ?? null;

    clearTransferProofPreview();

    if (! file) {
        transferProofName.value = '';
        return;
    }

    transferProofName.value = file.name;

    if (String(file.type || '').startsWith('image/')) {
        transferProofPreviewUrl.value = URL.createObjectURL(file);
    }
};

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
    clearTransferProofPreview();

    if (countdownTimer) {
        window.clearInterval(countdownTimer);
        countdownTimer = null;
    }
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" :site="props.site" />

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
                        <div v-if="props.package" class="flex justify-between text-sm">
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

                            <div class="flex justify-between border-t border-gray-100 pt-2 text-sm">
                                <span class="text-gray-600" style="font-weight: 500;">Total Booking</span>
                                <span class="text-[#1F2937]">{{ formatRupiah(totalPrice) }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600" style="font-weight: 500;">{{ selectedPaymentLabel }}</span>
                                <span class="text-[#1F2937]" style="font-size: 1.25rem; font-weight: 700;">
                                    {{ formatRupiah(payableAmount) }}
                                </span>
                            </div>

                            <div v-if="paymentType === 'dp50'" class="flex justify-between text-sm">
                                <span class="text-gray-500">Sisa Pelunasan</span>
                                <span class="text-[#1F2937]">{{ formatRupiah(remainingAfterDp) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="manualPaymentEnabled" class="mb-6 grid grid-cols-2 gap-3">
                    <button
                        type="button"
                        class="rounded-xl border-2 p-4 text-left transition-all duration-200"
                        :class="paymentType === 'full'
                            ? 'border-[#2563EB] bg-[#2563EB]/9 shadow-sm'
                            : 'border-slate-300 bg-white hover:border-slate-400'"
                        @click="paymentType = 'full'"
                    >
                        <p class="text-sm" :class="paymentType === 'full' ? 'text-[#2563EB]' : 'text-[#1F2937]'" style="font-weight: 600;">Full Lunas</p>
                        <p class="mt-0.5 text-xs text-gray-500">{{ qrLabel }} - {{ formatRupiah(totalPrice) }}</p>
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
                        <p class="mt-0.5 text-xs text-gray-500">{{ qrLabel }} - {{ formatRupiah(dpAmount) }}</p>
                    </button>
                </div>

                <div v-if="paymentAvailabilityMessage" class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    {{ paymentAvailabilityMessage }}
                </div>

                <div v-if="manualPaymentEnabled && qrImageUrl" class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                    <div class="border-b border-slate-300 px-6 pb-6 pt-6">
                        <h3 class="flex items-center gap-2 text-[#1F2937]" style="font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <path d="M7 7h3v3H7z" />
                                <path d="M14 7h3v3h-3z" />
                                <path d="M7 14h3v3H7z" />
                                <path d="M13 13h1" />
                                <path d="M16 13h1" />
                                <path d="M13 16h1" />
                                <path d="M16 16h1" />
                            </svg>
                            {{ selectedPaymentLabel }} via {{ qrLabel }}
                        </h3>
                    </div>

                    <div class="flex flex-col items-center p-6">
                        <img
                            :src="qrImageUrl"
                            :alt="qrLabel"
                            class="mb-4 h-64 w-64 rounded-2xl border border-gray-100 bg-gray-50 object-contain p-2"
                        >
                        <p class="mb-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 700;">{{ formatRupiah(payableAmount) }}</p>
                        <p v-if="paymentType === 'dp50'" class="mb-2 text-center text-xs text-gray-500">
                            Sisa pelunasan setelah DP: {{ formatRupiah(remainingAfterDp) }}
                        </p>
                        <p class="max-w-sm text-center text-sm text-gray-500">
                            {{ paymentInstructions }}
                        </p>
                    </div>
                </div>

                <form
                    v-if="manualPaymentEnabled && qrImageUrl"
                    :action="props.routes.store"
                    method="post"
                    enctype="multipart/form-data"
                    @submit="handleSubmit"
                >
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

                    <div class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                        <div class="border-b border-slate-300 px-6 pb-6 pt-6">
                            <h3 class="flex items-center gap-2 text-[#1F2937]" style="font-weight: 600;">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                    <polyline points="17 8 12 3 7 8" />
                                    <line x1="12" y1="3" x2="12" y2="15" />
                                </svg>
                                Upload Bukti Pembayaran
                            </h3>
                        </div>

                        <div class="space-y-4 p-6">
                            <label class="block text-sm text-[#475569]">
                                Foto bukti pembayaran
                                <input
                                    type="file"
                                    name="transfer_proof"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    required
                                    class="mt-2 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-[#334155] file:mr-3 file:rounded-lg file:border-0 file:bg-[#2563EB]/10 file:px-3 file:py-2 file:text-sm file:font-medium file:text-[#2563EB]"
                                    @change="handleTransferProofChange"
                                >
                            </label>

                            <p class="text-xs text-gray-500">
                                Format yang didukung: JPG, PNG, WEBP. Unggah screenshot atau foto bukti transfer yang jelas.
                            </p>

                            <div v-if="transferProofName" class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <p class="text-sm text-[#1F2937]" style="font-weight: 600;">File dipilih</p>
                                <p class="mt-1 text-sm text-gray-500">{{ transferProofName }}</p>

                                <img
                                    v-if="transferProofPreviewUrl"
                                    :src="transferProofPreviewUrl"
                                    alt="Preview bukti pembayaran"
                                    class="mt-3 max-h-64 w-full rounded-xl border border-slate-200 object-contain bg-white p-2"
                                >
                            </div>
                        </div>
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

                <a
                    v-else
                    :href="props.routes.back"
                    class="inline-flex h-12 w-full items-center justify-center rounded-xl border border-slate-300 bg-white text-sm font-medium text-[#334155] transition hover:bg-slate-50"
                >
                    Kembali Ubah Booking
                </a>

                <div class="h-20 md:hidden" />
            </div>
        </div>
    </div>
</template>
