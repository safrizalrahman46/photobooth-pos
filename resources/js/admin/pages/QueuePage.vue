<script setup>
defineProps({
    queueStats: { type: Object, default: () => ({}) },
    currentQueue: { type: Object, default: null },
    waitingQueue: { type: Array, default: () => [] },
    queueProgressStyle: { type: Object, default: () => ({ width: '0%' }) },
    queueRemainingText: { type: String, default: '00:00' },
    queueSessionDurationText: { type: String, default: '00:00' },
    resolveQueueStatus: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #0C4A6E 0%, #0369A1 58%, #0284C7 100%); box-shadow: 0 6px 24px rgba(12,74,110,0.24);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-10 -top-8 h-36 w-36 rounded-full" style="background: rgba(186,230,253,0.18);"></div>
                <div class="absolute left-10 top-4 h-8 w-8 rounded-full" style="background: rgba(224,242,254,0.2);"></div>
            </div>
            <div class="relative">
                <h2 class="text-[1.35rem] font-bold text-white">Queue Management</h2>
                <p class="text-sm" style="color: rgba(255,255,255,0.78);">Live queue control with active session tracking.</p>
            </div>
        </section>

        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
            <div class="rounded-2xl border p-4" style="border-color: #E0F2FE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(2,132,199,0.09), 0 8px 20px rgba(2,132,199,0.08);">
                <p class="text-xs text-[#94A3B8]">In Queue</p>
                <p class="mt-1 text-2xl font-bold text-[#2563EB]">{{ queueStats.in_queue }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #E0F2FE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(2,132,199,0.09), 0 8px 20px rgba(2,132,199,0.08);">
                <p class="text-xs text-[#94A3B8]">Now Serving</p>
                <p class="mt-1 text-2xl font-bold text-[#7C3AED]">{{ queueStats.in_session }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #E0F2FE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(2,132,199,0.09), 0 8px 20px rgba(2,132,199,0.08);">
                <p class="text-xs text-[#94A3B8]">Waiting</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ queueStats.waiting }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #E0F2FE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(2,132,199,0.09), 0 8px 20px rgba(2,132,199,0.08);">
                <p class="text-xs text-[#94A3B8]">Completed Today</p>
                <p class="mt-1 text-2xl font-bold text-[#059669]">{{ queueStats.completed_today }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-5">
            <div class="rounded-2xl border p-5 lg:col-span-2" style="border-color: #E0F2FE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(2,132,199,0.09), 0 8px 20px rgba(2,132,199,0.08);">
                <p class="text-xs text-[#94A3B8]">Current Session</p>
                <template v-if="currentQueue">
                    <p class="mt-2 text-5xl font-bold text-[#1D4ED8]">{{ currentQueue.queue_code }}</p>
                    <p class="mt-1 text-sm text-[#1F2937]">{{ currentQueue.customer_name }}</p>
                    <p class="text-xs text-[#64748B]">{{ currentQueue.package_name }}</p>

                    <div class="mt-4 h-2 overflow-hidden rounded-full" style="background: #DBEAFE;">
                        <div class="h-full rounded-full transition-all duration-1000" :style="queueProgressStyle"></div>
                    </div>
                    <div class="mt-1 flex justify-between text-xs text-[#94A3B8]">
                        <span>Remaining {{ queueRemainingText }}</span>
                        <span>Total {{ queueSessionDurationText }}</span>
                    </div>

                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <button type="button" class="rounded-lg py-2 text-sm" style="background: #2563EB; color: #FFFFFF;">Call Next</button>
                        <button type="button" class="rounded-lg py-2 text-sm" style="background: #ECFDF5; color: #059669; border: 1px solid #A7F3D0;">Complete</button>
                    </div>
                </template>
                <p v-else class="mt-3 text-sm text-[#94A3B8]">No active queue session right now.</p>
            </div>

            <div class="rounded-2xl border lg:col-span-3" style="border-color: #E0F2FE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(2,132,199,0.09), 0 8px 20px rgba(2,132,199,0.08);">
                <div class="border-b px-5 py-4" style="border-color: #E0F2FE; background: #F0F9FF;">
                    <h3 class="text-sm font-semibold text-[#1F2937]">Waiting List</h3>
                </div>
                <div class="divide-y" style="border-color: #F8FAFC;">
                    <div v-for="(ticket, index) in waitingQueue" :key="`queue-module-ticket-${ticket.queue_code}-${index}`" class="flex items-center justify-between px-5 py-3.5">
                        <div class="flex items-center gap-3">
                            <span class="flex h-7 w-7 items-center justify-center rounded-lg text-xs" style="background: #EFF6FF; color: #2563EB;">{{ index + 1 }}</span>
                            <div>
                                <p class="text-sm text-[#1F2937]">{{ ticket.queue_code }}</p>
                                <p class="text-xs text-[#94A3B8]">{{ ticket.customer_name }}</p>
                            </div>
                        </div>
                        <span class="rounded-full px-2 py-0.5 text-xs" :style="{ background: resolveQueueStatus(ticket.status).bg, color: resolveQueueStatus(ticket.status).color }">{{ ticket.package_name }}</span>
                    </div>
                    <p v-if="!waitingQueue.length" class="px-5 py-10 text-center text-sm text-[#94A3B8]">Queue waiting list is currently empty.</p>
                </div>
            </div>
        </div>
    </div>
</template>
