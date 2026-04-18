<script setup>
import { Sparkles } from 'lucide-vue-next';

defineProps({
    designCards: { type: Array, default: () => [] },
    panelBaseUrl: { type: String, default: '/admin' },
});
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(135deg, #1E3A8A 0%, #1D4ED8 52%, #2563EB 100%); box-shadow: 0 6px 24px rgba(37,99,235,0.2);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-8 -top-8 h-36 w-36 rounded-full" style="background: rgba(147,197,253,0.2);"></div>
                <div class="absolute right-24 top-4 h-10 w-10 rounded-full" style="background: rgba(191,219,254,0.18);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="mb-1 flex items-center gap-2">
                        <Sparkles class="h-3.5 w-3.5" style="color: #BFDBFE;" />
                        <span class="text-xs font-medium" style="color: #BFDBFE;">Theme catalogs</span>
                    </div>
                    <h2 class="text-[1.35rem] font-bold text-white">Design Catalogs</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.72);">Visual templates used by each package and campaign.</p>
                </div>
                <a :href="`${panelBaseUrl}/design-catalogs/create`" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #1D4ED8;">Add Design</a>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="(design, index) in designCards"
                :key="design.id"
                class="overflow-hidden rounded-2xl border"
                style="border-color: #DBEAFE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.08), 0 6px 18px rgba(37,99,235,0.08);"
            >
                <div class="h-36" :style="{ background: `linear-gradient(135deg, ${design.tone.accent} 0%, ${design.tone.border} 100%)` }"></div>
                <div class="space-y-3 p-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-[#1F2937]">{{ design.name }}</h3>
                        <span
                            class="rounded-full px-2 py-0.5 text-xs"
                            :style="design.status === 'active' ? { background: '#ECFDF5', color: '#059669' } : { background: '#FFFBEB', color: '#D97706' }"
                        >
                            {{ design.status }}
                        </span>
                    </div>
                    <p class="text-xs text-[#94A3B8]">{{ design.bookings }} bookings using this design</p>
                    <div class="flex items-center justify-between text-xs text-[#64748B]">
                        <span>Updated {{ design.updated }}</span>
                        <a :href="`${panelBaseUrl}/design-catalogs`" class="text-[#2563EB]">Manage</a>
                    </div>
                </div>
            </article>

            <p v-if="!designCards.length" class="col-span-full rounded-xl border border-dashed p-8 text-center text-sm text-[#94A3B8]" style="border-color: #93C5FD;">
                No design data available.
            </p>
        </div>
    </div>
</template>
