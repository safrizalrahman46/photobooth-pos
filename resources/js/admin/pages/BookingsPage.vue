<script setup>
import { Search } from 'lucide-vue-next';

defineProps({
    search: { type: String, default: '' },
    filterStatus: { type: String, default: 'all' },
    filterTabs: { type: Array, default: () => [] },
    panelBookingsUrl: { type: String, default: '/admin/bookings' },
    normalizedRows: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    bookingResultCaption: { type: String, default: '' },
    canGoPrev: { type: Boolean, default: false },
    canGoNext: { type: Boolean, default: false },
    pagination: { type: Object, default: () => ({ current_page: 1, last_page: 1 }) },
    resolveBookingStatus: { type: Function, required: true },
});

const emit = defineEmits([
    'update:search',
    'set-filter-status',
    'go-prev-page',
    'go-next-page',
]);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(135deg, #0F766E 0%, #0EA5A5 62%, #14B8A6 100%); box-shadow: 0 6px 24px rgba(15,118,110,0.26);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-8 top-0 h-28 w-28 rounded-full" style="background: rgba(240,253,250,0.2);"></div>
                <div class="absolute right-6 top-5 h-10 w-10 rounded-full" style="background: rgba(204,251,241,0.24);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Bookings</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.78);">Real-time reservation monitoring with status filters.</p>
                </div>
                <a :href="`${panelBookingsUrl}/create`" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #0F766E;">New Booking</a>
            </div>
        </section>

        <div class="overflow-hidden rounded-2xl border" style="background: #FFFFFF; border-color: #CCFBF1; box-shadow: 0 1px 3px rgba(15,118,110,0.08), 0 8px 20px rgba(15,118,110,0.08);">
            <div class="border-b p-6" style="border-color: #CCFBF1; background: #F0FDFA;">
                <div class="flex flex-col gap-3 sm:flex-row">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" />
                        <input
                            :value="search"
                            type="text"
                            placeholder="Search by customer name or booking ID..."
                            class="w-full rounded-lg border py-2 pl-9 pr-4 text-sm"
                            style="background: #F8FAFC; border-color: #EEF2FF;"
                            @input="emit('update:search', $event.target.value)"
                        >
                    </div>
                </div>

                <div class="mt-3 flex gap-1.5 overflow-x-auto pb-1">
                    <button
                        v-for="tab in filterTabs"
                        :key="`booking-module-filter-tab-${tab.key}`"
                        type="button"
                        class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs"
                        :style="{
                            background: filterStatus === tab.key ? '#2563EB' : '#F8FAFC',
                            color: filterStatus === tab.key ? '#FFFFFF' : '#64748B',
                            border: `1px solid ${filterStatus === tab.key ? '#2563EB' : '#EEF2FF'}`,
                        }"
                        @click="emit('set-filter-status', tab.key)"
                    >
                        {{ tab.label }}
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr style="border-bottom: 1px solid #CCFBF1; background: #F0FDFA;">
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Booking ID</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Customer</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Package</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Date and Time</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Amount</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Payment</th>
                            <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in normalizedRows" :key="`booking-module-row-${row.id}`" style="border-bottom: 1px solid #F8FAFC;">
                            <td class="px-5 py-3.5"><span class="text-sm font-semibold text-[#2563EB]">{{ row.id }}</span></td>
                            <td class="px-5 py-3.5 text-sm text-[#1F2937]">{{ row.name }}</td>
                            <td class="px-5 py-3.5 text-sm text-[#374151]">{{ row.pkg }}</td>
                            <td class="px-5 py-3.5">
                                <p class="text-sm text-[#1F2937]">{{ row.date }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ row.time }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-sm font-semibold text-[#1F2937]">{{ row.amount_text }}</td>
                            <td class="px-5 py-3.5 text-xs text-[#64748B]">{{ row.payment }}</td>
                            <td class="px-5 py-3.5">
                                <span
                                    class="rounded-full px-2.5 py-1 text-xs"
                                    :style="{ background: resolveBookingStatus(row.status).bg, color: resolveBookingStatus(row.status).color }"
                                >
                                    {{ resolveBookingStatus(row.status).label }}
                                </span>
                            </td>
                        </tr>
                        <tr v-if="loading">
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-[#94A3B8]">Loading bookings...</td>
                        </tr>
                        <tr v-else-if="!normalizedRows.length">
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-[#94A3B8]">No bookings found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 p-4" style="border-top: 1px solid #F1F5F9;">
                <p class="text-xs text-[#94A3B8]">{{ bookingResultCaption }}</p>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs text-gray-600"
                        :class="canGoPrev ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                        :disabled="!canGoPrev || loading"
                        @click="emit('go-prev-page')"
                    >
                        Previous
                    </button>
                    <span class="text-xs text-[#94A3B8]">Page {{ pagination.current_page }} / {{ Math.max(pagination.last_page, 1) }}</span>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs text-gray-600"
                        :class="canGoNext ? 'hover:bg-gray-50' : 'cursor-not-allowed opacity-50'"
                        :disabled="!canGoNext || loading"
                        @click="emit('go-next-page')"
                    >
                        Next
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
