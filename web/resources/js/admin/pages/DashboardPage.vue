<script setup>
import { BellRing, Clock3, Sparkles } from 'lucide-vue-next';

import RevenueOverviewChart from '../components/RevenueOverviewChart.vue';
import StackedBarChart from '../components/StackedBarChart.vue';

const props = defineProps({
    summaryCards: { type: Array, default: () => [] },
    revenueSeries: { type: Array, default: () => [] },
    activeRevenuePeriod: { type: String, default: '7d' },
    revenueTotal: { type: String, default: 'Rp 0' },
    bookingTotal: { type: Number, default: 0 },
    queueStats: { type: Object, default: () => ({}) },
    currentQueue: { type: Object, default: null },
    waitingQueue: { type: Array, default: () => [] },
    ownerHighlights: { type: Array, default: () => [] },
    cashierChartLabels: { type: Array, default: () => [] },
    cashierChartDatasets: { type: Array, default: () => [] },
    reportRangeLabel: { type: String, default: '' },
    formatRupiah: { type: Function, required: true },
});

const emit = defineEmits(['set-revenue-period']);

const resolveHighlightTone = (tone) => {
    const normalized = String(tone || '').toLowerCase();

    if (normalized === 'blue') {
        return { bg: '#EFF6FF', color: '#2563EB', border: '#DBEAFE' };
    }

    if (normalized === 'amber') {
        return { bg: '#FFFBEB', color: '#D97706', border: '#FDE68A' };
    }

    if (normalized === 'purple') {
        return { bg: '#F5F3FF', color: '#7C3AED', border: '#DDD6FE' };
    }

    if (normalized === 'rose') {
        return { bg: '#FFF1F2', color: '#E11D48', border: '#FECDD3' };
    }

    return { bg: '#F8FAFC', color: '#64748B', border: '#E2E8F0' };
};

const queueSourceLabel = (sourceType) => {
    return String(sourceType || '').toLowerCase() === 'booking' ? 'Booking' : 'Walk-in';
};

const revenueChartLabels = () => props.revenueSeries.map((point) => String(point?.label || '-'));
const revenueChartData = () => props.revenueSeries.map((point) => Number(point?.revenue || 0));
const bookingChartData = () => props.revenueSeries.map((point) => Number(point?.bookings || 0));
</script>

