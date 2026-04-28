<script setup>
import { onMounted, ref } from 'vue';
import PublicBookingNavbar from './PublicBookingNavbar.vue';

const props = defineProps({
    booking: {
        type: Object,
        required: true,
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
});

const isReady = ref(false);

const formatRupiah = (value) => {
    const amount = Number(value || 0);

    return `Rp ${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number.isNaN(amount) ? 0 : amount)}`;
};

const paymentBadgeClass = (status) => {
    if (status === 'Lunas') {
        return 'bg-[#22C55E]/10 text-[#22C55E]';
    }

    if (status === 'DP') {
        return 'bg-[#F59E0B]/10 text-[#F59E0B]';
    }

    return 'bg-gray-100 text-gray-500';
};

const stageStyle = (delay) => ({
    transitionDelay: `${delay}ms`,
});

const hasContinuePayment = () => String(props.booking?.continue_payment_url || '').trim() !== '';

onMounted(() => {
    window.requestAnimationFrame(() => {
        isReady.value = true;
    });
});
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" :site="props.site" :navigation="props.navigation" />

        <div class="relative min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute right-10 top-10 h-48 w-48 rounded-full bg-[#2563EB]/5"></div>
                <div class="absolute bottom-20 left-10 h-32 w-32 rotate-12 rounded-xl bg-[#60A5FA]/5"></div>
            </div>

            <main class="relative mx-auto max-w-lg px-4 py-8">
                <div
                    class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm transition-all duration-500 ease-out"
                    :class="isReady ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'"
                >
                    <div class="flex flex-col items-center p-6 text-center">
                        <div class="mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-[#22C55E]/10" :class="isReady ? 'success-pulse' : ''">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-[#22C55E]" :class="isReady ? 'success-pop' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </div>

                        <h1 class="text-[#1F2937]" style="font-size: 1.5rem; font-weight: 700;">Konfirmasi Booking Berhasil</h1>
                        <p class="mt-1 max-w-sm text-sm text-gray-500">Detail booking kamu sudah tercatat. Simpan kode booking berikut untuk proses check-in.</p>

                        <div
                            v-if="props.booking.notice"
                            class="mt-4 w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-left text-sm text-amber-800"
                        >
                            {{ props.booking.notice }}
                        </div>

                        <div class="mt-4 w-full rounded-xl border border-slate-200 bg-gray-50 px-4 py-3 text-left">
                            <p class="text-sm text-[#1F2937]" style="font-weight: 700;">Status Pembayaran</p>
                            <p class="mt-1 text-sm text-gray-600">{{ props.booking.payment_message || '-' }}</p>
                            <p v-if="props.booking.payment_reference" class="mt-1 text-xs text-gray-500">
                                Referensi transfer: {{ props.booking.payment_reference }}
                            </p>
                        </div>

                        <div class="mt-4 w-full rounded-xl border border-slate-200 bg-gray-50 px-4 py-3">
                            <p class="text-xs uppercase tracking-[0.14em] text-gray-500">Kode Booking</p>
                            <p class="mt-1 text-[#1F2937]" style="font-size: 1.5rem; font-weight: 700; letter-spacing: 0.08em;">{{ props.booking.booking_code }}</p>
                        </div>
                    </div>
                </div>

                <div
                    class="mb-6 overflow-hidden rounded-xl border-0 bg-white shadow-sm transition-all duration-500 ease-out"
                    :class="isReady ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'"
                    :style="stageStyle(120)"
                >
                    <div class="border-b border-slate-300 px-6 pb-6 pt-6">
                        <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="2" y="5" width="20" height="14" rx="2" />
                                <line x1="2" y1="10" x2="22" y2="10" />
                            </svg>
                            Detail Booking Confirm
                        </h2>
                    </div>

                    <div class="space-y-3 p-6">
                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Nama Pemesan</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.customer_name }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Nomor HP</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.customer_phone }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Cabang</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.branch_name }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Paket</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.package_name }}</span>
                        </div>

                        <div v-if="props.booking.design_name" class="flex justify-between text-sm">
                            <span class="text-gray-500">Tema Desain</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.design_name }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Tanggal</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.date_text }}</span>
                        </div>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-500">Waktu</span>
                            <span class="text-[#1F2937]" style="font-weight: 500;">{{ props.booking.time_text }}</span>
                        </div>

                        <div class="space-y-1.5 border-t border-dashed border-slate-300 pt-3">
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Total Booking</span>
                                <span class="text-[#1F2937]" style="font-weight: 600;">{{ formatRupiah(props.booking.total_amount) }}</span>
                            </div>

                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Dibayar</span>
                                <span class="text-[#1F2937]" style="font-weight: 600;">{{ formatRupiah(props.booking.paid_amount) }}</span>
                            </div>

                            <div class="flex justify-between border-t border-gray-100 pt-2">
                                <span class="text-gray-600" style="font-weight: 500;">Status Pembayaran</span>
                                <span class="rounded-full px-2 py-0.5 text-xs" :class="paymentBadgeClass(props.booking.payment_status)" style="font-weight: 600;">
                                    {{ props.booking.payment_status }}
                                </span>
                            </div>
                        </div>

                        <div v-if="props.booking.notes" class="rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-600">
                            <p class="text-xs uppercase tracking-[0.12em] text-gray-400">Catatan</p>
                            <p class="mt-1">{{ props.booking.notes }}</p>
                        </div>
                    </div>
                </div>

                <div
                    class="mb-6 flex items-center gap-2 rounded-lg bg-[#F59E0B]/10 px-3 py-2 text-xs text-[#F59E0B] transition-all duration-500 ease-out"
                    :class="isReady ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'"
                    :style="stageStyle(210)"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 6v6l4 2" />
                    </svg>
                    Harap datang minimal 10 menit sebelum jadwal sesi dimulai.
                </div>

                <div
                    class="grid gap-3 transition-all duration-500 ease-out"
                    :class="isReady ? 'translate-y-0 opacity-100' : 'translate-y-3 opacity-0'"
                    :style="stageStyle(300)"
                >
                    <a
                        v-if="hasContinuePayment()"
                        :href="props.booking.continue_payment_url"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-[#2563EB] px-5 text-sm text-white shadow-md shadow-[#2563EB]/20 transition hover:bg-[#2563EB]/90"
                    >
                        Lanjutkan Pembayaran
                    </a>
                    <a :href="props.routes.booking" class="inline-flex h-11 items-center justify-center rounded-xl bg-[#2563EB] px-5 text-sm text-white shadow-md shadow-[#2563EB]/20 transition hover:bg-[#2563EB]/90">
                        Buat Booking Lagi
                    </a>
                    <a :href="props.routes.queueBoard" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm text-gray-700 transition hover:bg-slate-100">
                        Lihat Queue Board
                    </a>
                    <a :href="props.routes.landing" class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 bg-white px-5 text-sm text-gray-700 transition hover:bg-slate-100">
                        Kembali ke Beranda
                    </a>
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.success-pop {
    animation: success-pop 0.52s ease-out;
}

.success-pulse {
    animation: success-pulse 1s ease-out;
}

@keyframes success-pop {
    0% {
        opacity: 0;
        transform: scale(0.72);
    }

    72% {
        opacity: 1;
        transform: scale(1.08);
    }

    100% {
        transform: scale(1);
    }
}

@keyframes success-pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.28);
    }

    100% {
        box-shadow: 0 0 0 14px rgba(34, 197, 94, 0);
    }
}
</style>
