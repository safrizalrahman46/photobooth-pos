<script setup>
import { computed, ref } from 'vue';
import PublicBookingNavbar from './PublicBookingNavbar.vue';

const props = defineProps({
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
const digitsOnly = (value) => asString(value).replace(/\D+/g, '');

const customerName = ref(asString(props.oldValues.customer_name));
const customerPhone = ref(digitsOnly(props.oldValues.customer_phone));
const customerEmail = ref(asString(props.oldValues.customer_email));
const notes = ref(asString(props.oldValues.notes));
const termsAccepted = ref(Boolean(props.oldValues.terms_accepted));

const onPhoneInput = (event) => {
    const normalized = digitsOnly(event?.target?.value);
    customerPhone.value = normalized;

    if (event?.target) {
        event.target.value = normalized;
    }
};

const canSubmit = computed(() => {
    return Boolean(
        customerName.value.trim()
        && /^\d+$/.test(customerPhone.value)
        && termsAccepted.value,
    );
});
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" :site="props.site" :navigation="props.navigation" />

        <main class="relative mx-auto w-full max-w-7xl px-4 pb-20 pt-8 sm:px-6">
            <div class="mb-8">
                <h1 class="text-[#1F2937]" style="font-size: 1.75rem; font-weight: 700;">Data Pemesan</h1>
                <p class="mt-1 text-gray-500" style="font-size: 0.875rem;">Isi data pemesan terlebih dahulu sebelum lanjut ke booking sesi foto</p>
            </div>

            <form :action="props.routes.submit" method="post" class="space-y-6">
                <input type="hidden" name="_token" :value="props.csrfToken">

                <div
                    v-if="props.errors.length"
                    class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                >
                    <ul class="space-y-1">
                        <li v-for="(message, index) in props.errors" :key="`server-${index}`">
                            {{ message }}
                        </li>
                    </ul>
                </div>

                <section class="overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                    <div class="border-b border-slate-200 px-6 pb-6 pt-6">
                        <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                            Data Pemesan
                        </h2>
                    </div>

                    <div class="space-y-4 p-4 sm:p-6">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Nama Pemesan</span>
                                <input
                                    v-model="customerName"
                                    name="customer_name"
                                    required
                                    maxlength="120"
                                    type="text"
                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                >
                            </label>

                            <label class="space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Nomor HP</span>
                                <input
                                    v-model="customerPhone"
                                    name="customer_phone"
                                    required
                                    maxlength="30"
                                    type="tel"
                                    inputmode="numeric"
                                    pattern="[0-9]*"
                                    @input="onPhoneInput"
                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                >
                            </label>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <label class="space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Email (opsional)</span>
                                <input
                                    v-model="customerEmail"
                                    name="customer_email"
                                    type="email"
                                    maxlength="120"
                                    class="h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                >
                            </label>

                            <label class="space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Catatan (opsional)</span>
                                <textarea
                                    v-model="notes"
                                    name="notes"
                                    maxlength="1000"
                                    rows="2"
                                    class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                ></textarea>
                            </label>
                        </div>

                        <div class="rounded-xl border border-[#D97706]/20 bg-[#D97706]/5 px-4 py-4">
                            <h3 class="text-sm text-[#1F2937]" style="font-weight: 700;">Syarat & Ketentuan</h3>

                            <ul class="mt-2 space-y-2 text-sm text-[#1F2937]">
                                <li>- Saya bersedia datang minimal 10 menit sebelum sesi dimulai.</li>
                                <li>- Jika terlambat waktu sesi dihitung sesuai jadwal booking.</li>
                                <li>- Booking dianggap sah setelah melakukan pembayaran.</li>
                                <li>- Saya bersedia menjaga properti studio dan bertanggung jawab atas kerusakan akibat kelalaian.</li>
                            </ul>

                            <label class="mt-3 flex items-start gap-3 text-sm text-[#1F2937]">
                                <input
                                    v-model="termsAccepted"
                                    name="terms_accepted"
                                    type="checkbox"
                                    value="1"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-[#2563EB] focus:ring-[#2563EB]"
                                >
                                <span style="font-weight: 500;">Saya sudah membaca dan menyetujui seluruh S&K di atas.</span>
                            </label>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <a
                        :href="props.routes.landing"
                        class="inline-flex h-11 items-center justify-center rounded-xl border border-slate-300 px-5 text-sm text-gray-600 transition hover:bg-slate-200"
                    >
                        Kembali
                    </a>

                    <button
                        type="submit"
                        class="inline-flex h-11 items-center justify-center rounded-xl bg-[#2563EB] px-5 text-sm text-white shadow-md shadow-[#2563EB]/20 transition"
                        :class="canSubmit ? 'hover:bg-[#2563EB]/90' : 'cursor-not-allowed opacity-70'"
                        :disabled="!canSubmit"
                    >
                        Lanjut Booking Sesi Foto
                    </button>
                </div>
            </form>
        </main>
    </div>
</template>