<template>
    <div class="space-y-5">
        <section class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.45fr)_360px]">
            <article class="rounded-3xl border p-6" style="background: #FFFFFF; border-color: #DBEAFE; box-shadow: 0 1px 3px rgba(37,99,235,0.06), 0 12px 28px rgba(37,99,235,0.08);">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <div class="mb-2 flex items-center gap-2 text-xs font-medium text-[#2563EB]">
                            <Sparkles class="h-3.5 w-3.5" />
                            Revenue Overview
                        </div>
                        <h2 class="text-[1.55rem] font-bold text-[#0F172A]">{{ revenueTotal }}</h2>
                        <p class="mt-1 text-sm text-[#64748B]">{{ bookingTotal }} booking pada periode terpilih.</p>
                    </div>

                    <div class="flex gap-1 rounded-xl p-1" style="background: #F8FAFC; border: 1px solid #E2E8F0;">
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            :style="{ background: activeRevenuePeriod === '7d' ? '#2563EB' : 'transparent', color: activeRevenuePeriod === '7d' ? '#FFFFFF' : '#64748B' }"
                            @click="emit('set-revenue-period', '7d')"
                        >
                            7 Hari
                        </button>
                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-xs font-medium"
                            :style="{ background: activeRevenuePeriod === '30d' ? '#2563EB' : 'transparent', color: activeRevenuePeriod === '30d' ? '#FFFFFF' : '#64748B' }"
                            @click="emit('set-revenue-period', '30d')"
                        >
                            30 Hari
                        </button>
                    </div>
                </div>

                <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    <div
                        v-for="(card, index) in summaryCards"
                        :key="`dashboard-summary-${index}-${card.title}`"
                        class="rounded-2xl border px-4 py-3"
                        :style="{ background: card.tone?.light || '#F8FAFC', borderColor: card.tone?.border || '#E2E8F0' }"
                    >
                        <p class="text-[0.7rem] uppercase tracking-[0.08em] text-[#64748B]">{{ card.title }}</p>
                        <p class="mt-1 text-lg font-bold text-[#0F172A]">{{ card.value }}</p>
                        <p class="mt-1 text-xs" :style="{ color: card.tone?.accent || '#2563EB' }">{{ card.change }} · {{ card.changeLabel }}</p>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FCFDFF;">
                    <RevenueOverviewChart
                        :labels="revenueChartLabels()"
                        :revenue-data="revenueChartData()"
                        :booking-data="bookingChartData()"
                        :height="280"
                        empty-label="Belum ada trend revenue pada periode ini."
                    />
                </div>
            </article>

            <article class="rounded-3xl border p-5" style="background: #FFFFFF; border-color: #E2E8F0; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 12px 28px rgba(15,23,42,0.07);">
                <div class="mb-4 flex items-center gap-2 text-sm font-semibold text-[#0F172A]">
                    <BellRing class="h-4 w-4 text-[#D97706]" />
                    Alert Operasional
                </div>

                <div class="space-y-3">
                    <div
                        v-for="(item, index) in ownerHighlights"
                        :key="`dashboard-highlight-${index}-${item.label}`"
                        class="rounded-2xl border px-4 py-3"
                        :style="{ background: resolveHighlightTone(item.tone).bg, borderColor: resolveHighlightTone(item.tone).border }"
                    >
                        <p class="text-xs text-[#64748B]">{{ item.label }}</p>
                        <p class="mt-1 text-xl font-bold" :style="{ color: resolveHighlightTone(item.tone).color }">{{ item.value }}</p>
                        <p class="mt-1 text-xs text-[#64748B]">{{ item.helper }}</p>
                    </div>

                    <div v-if="!ownerHighlights.length" class="rounded-2xl border border-dashed px-4 py-8 text-center text-sm text-[#94A3B8]" style="border-color: #E2E8F0; background: #F8FAFC;">
                        Belum ada alert operasional untuk ditampilkan.
                    </div>
                </div>
            </article>
        </section>

        <section class="rounded-3xl border p-6" style="background: #FFFFFF; border-color: #DBEAFE; box-shadow: 0 1px 3px rgba(37,99,235,0.06), 0 14px 30px rgba(37,99,235,0.08);">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.08em] text-[#2563EB]">Queue Monitor</p>
                    <h3 class="mt-1 text-[1.45rem] font-bold text-[#0F172A]">Pantau antrian aktif dalam satu panel</h3>
                </div>
                <div class="flex flex-wrap gap-2 text-xs">
                    <span class="rounded-full bg-[#EFF6FF] px-3 py-1 font-semibold text-[#2563EB]">Waiting {{ queueStats.waiting || 0 }}</span>
                    <span class="rounded-full bg-[#F5F3FF] px-3 py-1 font-semibold text-[#7C3AED]">In Session {{ queueStats.in_session || 0 }}</span>
                    <span class="rounded-full bg-[#ECFDF5] px-3 py-1 font-semibold text-[#059669]">Done {{ queueStats.completed_today || 0 }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.15fr)_380px]">
                <article class="overflow-hidden rounded-3xl" style="background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 52%, #60A5FA 100%); box-shadow: inset 0 1px 0 rgba(255,255,255,0.12);">
                    <div class="px-6 py-5 text-white">
                        <div class="flex items-center justify-between text-xs font-medium text-white/80">
                            <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full bg-emerald-300"></span>Now Serving</span>
                            <span>{{ currentQueue ? queueSourceLabel(currentQueue.source_type) : 'Idle' }}</span>
                        </div>

                        <template v-if="currentQueue">
                            <p class="mt-4 text-[4.5rem] font-extrabold leading-none">{{ currentQueue.queue_code }}</p>
                            <p class="mt-3 text-[1.4rem] font-semibold">{{ currentQueue.customer_name }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-xs text-white/90">
                                <span class="rounded-full bg-white/15 px-3 py-1">{{ currentQueue.package_name }}</span>
                                <span class="rounded-full bg-slate-900/20 px-3 py-1">{{ currentQueue.branch_name || '-' }}</span>
                                <span class="rounded-full bg-slate-900/20 px-3 py-1">{{ currentQueue.status_label }}</span>
                            </div>
                            <div class="mt-5 grid grid-cols-2 gap-3 text-sm text-white/85 sm:grid-cols-4">
                                <div class="rounded-2xl bg-white/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.08em] text-white/70">Total In Queue</p>
                                    <p class="mt-1 text-2xl font-bold text-white">{{ queueStats.in_queue || 0 }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.08em] text-white/70">Waiting</p>
                                    <p class="mt-1 text-2xl font-bold text-white">{{ queueStats.waiting || 0 }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.08em] text-white/70">In Session</p>
                                    <p class="mt-1 text-2xl font-bold text-white">{{ queueStats.in_session || 0 }}</p>
                                </div>
                                <div class="rounded-2xl bg-white/10 px-4 py-3">
                                    <p class="text-xs uppercase tracking-[0.08em] text-white/70">Completed</p>
                                    <p class="mt-1 text-2xl font-bold text-white">{{ queueStats.completed_today || 0 }}</p>
                                </div>
                            </div>
                        </template>

                        <template v-else>
                            <div class="mt-10 rounded-3xl border border-white/15 bg-white/10 px-6 py-10 text-center">
                                <Clock3 class="mx-auto h-8 w-8 text-white/70" />
                                <p class="mt-4 text-xl font-semibold">Belum ada sesi aktif</p>
                                <p class="mt-2 text-sm text-white/75">Tiket waiting berikutnya akan tampil di sini setelah dipanggil.</p>
                            </div>
                        </template>
                    </div>
                </article>

                <article class="rounded-3xl border p-5" style="background: #F8FAFC; border-color: #E2E8F0;">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-[0.08em] text-[#2563EB]">Next Queue</p>
                            <h4 class="mt-1 text-base font-semibold text-[#0F172A]">5 tiket berikutnya</h4>
                        </div>
                        <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-[#64748B]">{{ waitingQueue.length }} tampil</span>
                    </div>

                    <div class="space-y-3">
                        <div v-for="ticket in waitingQueue" :key="`dashboard-waiting-${ticket.ticket_id}`" class="rounded-2xl border bg-white px-4 py-3" style="border-color: #E2E8F0;">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-lg font-bold text-[#2563EB]">{{ ticket.queue_code }}</p>
                                    <p class="text-sm font-medium text-[#0F172A]">{{ ticket.customer_name }}</p>
                                    <p class="mt-1 text-xs text-[#64748B]">{{ ticket.package_name }} · {{ ticket.branch_name || '-' }}</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-medium text-[#64748B]">{{ queueSourceLabel(ticket.source_type) }}</span>
                                        <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-medium text-[#64748B]">Masuk {{ ticket.added_at || '-' }}</span>
                                    </div>
                                </div>
                                <span class="rounded-full bg-[#EFF6FF] px-2.5 py-1 text-[0.68rem] font-semibold text-[#2563EB]">{{ ticket.status_label }}</span>
                            </div>
                        </div>

                        <div v-if="!waitingQueue.length" class="rounded-2xl border border-dashed px-4 py-8 text-center text-sm text-[#94A3B8]" style="background: #FFFFFF; border-color: #DBEAFE;">
                            Tidak ada antrean waiting saat ini.
                        </div>
                    </div>
                </article>
            </div>
        </section>

        <section class="rounded-3xl border p-6" style="background: #FFFFFF; border-color: #DCFCE7; box-shadow: 0 1px 3px rgba(21,128,61,0.06), 0 12px 28px rgba(21,128,61,0.08);">
            <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.08em] text-[#15803D]">Cashier Performance</p>
                    <h3 class="mt-1 text-[1.3rem] font-bold text-[#0F172A]">Pendapatan cashier per hari</h3>
                    <p class="mt-1 text-sm text-[#64748B]">{{ reportRangeLabel || 'Menggunakan periode report aktif.' }}</p>
                </div>
            </div>

            <StackedBarChart
                :labels="cashierChartLabels"
                :datasets="cashierChartDatasets"
                :height="300"
                empty-label="Belum ada performa cashier pada periode ini."
            />
        </section>
    </div>
</template>
