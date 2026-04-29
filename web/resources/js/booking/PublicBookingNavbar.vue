<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    routes: {
        type: Object,
        required: true,
    },
    site: {
        type: Object,
        default: () => ({}),
    },
});

const mobileOpen = ref(false);

const links = computed(() => {
    return [
        {
            key: 'book',
            href: props.routes.booking || props.routes.back || '/booking',
            label: 'Book',
        },
        {
            key: 'admin',
            href: props.routes.admin || '/admin',
            label: 'Admin',
        },
        {
            key: 'queue',
            href: props.routes.queueBoard || '/queue-board',
            label: 'Queue',
        },
    ];
});

const logoSrc = computed(() => props.site.logo_url || props.routes.logo || '/favicon.ico');
const brandName = computed(() => props.site.brand_name || 'Ready to Pict');
const shortName = computed(() => props.site.short_name || 'Studio');

const currentPath = computed(() => window.location.pathname);

const isActive = (href) => {
    if (!href || href === '#') {
        return false;
    }

    try {
        const url = new URL(href, window.location.origin);
        return url.pathname === currentPath.value;
    } catch {
        return href === currentPath.value;
    }
};
</script>

<template>
    <nav class="sticky top-0 z-50 border-b border-slate-300 bg-white/80 backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6">
            <a :href="props.routes.landing || '/'" class="flex items-center gap-2.5">
                <img :src="logoSrc" alt="Ready to Pict" class="h-12 w-12 rounded-full object-cover ring-2 ring-[#2563EB]/30 ring-offset-1">
                <div class="flex flex-col leading-tight">
                    <span class="text-[#1F2937]" style="font-size: 1rem; font-weight: 700;">
                        {{ brandName }}
                    </span>
                    <span class="text-[0.6rem] uppercase tracking-widest text-gray-400">{{ shortName }}</span>
                </div>
            </a>

            <div class="hidden items-center gap-1 md:flex">
                <a
                    v-for="link in links"
                    :key="link.key"
                    :href="link.href"
                    class="rounded-lg px-4 py-2 text-sm transition-colors"
                    :class="isActive(link.href)
                        ? 'bg-[#2563EB]/10 text-[#2563EB]'
                        : 'text-gray-600 hover:bg-gray-50 hover:text-[#1F2937]'"
                    :style="{ fontWeight: isActive(link.href) ? 600 : 400 }"
                >
                    {{ link.label }}
                </a>
            </div>

            <button
                type="button"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-600 hover:bg-gray-50 md:hidden"
                @click="mobileOpen = !mobileOpen"
            >
                <svg v-if="mobileOpen" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 6L6 18" />
                    <path d="M6 6l12 12" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3 6h18" />
                    <path d="M3 12h18" />
                    <path d="M3 18h18" />
                </svg>
            </button>
        </div>

        <div v-if="mobileOpen" class="border-t border-gray-100 bg-white px-4 pb-4 md:hidden">
            <a
                v-for="link in links"
                :key="`mobile-${link.key}`"
                :href="link.href"
                class="block rounded-lg px-4 py-2.5 text-sm transition-colors"
                :class="isActive(link.href)
                    ? 'bg-[#2563EB]/10 text-[#2563EB]'
                    : 'text-gray-600'"
                :style="{ fontWeight: isActive(link.href) ? 600 : 400 }"
                @click="mobileOpen = false"
            >
                {{ link.label }}
            </a>
        </div>
    </nav>
</template>
