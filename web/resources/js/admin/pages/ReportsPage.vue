<script setup>
import { computed } from 'vue';
import { Download } from 'lucide-vue-next';

import StackedBarChart from '../components/StackedBarChart.vue';

const props = defineProps({
    reportFilters: { type: Object, required: true },
    reportError: { type: String, default: '' },
    reportLoading: { type: Boolean, default: false },
    reportSummaryCards: { type: Array, default: () => [] },
    reportDailyRows: { type: Array, default: () => [] },
    reportStatusRows: { type: Array, default: () => [] },
    reportPackageRows: { type: Array, default: () => [] },
    reportCashierRows: { type: Array, default: () => [] },
    reportAddOnRows: { type: Array, default: () => [] },
    reportPackageOptions: { type: Array, default: () => [] },
    reportCashierOptions: { type: Array, default: () => [] },
    reportRangeLabel: { type: String, default: '' },
    reportCashierChartLabels: { type: Array, default: () => [] },
    reportCashierChartDatasets: { type: Array, default: () => [] },
});

const emit = defineEmits(['export-report']);

const hasReportData = computed(() => {
    return Boolean(
        props.reportDailyRows.length
        || props.reportCashierRows.length
        || props.reportPackageRows.length
        || props.reportAddOnRows.length,
    );
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-3xl px-6 py-5" style="background: linear-gradient(135deg, #14532D 0%, #166534 52%, #15803D 100%); box-shadow: 0 10px 28px rgba(22,101,52,0.24);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-12 -top-10 h-40 w-40 rounded-full" style="background: rgba(187,247,208,0.2);"></div>
                <div class="absolute left-8 top-5 h-9 w-9 rounded-full" style="background: rgba(220,252,231,0.18);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3 text-white">
                <div>
                    <h2 class="text-[1.35rem] font-bold">Reports</h2>
                    <p class="text-sm text-white/80">Analitik revenue, conversion, dan performa cashier per hari.</p>
                </div>
                <button type="button" class="inline-flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold transition" :style="{ background: reportLoading || !hasReportData ? 'rgba(255,255,255,0.55)' : '#FFFFFF', color: reportLoading || !hasReportData ? '#6B7280' : '#166534', cursor: reportLoading || !hasReportData ? 'not-allowed' : 'pointer' }" :disabled="reportLoading || !hasReportData" @click="emit('export-report')">
                    <Download class="h-4 w-4" />
                    {{ reportLoading ? 'Menyiapkan...' : 'Export Excel' }}
                </button>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 rounded-3xl border p-4 lg:grid-cols-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">From</label>
                <input v-model="reportFilters.from" type="date" class="w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
            </div>
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">To</label>
                <input v-model="reportFilters.to" type="date" class="w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
            </div>
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">Package</label>
                <select v-model="reportFilters.package_id" class="w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #E2E8F0;">
                    <option value="">All packages</option>
                    <option v-for="pkg in reportPackageOptions" :key="`report-pkg-option-${pkg.id}`" :value="String(pkg.id)">
                        {{ pkg.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">Cashier</label>
                <select v-model="reportFilters.cashier_id" class="w-full rounded-xl border px-3 py-2 text-sm" style="border-color: #E2E8F0;">
                    <option value="">All cashiers</option>
                    <option v-for="cashier in reportCashierOptions" :key="`report-cashier-option-${cashier.id}`" :value="String(cashier.id)">
                        {{ cashier.name }}
                    </option>
                </select>
            </div>
            <div class="rounded-2xl px-4 py-3" style="background: #F0FDF4; border: 1px solid #BBF7D0;">
                <p class="text-xs text-[#16A34A]">Range Aktif</p>
                <p class="mt-1 text-sm font-semibold text-[#166534]">{{ reportRangeLabel || '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
            <div class="rounded-2xl border px-4 py-3" style="border-color: #DCFCE7; background: #FFFFFF;">
                <p class="text-xs text-[#94A3B8]">Data Harian</p>
                <p class="mt-1 text-lg font-semibold text-[#0F172A]">{{ reportDailyRows.length }} hari</p>
            </div>
            <div class="rounded-2xl border px-4 py-3" style="border-color: #DCFCE7; background: #FFFFFF;">
                <p class="text-xs text-[#94A3B8]">Cashier Aktif</p>
                <p class="mt-1 text-lg font-semibold text-[#0F172A]">{{ reportCashierRows.length }} cashier</p>
            </div>
            <div class="rounded-2xl border px-4 py-3" style="border-color: #DCFCE7; background: #FFFFFF;">
                <p class="text-xs text-[#94A3B8]">Paket Tampil</p>
                <p class="mt-1 text-lg font-semibold text-[#0F172A]">{{ reportPackageRows.length }} paket</p>
            </div>
        </div>

        <p v-if="reportError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #DC2626;">
            {{ reportError }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
            <div
                v-for="(card, index) in reportSummaryCards"
                :key="`report-summary-card-${index}-${card.label}`"
                class="rounded-3xl border p-4"
                :style="{ borderColor: card.tone.border, background: '#FFFFFF', boxShadow: '0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08)' }"
            >
                <p class="text-xs text-[#94A3B8]">{{ card.label }}</p>
                <p class="mt-1 text-xl font-bold text-[#1F2937]">{{ card.value }}</p>
                <p class="mt-1 text-xs" :style="{ color: card.tone.accent }">{{ card.helper }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-[minmax(0,1.25fr)_360px]">
            <div class="rounded-3xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="text-sm font-semibold text-[#1F2937]">Cashier Performance per Hari</h3>
                <p class="mt-1 text-xs text-[#94A3B8]">Stacked bar tanpa donut chart, sesuai periode filter aktif.</p>

                <div v-if="reportLoading" class="py-16 text-center text-sm text-[#94A3B8]">Loading report...</div>
                <StackedBarChart
                    v-else
                    :labels="reportCashierChartLabels"
                    :datasets="reportCashierChartDatasets"
                    :height="320"
                    empty-label="Belum ada pendapatan cashier pada periode ini."
                />
            </div>

            <div class="rounded-3xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="text-sm font-semibold text-[#1F2937]">Booking Status Mix</h3>
                <div class="mt-3 space-y-2">
                    <div v-for="status in reportStatusRows" :key="`report-status-${status.status}`" class="flex items-center justify-between rounded-2xl px-3 py-2" style="background: #F8FAFC;">
                        <span class="text-xs text-[#64748B]">{{ status.label }}</span>
                        <span class="text-xs font-semibold text-[#1F2937]">{{ status.count }}</span>
                    </div>
                    <p v-if="!reportStatusRows.length" class="text-xs text-[#94A3B8]">No status data available.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
            <div class="rounded-3xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Ringkasan Harian</h3>
                <div class="max-h-[420px] space-y-2 overflow-y-auto pr-1">
                    <div v-for="row in reportDailyRows" :key="`report-day-${row.date}`" class="rounded-2xl bg-[#F8FAFC] px-4 py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div>
                                <p class="text-sm font-semibold text-[#0F172A]">{{ row.label }}</p>
                                <p class="mt-1 text-xs text-[#64748B]">{{ row.transactions }} transaksi · {{ row.bookings }} booking · {{ row.walk_ins }} walk-in</p>
                            </div>
                            <p class="text-sm font-semibold text-[#059669]">{{ row.revenue_text }}</p>
                        </div>
                    </div>
                    <p v-if="!reportDailyRows.length" class="text-xs text-[#94A3B8]">No report data in selected range.</p>
                </div>
            </div>

            <div class="rounded-3xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Package Popularity</h3>
                <div class="max-h-[420px] space-y-2 overflow-y-auto pr-1">
                    <div v-for="pkg in reportPackageRows" :key="`report-package-row-${pkg.package_id}`" class="flex items-center justify-between rounded-2xl px-3 py-2" style="background: #F8FAFC;">
                        <div>
                            <p class="text-xs text-[#1F2937]">{{ pkg.package_name }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ pkg.booking_count }} bookings</p>
                        </div>
                        <span class="text-xs font-semibold text-[#059669]">{{ pkg.revenue_text }}</span>
                    </div>
                    <p v-if="!reportPackageRows.length" class="text-xs text-[#94A3B8]">No package report in range.</p>
                </div>
            </div>

            <div class="rounded-3xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Add-on Usage</h3>
                <div class="max-h-[420px] space-y-2 overflow-y-auto pr-1">
                    <div v-for="addOn in reportAddOnRows" :key="`report-addon-row-${addOn.add_on_id}`" class="flex items-center justify-between rounded-2xl px-3 py-2" style="background: #F8FAFC;">
                        <div>
                            <p class="text-xs text-[#1F2937]">{{ addOn.add_on_name }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ addOn.total_qty }} qty · {{ addOn.booking_count }} bookings</p>
                        </div>
                        <span class="text-xs font-semibold text-[#0F766E]">{{ addOn.total_revenue_text }}</span>
                    </div>
                    <p v-if="!reportAddOnRows.length" class="text-xs text-[#94A3B8]">No add-on usage in range.</p>
                </div>
            </div>
        </div>

        <div class="rounded-3xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
            <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Ringkasan Cashier</h3>
            <div class="max-h-[360px] space-y-2 overflow-y-auto pr-1">
                <div v-for="cashier in reportCashierRows" :key="`report-cashier-row-${cashier.cashier_id}`" class="flex items-center justify-between rounded-2xl px-4 py-3" style="background: #F8FAFC;">
                    <div>
                        <p class="text-sm font-semibold text-[#1F2937]">{{ cashier.cashier_name }}</p>
                        <p class="text-xs text-[#94A3B8]">{{ cashier.transaction_count }} trx · Avg {{ cashier.average_transaction_text }}</p>
                    </div>
                    <span class="text-sm font-semibold text-[#2563EB]">{{ cashier.revenue_text }}</span>
                </div>
                <p v-if="!reportCashierRows.length" class="text-xs text-[#94A3B8]">No cashier report in range.</p>
            </div>
        </div>
    </div>
</template>
