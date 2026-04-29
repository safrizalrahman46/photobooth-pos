<script setup>
import { Download, Plus, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    summaryCards: { type: Array, default: () => [] },
    revenueSeries: { type: Array, default: () => [] },
    activeRevenuePeriod: { type: String, default: '7d' },
    revenueTotal: { type: String, default: 'Rp 0' },
    bookingTotal: { type: Number, default: 0 },
    queueStats: { type: Object, default: () => ({}) },
    currentQueue: { type: Object, default: null },
    waitingQueue: { type: Array, default: () => [] },
    recentTransactions: { type: Array, default: () => [] },
    recentActivities: { type: Array, default: () => [] },
    panelBookingsUrl: { type: String, default: '/admin/bookings' },
    panelTransactionsUrl: { type: String, default: '/admin/transactions' },
    formatRupiah: { type: Function, required: true },
});

const emit = defineEmits(['set-revenue-period']);
</script>

<template>
    <div class="space-y-5">
        <section
            class="relative overflow-hidden rounded-2xl px-6 py-5"
            style="background: linear-gradient(135deg, #1D4ED8 0%, #2563EB 50%, #3B82F6 100%); box-shadow: 0 4px 20px rgba(37,99,235,0.2);"
        >
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-6 -top-6 h-32 w-32 rounded-full" style="background: rgba(147,197,253,0.18);"></div>
                <div class="absolute right-20 top-4 h-10 w-10 rounded-full" style="background: rgba(191,219,254,0.16);"></div>
                <div class="absolute bottom-0 right-0 h-full w-44 opacity-[0.08]" style="background-image: repeating-linear-gradient(-45deg, #FFFFFF 0px, #FFFFFF 1px, transparent 1px, transparent 12px);"></div>
            </div>
            <div class="relative flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="mb-1 flex items-center gap-2">
                        <Sparkles class="h-3.5 w-3.5" style="color: #93C5FD;" />
                        <span style="color: #93C5FD; font-size: 0.75rem; font-weight: 500;">Owner overview</span>
                    </div>
                    <h2 class="mb-1 text-white" style="font-size: 1.4rem; font-weight: 700;">Business Overview</h2>
                    <p style="color: rgba(255,255,255,0.65); font-size: 0.8rem;">Semua indikator utama dalam satu layar.</p>
                </div>

                <div class="flex flex-wrap gap-2.5">
                    <a :href="panelTransactionsUrl" class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-[0.8rem]" style="background: rgba(255,255,255,0.12); color: #FFFFFF; border: 1px solid rgba(255,255,255,0.2); font-weight: 500;">
                        <Download class="h-3.5 w-3.5" />
                        Export
                    </a>
                    <a :href="`${panelBookingsUrl}/create`" class="flex items-center gap-1.5 rounded-xl px-4 py-2 text-[0.8rem]" style="background: #FFFFFF; color: #2563EB; font-weight: 600;">
                        <Plus class="h-3.5 w-3.5" />
                        New Booking
                    </a>
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="(card, index) in summaryCards"
                :key="`summary-${index}`"
                class="rounded-2xl border p-5"
                style="background: #FFFFFF; border-color: #EEF2FF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 6px 20px rgba(37,99,235,0.05);"
            >
                <p class="text-xs text-[#94A3B8]">{{ card.title }}</p>
                <p class="mt-1 text-[1.45rem] font-bold text-[#0F172A]">{{ card.value }}</p>
                <p class="mt-1 text-xs text-[#64748B]">{{ card.change }} · {{ card.changeLabel }}</p>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <article class="rounded-2xl border p-6 lg:col-span-2" style="background: #FFFFFF; border-color: #EEF2FF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 6px 20px rgba(37,99,235,0.05);">
                <div class="mb-5 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-[#0F172A]">Revenue Overview</h3>
                        <p class="text-xs text-[#94A3B8]">Revenue + booking trend</p>
                    </div>
                    <div class="flex gap-1 rounded-lg p-1" style="background: #F8FAFC; border: 1px solid #EEF2FF;">
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-xs"
                            :style="{ background: activeRevenuePeriod === '7d' ? '#2563EB' : 'transparent', color: activeRevenuePeriod === '7d' ? '#FFFFFF' : '#94A3B8' }"
                            @click="emit('set-revenue-period', '7d')"
                        >
                            Last 7 days
                        </button>
                        <button
                            type="button"
                            class="rounded-md px-3 py-1.5 text-xs"
                            :style="{ background: activeRevenuePeriod === '30d' ? '#2563EB' : 'transparent', color: activeRevenuePeriod === '30d' ? '#FFFFFF' : '#94A3B8' }"
                            @click="emit('set-revenue-period', '30d')"
                        >
                            Last 30 days
                        </button>
                    </div>
                </div>

                <div class="mb-4 flex gap-5 text-sm">
                    <div><span class="text-[#94A3B8]">Revenue:</span> <strong class="text-[#1F2937]">{{ revenueTotal }}</strong></div>
                    <div><span class="text-[#94A3B8]">Bookings:</span> <strong class="text-[#1F2937]">{{ bookingTotal }}</strong></div>
                </div>

                <div class="space-y-2">
                    <div v-for="point in revenueSeries" :key="`point-${point.key}`" class="grid grid-cols-[72px_1fr_96px] items-center gap-3">
                        <span class="text-xs text-[#64748B]">{{ point.label }}</span>
                        <div class="h-2 rounded-full bg-[#DBEAFE]">
                            <div class="h-full rounded-full" style="background: linear-gradient(90deg, #2563EB, #60A5FA);" :style="{ width: `${Math.max(5, (Number(point.revenue || 0) / Math.max(...revenueSeries.map((item) => Number(item.revenue || 0)), 1)) * 100)}%` }"></div>
                        </div>
                        <span class="text-right text-xs text-[#2563EB]">{{ formatRupiah(point.revenue || 0) }}</span>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border p-6" style="background: #FFFFFF; border-color: #EEF2FF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 6px 20px rgba(37,99,235,0.05);">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-[#0F172A]">Queue Monitor</h3>
                    <span class="rounded-full bg-[#ECFDF5] px-2 py-0.5 text-xs text-[#059669]">Live</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-lg bg-[#F8FAFC] p-3 text-xs text-[#64748B]">In Queue <p class="text-lg font-bold text-[#1F2937]">{{ queueStats.in_queue || 0 }}</p></div>
                    <div class="rounded-lg bg-[#F8FAFC] p-3 text-xs text-[#64748B]">Now Serving <p class="text-lg font-bold text-[#1F2937]">{{ queueStats.in_session || 0 }}</p></div>
                    <div class="rounded-lg bg-[#F8FAFC] p-3 text-xs text-[#64748B]">Waiting <p class="text-lg font-bold text-[#1F2937]">{{ queueStats.waiting || 0 }}</p></div>
                    <div class="rounded-lg bg-[#F8FAFC] p-3 text-xs text-[#64748B]">Completed <p class="text-lg font-bold text-[#1F2937]">{{ queueStats.completed_today || 0 }}</p></div>
                </div>
                <div v-if="currentQueue" class="mt-4 rounded-lg border border-[#DBEAFE] bg-[#EFF6FF] p-3 text-xs">
                    <p class="font-semibold text-[#2563EB]">{{ currentQueue.queue_code }}</p>
                    <p class="text-[#1F2937]">{{ currentQueue.customer_name }}</p>
                    <p class="text-[#64748B]">{{ currentQueue.package_name }}</p>
                </div>
            </article>
        </section>

        <section class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <article class="rounded-2xl border p-5" style="background: #FFFFFF; border-color: #EEF2FF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 6px 20px rgba(37,99,235,0.05);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Recent Transactions</h3>
                <div class="space-y-2">
                    <div v-for="item in recentTransactions.slice(0, 6)" :key="item.id" class="flex items-center justify-between rounded-lg bg-[#F8FAFC] px-3 py-2" style="border: 1px solid #EEF2FF;">
                        <div>
                            <p class="text-xs font-semibold text-[#2563EB]">{{ item.id }}</p>
                            <p class="text-xs text-[#64748B]">{{ item.customer }}</p>
                        </div>
                        <p class="text-xs font-semibold text-[#1F2937]">{{ item.amountText }}</p>
                    </div>
                </div>
            </article>

            <article class="rounded-2xl border p-5" style="background: #FFFFFF; border-color: #EEF2FF; box-shadow: 0 1px 3px rgba(37,99,235,0.05), 0 6px 20px rgba(37,99,235,0.05);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Activity Logs</h3>
                <div class="space-y-2">
                    <div v-for="item in recentActivities.slice(0, 6)" :key="item.id" class="rounded-lg bg-[#F8FAFC] px-3 py-2" style="border: 1px solid #EEF2FF;">
                        <p class="text-xs text-[#1F2937]">{{ item.action }}</p>
                        <p class="text-xs text-[#94A3B8]">{{ item.actor }} · {{ item.module }} · {{ item.time }}</p>
                    </div>
                </div>
            </article>
        </section>
    </div>
</template>
