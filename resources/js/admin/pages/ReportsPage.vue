<script setup>
defineProps({
    reportFilters: { type: Object, required: true },
    reportError: { type: String, default: '' },
    reportLoading: { type: Boolean, default: false },
    reportSummaryCards: { type: Array, default: () => [] },
    reportDailyRows: { type: Array, default: () => [] },
    reportDailyMaxRevenue: { type: Number, default: 1 },
    reportStatusRows: { type: Array, default: () => [] },
    reportPackageRows: { type: Array, default: () => [] },
    reportCashierRows: { type: Array, default: () => [] },
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #14532D 0%, #166534 58%, #15803D 100%); box-shadow: 0 6px 24px rgba(22,101,52,0.26);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-12 -top-10 h-40 w-40 rounded-full" style="background: rgba(187,247,208,0.2);"></div>
                <div class="absolute left-8 top-5 h-9 w-9 rounded-full" style="background: rgba(220,252,231,0.18);"></div>
            </div>
            <div class="relative">
                <h2 class="text-[1.35rem] font-bold text-white">Reports</h2>
                <p class="text-sm" style="color: rgba(255,255,255,0.8);">Daily revenue analytics and conversion quality from backend report summary.</p>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 rounded-2xl border p-4 lg:grid-cols-4" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">From</label>
                <input v-model="reportFilters.from" type="date" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
            </div>
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">To</label>
                <input v-model="reportFilters.to" type="date" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
            </div>
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">Package</label>
                <select v-model="reportFilters.package_id" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;">
                    <option value="">All packages</option>
                    <option v-for="pkg in reportPackageRows" :key="`report-pkg-option-${pkg.package_id}`" :value="String(pkg.package_id)">
                        {{ pkg.package_name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-[#94A3B8]">Cashier</label>
                <select v-model="reportFilters.cashier_id" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;">
                    <option value="">All cashiers</option>
                    <option v-for="cashier in reportCashierRows" :key="`report-cashier-option-${cashier.cashier_id}`" :value="String(cashier.cashier_id)">
                        {{ cashier.cashier_name }}
                    </option>
                </select>
            </div>
        </div>

        <p v-if="reportError" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #DC2626;">
            {{ reportError }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div
                v-for="(card, index) in reportSummaryCards"
                :key="`report-summary-card-${index}-${card.label}`"
                class="rounded-2xl border p-4"
                :style="{ borderColor: card.tone.border, background: '#FFFFFF', boxShadow: '0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08)' }"
            >
                <p class="text-xs text-[#94A3B8]">{{ card.label }}</p>
                <p class="mt-1 text-xl font-bold text-[#1F2937]">{{ card.value }}</p>
                <p class="mt-1 text-xs" :style="{ color: card.tone.accent }">{{ card.helper }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
            <div class="rounded-2xl border p-5 lg:col-span-2" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="text-sm font-semibold text-[#1F2937]">Daily Revenue Trend</h3>
                <p class="text-xs text-[#94A3B8]">{{ reportDailyMinDate }} to {{ reportDailyMaxDate }}</p>

                <div v-if="reportLoading" class="py-16 text-center text-sm text-[#94A3B8]">Loading report...</div>
                <div v-else-if="!reportDailyRows.length" class="py-16 text-center text-sm text-[#94A3B8]">No report data in selected range.</div>
                <div v-else class="mt-4 space-y-2">
                    <div
                        v-for="row in reportDailyRows"
                        :key="`report-day-${row.date}`"
                        class="grid grid-cols-[80px_1fr_90px] items-center gap-3"
                    >
                        <span class="text-xs text-[#64748B]">{{ row.label }}</span>
                        <div class="h-2 overflow-hidden rounded-full" style="background: #DBEAFE;">
                            <div
                                class="h-full rounded-full"
                                style="background: linear-gradient(90deg, #2563EB, #60A5FA);"
                                :style="{ width: `${Math.max(6, (Number(row.revenue || 0) / reportDailyMaxRevenue) * 100)}%` }"
                            ></div>
                        </div>
                        <span class="text-right text-xs text-[#2563EB]">{{ row.revenue_text }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="text-sm font-semibold text-[#1F2937]">Booking Status Mix</h3>
                <div class="mt-3 space-y-2">
                    <div v-for="status in reportStatusRows" :key="`report-status-${status.status}`" class="flex items-center justify-between rounded-lg px-3 py-2" style="background: #F8FAFC;">
                        <span class="text-xs text-[#64748B]">{{ status.label }}</span>
                        <span class="text-xs font-semibold text-[#1F2937]">{{ status.count }}</span>
                    </div>
                    <p v-if="!reportStatusRows.length" class="text-xs text-[#94A3B8]">No status data available.</p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Package Popularity</h3>
                <div class="space-y-2">
                    <div v-for="pkg in reportPackageRows" :key="`report-package-row-${pkg.package_id}`" class="flex items-center justify-between rounded-lg px-3 py-2" style="background: #F8FAFC;">
                        <div>
                            <p class="text-xs text-[#1F2937]">{{ pkg.package_name }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ pkg.booking_count }} bookings</p>
                        </div>
                        <span class="text-xs font-semibold text-[#059669]">{{ pkg.revenue_text }}</span>
                    </div>
                    <p v-if="!reportPackageRows.length" class="text-xs text-[#94A3B8]">No package report in range.</p>
                </div>
            </div>

            <div class="rounded-2xl border p-5" style="border-color: #DCFCE7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(21,128,61,0.08), 0 8px 20px rgba(21,128,61,0.08);">
                <h3 class="mb-3 text-sm font-semibold text-[#1F2937]">Cashier Performance</h3>
                <div class="space-y-2">
                    <div v-for="cashier in reportCashierRows" :key="`report-cashier-row-${cashier.cashier_id}`" class="flex items-center justify-between rounded-lg px-3 py-2" style="background: #F8FAFC;">
                        <div>
                            <p class="text-xs text-[#1F2937]">{{ cashier.cashier_name }}</p>
                            <p class="text-xs text-[#94A3B8]">{{ cashier.transaction_count }} trx · Avg {{ cashier.average_transaction_text }}</p>
                        </div>
                        <span class="text-xs font-semibold text-[#2563EB]">{{ cashier.revenue_text }}</span>
                    </div>
                    <p v-if="!reportCashierRows.length" class="text-xs text-[#94A3B8]">No cashier report in range.</p>
                </div>
            </div>
        </div>
    </div>
</template>
