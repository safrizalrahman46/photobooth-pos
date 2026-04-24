<script setup>
import { Camera, ChevronLeft, ChevronRight, LogOut, X } from 'lucide-vue-next';

const props = defineProps({
    navItems: {
        type: Array,
        default: () => [],
    },
    navGroups: {
        type: Array,
        default: () => [],
    },
    activeModuleId: {
        type: String,
        default: 'dashboard',
    },
    mobileOpen: {
        type: Boolean,
        default: false,
    },
    sidebarCollapsed: {
        type: Boolean,
        default: false,
    },
    brandName: {
        type: String,
        default: 'Dashboard',
    },
    dashboardLabel: {
        type: String,
        default: 'Dashboard',
    },
    currentUser: {
        type: Object,
        default: () => ({
            name: 'User',
            initials: 'US',
            roleLabel: 'User',
        }),
    },
});

const emit = defineEmits(['toggle-mobile', 'toggle-collapse', 'logout', 'navigate']);

const isActive = (itemId) => String(itemId || '') === String(props.activeModuleId || 'dashboard');
const itemForGroup = (groupKey) => props.navItems.filter((item) => item.group === groupKey);

const shouldHandleClientNavigate = (event, href) => {
    if (!event || event.defaultPrevented) {
        return false;
    }

    if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
        return false;
    }

    const rawHref = String(href || '').trim();

    if (!rawHref) {
        return false;
    }

    if (rawHref.startsWith('/admin')) {
        return true;
    }

    try {
        const url = new URL(rawHref, window.location.origin);

        return url.origin === window.location.origin && url.pathname.startsWith('/admin');
    } catch {
        return false;
    }
};

const handleNavClick = (event, item) => {
    if (!shouldHandleClientNavigate(event, item?.href)) {
        return;
    }

    event.preventDefault();
    emit('navigate', String(item?.href || '/admin'));
};
</script>

