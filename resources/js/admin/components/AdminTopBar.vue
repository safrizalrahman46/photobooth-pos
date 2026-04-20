<script setup>
import { Bell, Menu, Search, Settings, X } from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    dateLabel: {
        type: String,
        default: '',
    },
    showTopSearch: {
        type: Boolean,
        default: false,
    },
    topSearchValue: {
        type: String,
        default: '',
    },
});

const emit = defineEmits(['open-mobile', 'toggle-top-search', 'update:topSearchValue']);
</script>

<template>
    <header
        class="sticky top-0 z-10 flex items-center gap-3 px-5 py-0"
        style="background: #FFFFFF; border-bottom: 1px solid #EEF2FF; backdrop-filter: blur(12px); min-height: 64px; box-shadow: 0 1px 8px rgba(37,99,235,0.05);"
    >
        <button
            type="button"
            class="rounded-xl bg-[#F1F5F9] p-2 text-slate-600 lg:hidden"
            @click="emit('open-mobile')"
        >
            <Menu class="h-4 w-4" />
        </button>

        <div class="min-w-0 flex-1">
            <h1 class="truncate text-base font-bold leading-tight text-[#0F172A]" style="font-family: Poppins, sans-serif;">{{ title }}</h1>
        </div>

        <div class="hidden items-center rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] px-3 py-1.5 lg:flex">
            <span class="text-[0.7rem] text-[#94A3B8]">{{ dateLabel }}</span>
        </div>

        <div class="relative">
            <div
                v-if="showTopSearch"
                class="flex items-center gap-2 rounded-xl border-[1.5px] border-[#2563EB] bg-[#F8FAFC] px-3 py-2"
                style="width: 220px;"
            >
                <Search class="h-3.5 w-3.5 shrink-0 text-[#2563EB]" />
                <input
                    :value="topSearchValue"
                    type="text"
                    autofocus
                    placeholder="Search..."
                    class="flex-1 bg-transparent text-[0.8rem] text-slate-800 outline-none placeholder:text-slate-400"
                    @input="emit('update:topSearchValue', $event.target.value)"
                    @blur="emit('toggle-top-search', false)"
                >
                <button type="button" class="text-slate-400" @click="emit('update:topSearchValue', '')">
                    <X class="h-3 w-3" />
                </button>
            </div>

            <button
                v-else
                type="button"
                class="flex items-center gap-2 rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] px-3 py-2 text-[#64748B]"
                @click="emit('toggle-top-search', true)"
            >
                <Search class="h-3.5 w-3.5" />
                <span class="hidden text-[0.75rem] sm:inline">Search</span>
            </button>
        </div>

        <button type="button" class="relative rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] p-2.5 text-[#64748B]">
            <Bell class="h-4 w-4" />
            <span class="absolute right-1.5 top-1.5 h-2 w-2 rounded-full bg-[#EF4444]" style="box-shadow: 0 0 0 2px white;"></span>
        </button>

        <button type="button" class="hidden rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] p-2.5 text-[#64748B] md:flex">
            <Settings class="h-4 w-4" />
        </button>
    </header>
</template>
