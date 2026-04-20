<script setup>
import { Search } from 'lucide-vue-next';

defineProps({
    activitySearch: { type: String, default: '' },
    activityModuleFilter: { type: String, default: 'all' },
    activityModuleOptions: { type: Array, default: () => [] },
    filteredActivityRows: { type: Array, default: () => [] },
    resolveActivityTone: { type: Function, required: true },
});

const emit = defineEmits(['update:activitySearch', 'update:activityModuleFilter']);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #431407 0%, #7C2D12 58%, #9A3412 100%); box-shadow: 0 6px 24px rgba(124,45,18,0.28);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-8 top-0 h-32 w-32 rounded-full" style="background: rgba(254,215,170,0.2);"></div>
                <div class="absolute right-10 top-6 h-8 w-8 rounded-full" style="background: rgba(255,237,213,0.2);"></div>
            </div>
            <div class="relative">
                <h2 class="text-[1.35rem] font-bold text-white">Activity Logs</h2>
                <p class="text-sm" style="color: rgba(255,255,255,0.8);">Audit trail of system actions across modules.</p>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 rounded-2xl border p-4 lg:grid-cols-[1fr_auto]" style="border-color: #FED7AA; background: #FFFFFF; box-shadow: 0 1px 3px rgba(154,52,18,0.09), 0 8px 20px rgba(154,52,18,0.08);">
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" />
                <input
                    :value="activitySearch"
                    type="text"
                    placeholder="Search actor, action, or module"
                    class="w-full rounded-lg border py-2 pl-9 pr-4 text-sm"
                    style="border-color: #E2E8F0;"
                    @input="emit('update:activitySearch', $event.target.value)"
                >
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto">
                <button
                    v-for="moduleOption in activityModuleOptions"
                    :key="`activity-module-option-${moduleOption}`"
                    type="button"
                    class="whitespace-nowrap rounded-lg px-3 py-1.5 text-xs"
                    :style="{
                        background: activityModuleFilter === moduleOption ? '#2563EB' : '#F8FAFC',
                        color: activityModuleFilter === moduleOption ? '#FFFFFF' : '#64748B',
                        border: `1px solid ${activityModuleFilter === moduleOption ? '#2563EB' : '#EEF2FF'}`,
                    }"
                    @click="emit('update:activityModuleFilter', moduleOption)"
                >
                    {{ moduleOption === 'all' ? 'All modules' : moduleOption }}
                </button>
            </div>
        </div>

        <div class="rounded-2xl border" style="border-color: #FED7AA; background: #FFFFFF; box-shadow: 0 1px 3px rgba(154,52,18,0.09), 0 8px 20px rgba(154,52,18,0.08);">
            <div class="divide-y" style="border-color: #F8FAFC;">
                <div v-for="(activity, index) in filteredActivityRows" :key="`activity-module-row-${activity.id}-${index}`" class="flex gap-3 px-5 py-4">
                    <div class="relative shrink-0">
                        <div
                            class="flex h-9 w-9 items-center justify-center rounded-xl text-xs font-bold"
                            :style="{ background: resolveActivityTone(activity.module).bg, color: resolveActivityTone(activity.module).color }"
                        >
                            {{ String(activity.module || 'A').charAt(0).toUpperCase() }}
                        </div>
                        <div v-if="index < filteredActivityRows.length - 1" class="absolute left-4 top-9 w-px" style="height: 24px; background: #F1F5F9;"></div>
                    </div>
                    <div class="flex-1 pb-1">
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm text-[#1F2937]">{{ activity.action }}</p>
                            <span class="text-xs text-[#CBD5E1]">{{ activity.time }}</span>
                        </div>
                        <p class="mt-0.5 text-xs text-[#94A3B8]">{{ activity.actor }} · {{ activity.module }}</p>
                    </div>
                </div>

                <p v-if="!filteredActivityRows.length" class="px-5 py-12 text-center text-sm text-[#94A3B8]">No activity found for selected filter.</p>
            </div>
        </div>
    </div>
</template>
