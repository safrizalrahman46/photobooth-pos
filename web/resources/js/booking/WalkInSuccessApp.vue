<script setup>
import PublicBookingNavbar from './PublicBookingNavbar.vue';

const props = defineProps({
    request: {
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
});

const formatRupiah = (value) => {
    const amount = Number(value || 0);

    return `Rp ${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number.isNaN(amount) ? 0 : amount)}`;
};

const formatExpiry = (value) => {
    if (!value) {
        return '-';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: '2-digit',
        month: 'short',
        hour: '2-digit',
        minute: '2-digit',
    }).format(date);
};
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" :site="props.site" />

        <main class="mx-auto max-w-xl px-4 py-8">
            <section class="overflow-hidden rounded-2xl bg-white shadow-sm">
                <div class="bg-[#2563EB] px-6 py-6 text-white">
                    <p class="text-xs uppercase tracking-[0.2em] text-white/75" style="font-weight: 700;">Kode Self Walk-in</p>
                    <h1 class="mt-2 text-3xl" style="font-weight: 900; letter-spacing: 0.08em;">{{ props.request.request_code }}</h1>
                    <p class="mt-2 text-sm text-white/80">Tunjukkan kode ini ke kasir untuk pembayaran tunai.</p>
                </div>

                <div class="space-y-4 p-6">
                    <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Request ini belum masuk antrean. Antrean dibuat otomatis setelah kasir mengonfirmasi pembayaran.
                    </div>

                    <div class="grid gap-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Nama</span>
                            <span class="text-right text-[#1F2937]" style="font-weight: 600;">{{ props.request.customer_name }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">No HP</span>
                            <span class="text-right text-[#1F2937]" style="font-weight: 600;">{{ props.request.customer_phone }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Cabang</span>
                            <span class="text-right text-[#1F2937]" style="font-weight: 600;">{{ props.request.branch_name }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Paket</span>
                            <span class="text-right text-[#1F2937]" style="font-weight: 600;">{{ props.request.package_name }}</span>
                        </div>
                        <div v-for="item in props.request.add_ons" :key="item.id || item.add_on_id" class="flex justify-between gap-4">
                            <span class="text-gray-500">{{ item.name || item.label }} x{{ item.qty }}</span>
                            <span class="text-right text-[#1F2937]" style="font-weight: 600;">{{ formatRupiah(item.line_total || (Number(item.unit_price || item.price || 0) * Number(item.qty || 1))) }}</span>
                        </div>
                        <div class="border-t border-dashed border-slate-300 pt-3">
                            <div class="flex justify-between gap-4 text-base">
                                <span class="text-[#1F2937]" style="font-weight: 800;">Total Bayar Tunai</span>
                                <span class="text-[#1F2937]" style="font-weight: 900;">{{ formatRupiah(props.request.total_amount) }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                        Berlaku sampai {{ formatExpiry(props.request.expires_at) }}. Jika kedaluwarsa, scan QR dan isi ulang.
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <a :href="props.routes.walkIn" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl border border-slate-300 px-4 text-sm text-slate-700" style="font-weight: 700;">Buat Lagi</a>
                        <a :href="props.routes.queueBoard" class="inline-flex h-11 flex-1 items-center justify-center rounded-xl bg-[#2563EB] px-4 text-sm text-white" style="font-weight: 800;">Lihat Queue Board</a>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
