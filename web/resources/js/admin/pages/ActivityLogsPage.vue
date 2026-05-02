<script setup>
import { History, Search } from 'lucide-vue-next';

defineProps({
    activitySearch: { type: String, default: '' },
    activityModuleFilter: { type: String, default: 'all' },
    activityModuleOptions: { type: Array, default: () => [] },
    filteredActivityRows: { type: Array, default: () => [] },
    resolveActivityTone: { type: Function, required: true },
});

const emit = defineEmits(['update:activitySearch', 'update:activityModuleFilter']);

const formatModuleLabel = (value) => {
    return String(value || '')
        .replace(/[-_]/g, ' ')
        .split(' ')
        .filter(Boolean)
        .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
        .join(' ');
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-3xl px-6 py-5" style="background: linear-gradient(135deg, #1E293B 0%, #334155 52%, #475569 100%); box-shadow: 0 10px 28px rgba(15,23,42,0.24);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -left-10 top-0 h-32 w-32 rounded-full" style="background: rgba(148,163,184,0.16);"></div>
                <div class="absolute right-10 top-6 h-10 w-10 rounded-full" style="background: rgba(226,232,240,0.16);"></div>
            </div>
            <div class="relative flex items-center gap-3 text-white">
                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10">
                    <History class="h-5 w-5" />
                </div>
                <div>
                    <h2 class="text-[1.35rem] font-bold">History Perubahan</h2>
                    <p class="text-sm text-white/75">Riwayat perubahan modul dan aksi sistem dengan retensi 90 hari.</p>
                </div>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-3 rounded-3xl border p-4 lg:grid-cols-[1fr_auto]" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 10px 24px rgba(15,23,42,0.06);">
            <div class="relative">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-[#94A3B8]" />
                <input
                    :value="activitySearch"
                    type="text"
                    placeholder="Cari actor, modul, label, atau detail perubahan"
                    class="w-full rounded-xl border py-2.5 pl-9 pr-4 text-sm"
                    style="border-color: #E2E8F0;"
                    @input="emit('update:activitySearch', $event.target.value)"
                >
            </div>
            <div class="flex items-center gap-1.5 overflow-x-auto">
                <button
                    v-for="moduleOption in activityModuleOptions"
                    :key="`activity-module-option-${moduleOption}`"
                    type="button"
                    class="whitespace-nowrap rounded-xl px-3 py-2 text-xs font-medium"
                    :style="{
                        background: activityModuleFilter === moduleOption ? '#2563EB' : '#F8FAFC',
                        color: activityModuleFilter === moduleOption ? '#FFFFFF' : '#64748B',
                        border: `1px solid ${activityModuleFilter === moduleOption ? '#2563EB' : '#E2E8F0'}`,
                    }"
                    @click="emit('update:activityModuleFilter', moduleOption)"
                >
                    {{ moduleOption === 'all' ? 'Semua modul' : formatModuleLabel(moduleOption) }}
                </button>
            </div>
        </div>

        <div class="rounded-3xl border" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 10px 24px rgba(15,23,42,0.06);">
            <div class="max-h-[720px] divide-y overflow-y-auto" style="border-color: #F1F5F9;">
                <article v-for="activity in filteredActivityRows" :key="`activity-row-${activity.id}`" class="px-5 py-4">
                    <div class="flex gap-3">
                        <div
                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl text-xs font-bold"
                            :style="{ background: resolveActivityTone(activity.module_key || activity.module).bg, color: resolveActivityTone(activity.module_key || activity.module).color }"
                        >
                            {{ String(activity.module || 'H').charAt(0).toUpperCase() }}
                        </div>

                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] font-semibold text-[#475569]">{{ activity.module }}</span>
                                        <span class="rounded-full bg-[#EFF6FF] px-2.5 py-1 text-[0.68rem] font-semibold text-[#2563EB]">{{ activity.action }}</span>
                                        <span v-if="activity.label" class="rounded-full bg-[#F1F5F9] px-2.5 py-1 text-[0.68rem] font-semibold text-[#334155]">{{ activity.label }}</span>
                                    </div>
                                    <p class="mt-2 text-sm font-semibold text-[#0F172A]">{{ activity.message }}</p>
                                    <p class="mt-1 text-xs text-[#64748B]">{{ activity.actor }}</p>
                                </div>
                                <div class="text-right text-xs text-[#94A3B8]">
                                    <p>{{ activity.time }}</p>
                                    <p class="mt-1">{{ activity.time_text }}</p>
                                </div>
                            </div>

                            <div v-if="activity.details?.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="detail in activity.details" :key="`activity-detail-${activity.id}-${detail}`" class="rounded-full bg-[#F8FAFC] px-2.5 py-1 text-[0.68rem] text-[#64748B]">
                                    {{ detail }}
                                </span>
                            </div>

                            <div v-if="activity.changed_fields?.length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="field in activity.changed_fields" :key="`activity-field-${activity.id}-${field}`" class="rounded-full border px-2.5 py-1 text-[0.68rem] font-medium text-[#2563EB]" style="border-color: #DBEAFE; background: #EFF6FF;">
                                    {{ field }}
                                </span>
                            </div>
                        </div>
                    </div>
                </article>

                <p v-if="!filteredActivityRows.length" class="px-5 py-14 text-center text-sm text-[#94A3B8]">Belum ada history perubahan untuk filter yang dipilih.</p>
            </div>
        </div>
    </div>
</template>