<template>
    <div>
        <div
            v-if="mobileOpen"
            class="fixed inset-0 z-20 lg:hidden"
            style="background: rgba(15,23,42,0.4); backdrop-filter: blur(4px);"
            @click="emit('toggle-mobile')"
        ></div>

        <aside
            class="fixed inset-y-0 left-0 z-30 flex h-[100dvh] max-h-[100dvh] w-[240px] flex-col overflow-hidden border-r border-[#EEF2FF] bg-white shadow-[2px_0_16px_rgba(37,99,235,0.06)] transition-all duration-300 lg:relative lg:h-screen lg:max-h-screen"
            :class="[
                mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-[240px]',
            ]"
        >
            <div class="relative flex shrink-0 items-center px-4 py-5" style="min-height: 72px;">
                <div
                    class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                    style="background: linear-gradient(135deg, #2563EB 0%, #60A5FA 100%); box-shadow: 0 4px 12px rgba(37,99,235,0.3);"
                >
                    <Camera class="h-4 w-4 text-white" />
                </div>

                <div v-if="!sidebarCollapsed" class="ml-3 min-w-0 flex-1 overflow-hidden">
                    <p class="whitespace-nowrap text-[0.875rem] font-bold tracking-[-0.01em]" style="font-family: Poppins, sans-serif; color: #1E3A8A;">
                        {{ brandName }}
                    </p>
                    <span class="whitespace-nowrap text-xs font-medium" style="color: #60A5FA;">{{ dashboardLabel }}</span>
                </div>

                <button
                    type="button"
                    class="hidden h-6 w-6 items-center justify-center rounded-lg transition-all duration-200 lg:flex"
                    :style="{ background: '#EFF6FF', color: '#2563EB', marginLeft: sidebarCollapsed ? 'auto' : undefined }"
                    @click="emit('toggle-collapse')"
                >
                    <ChevronRight v-if="sidebarCollapsed" class="h-3.5 w-3.5" />
                    <ChevronLeft v-else class="h-3.5 w-3.5" />
                </button>

                <button
                    type="button"
                    class="ml-auto flex h-7 w-7 items-center justify-center rounded-lg text-slate-500 lg:hidden"
                    @click="emit('toggle-mobile')"
                >
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div class="relative mx-4 h-px shrink-0 bg-[#EEF2FF]"></div>

            <nav class="relative min-h-0 flex-1 overflow-y-auto overscroll-contain px-3 py-4" style="-webkit-overflow-scrolling: touch;">
                <div v-for="group in navGroups" :key="`nav-group-${group.key}`" class="mb-1">
                    <p
                        v-if="!sidebarCollapsed"
                        class="px-3 pb-1.5 pt-3 text-[0.62rem] font-semibold uppercase tracking-widest"
                        style="color: #CBD5E1;"
                    >
                        {{ group.label }}
                    </p>

                    <div v-else-if="group.key !== 'overview'" class="mx-auto my-2 h-px w-6 bg-[#EEF2FF]"></div>

                    <a
                        v-for="item in itemForGroup(group.key)"
                        :key="`nav-item-${item.id}`"
                        :href="item.href"
                        :title="sidebarCollapsed ? item.label : undefined"
                        class="relative mb-[2px] flex w-full items-center rounded-xl transition-all duration-200"
                        :class="[
                            sidebarCollapsed ? 'h-10 justify-center px-0 py-2.5' : 'gap-3 px-3 py-2.5',
                            item.blink ? 'rtp-item-blink' : '',
                        ]"
                        :style="{
                            background: isActive(item.id) ? '#EFF6FF' : 'transparent',
                            color: isActive(item.id) ? '#2563EB' : '#64748B',
                            animationDuration: item.blink_duration || undefined,
                        }"
                        @click="handleNavClick($event, item)"
                    >
                        <span
                            v-if="isActive(item.id)"
                            class="absolute left-0 top-1/2 h-5 w-[3px] -translate-y-1/2 rounded-r-full bg-[#2563EB]"
                        ></span>

                        <span
                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :style="{ background: isActive(item.id) ? '#DBEAFE' : 'transparent' }"
                        >
                            <component :is="item.icon" class="h-4 w-4" />
                        </span>

                        <span
                            v-if="!sidebarCollapsed"
                            class="flex-1 whitespace-nowrap text-left text-[0.8125rem]"
                            :style="{ fontFamily: 'Poppins, sans-serif', fontWeight: isActive(item.id) ? 600 : 400 }"
                        >
                            {{ item.label }}
                        </span>

                        <span
                            v-if="!sidebarCollapsed && item.badge"
                            class="flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-[#FEE2E2] px-1 text-[0.65rem] font-bold text-[#EF4444]"
                        >
                            {{ item.badge }}
                        </span>

                        <span
                            v-if="sidebarCollapsed && item.blink"
                            class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full bg-[#EF4444]"
                        ></span>
                    </a>
                </div>
            </nav>

            <div class="relative shrink-0 border-t border-[#EEF2FF] p-3">
                <div class="flex items-center" :class="sidebarCollapsed ? 'justify-center' : 'gap-2.5 rounded-xl border border-[#EEF2FF] bg-[#F8FAFC] p-2.5'">
                    <div
                        class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-[0.7rem] font-bold text-white"
                        style="background: linear-gradient(135deg, #2563EB, #60A5FA);"
                    >
                        {{ currentUser.initials || 'US' }}
                    </div>

                    <div v-if="!sidebarCollapsed" class="min-w-0 flex-1">
                        <p class="truncate text-[0.8rem] font-semibold leading-tight" style="color: #1F2937;">{{ currentUser.name || 'User' }}</p>
                        <p class="truncate text-[0.7rem]" style="color: #94A3B8;">{{ currentUser.roleLabel || 'User' }}</p>
                    </div>

                    <button
                        v-if="!sidebarCollapsed"
                        type="button"
                        class="rounded-lg p-1.5 text-[#64748B]"
                        aria-label="Logout"
                        @click="emit('logout')"
                    >
                        <LogOut class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </aside>
    </div>
</template>

<style scoped>
.rtp-item-blink {
    animation-name: rtp-item-blink;
    animation-iteration-count: infinite;
    animation-timing-function: ease-in-out;
}

@keyframes rtp-item-blink {
    0%,
    40%,
    100% {
        box-shadow: inset 0 0 0 0 rgba(239, 68, 68, 0);
    }

    60% {
        box-shadow: inset 0 0 0 999px rgba(239, 68, 68, 0.08);
    }
}
</style>
