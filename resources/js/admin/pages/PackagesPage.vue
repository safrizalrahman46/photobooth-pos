<script setup>
import { Sparkles } from 'lucide-vue-next';

defineProps({
    packageCards: { type: Array, default: () => [] },
    panelBaseUrl: { type: String, default: '/admin' },
    formatRupiah: { type: Function, required: true },
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(135deg, #0F766E 0%, #0D9488 52%, #14B8A6 100%); box-shadow: 0 6px 24px rgba(13,148,136,0.2);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-8 -top-8 h-36 w-36 rounded-full" style="background: rgba(153,246,228,0.18);"></div>
                <div class="absolute right-24 top-4 h-10 w-10 rounded-full" style="background: rgba(204,251,241,0.16);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="mb-1 flex items-center gap-2">
                        <Sparkles class="h-3.5 w-3.5" style="color: #99F6E4;" />
                        <span class="text-xs font-medium" style="color: #99F6E4;">Catalog management</span>
                    </div>
                    <h2 class="text-[1.35rem] font-bold text-white">Packages</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.72);">Manage package catalog, pricing, and active availability.</p>
                </div>
                <a :href="`${panelBaseUrl}/packages/create`" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #0F766E;">Add Package</a>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border p-4" style="border-color: #CCFBF1; background: #FFFFFF; box-shadow: 0 1px 3px rgba(13,148,136,0.08), 0 6px 18px rgba(13,148,136,0.08);">
                <p class="text-xs text-[#94A3B8]">Total Packages</p>
                <p class="mt-1 text-2xl font-bold text-[#1F2937]">{{ packageCards.length }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #CCFBF1; background: #FFFFFF; box-shadow: 0 1px 3px rgba(13,148,136,0.08), 0 6px 18px rgba(13,148,136,0.08);">
                <p class="text-xs text-[#94A3B8]">Total Bookings</p>
                <p class="mt-1 text-2xl font-bold text-[#1F2937]">{{ packageCards.reduce((sum, card) => sum + card.bookings, 0) }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #CCFBF1; background: #FFFFFF; box-shadow: 0 1px 3px rgba(13,148,136,0.08), 0 6px 18px rgba(13,148,136,0.08);">
                <p class="text-xs text-[#94A3B8]">Pending</p>
                <p class="mt-1 text-2xl font-bold text-[#D97706]">{{ packageCards.reduce((sum, card) => sum + card.pending, 0) }}</p>
            </div>
            <div class="rounded-2xl border p-4" style="border-color: #CCFBF1; background: #FFFFFF; box-shadow: 0 1px 3px rgba(13,148,136,0.08), 0 6px 18px rgba(13,148,136,0.08);">
                <p class="text-xs text-[#94A3B8]">Estimated Revenue</p>
                <p class="mt-1 text-xl font-bold text-[#059669]">{{ formatRupiah(packageCards.reduce((sum, card) => sum + card.revenue, 0)) }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="(pkg, index) in packageCards"
                :key="`package-card-${pkg.name}-${index}`"
                class="overflow-hidden rounded-2xl border"
                style="border-color: #CCFBF1; background: #FFFFFF; box-shadow: 0 1px 3px rgba(13,148,136,0.08), 0 6px 18px rgba(13,148,136,0.08);"
            >
                <div class="border-b px-5 py-4" style="border-color: #CCFBF1; background: #F0FDFA;">
                    <div class="mb-2 flex items-center justify-between">
                        <span class="rounded-full bg-white px-2 py-0.5 text-xs text-[#0F766E]">Package</span>
                        <span class="text-xs text-[#0F766E]">{{ pkg.bookings }} bookings</span>
                    </div>
                    <h3 class="text-base font-semibold text-[#1F2937]">{{ pkg.name }}</h3>
                    <p class="mt-0.5 text-sm text-[#0F766E]">{{ pkg.revenueText }}</p>
                </div>
                <div class="px-5 py-4">
                    <div class="mb-4 grid grid-cols-2 gap-2 text-xs">
                        <div class="rounded-lg px-3 py-2" style="background: #FFFBEB; color: #D97706;">Pending: {{ pkg.pending }}</div>
                        <div class="rounded-lg px-3 py-2" style="background: #ECFDF5; color: #059669;">Completed: {{ pkg.completed }}</div>
                    </div>
                    <a :href="`${panelBaseUrl}/packages`" class="inline-flex rounded-lg bg-[#E6FFFA] px-3 py-1.5 text-xs text-[#0F766E]">Open Package Module</a>
                </div>
            </div>

            <div v-if="!packageCards.length" class="col-span-full rounded-2xl border border-dashed p-8 text-center text-sm text-[#94A3B8]" style="border-color: #99F6E4;">
                No package data from current booking snapshot.
            </div>
        </div>
    </div>
</template>
