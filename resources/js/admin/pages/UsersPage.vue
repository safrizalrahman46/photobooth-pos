<script setup>
import { computed } from 'vue';
import { Shield, UserRound, UsersRound } from 'lucide-vue-next';

const props = defineProps({
    userRows: { type: Array, default: () => [] },
    initialsFromName: { type: Function, required: true },
    panelBaseUrl: { type: String, default: '/admin' },
});

const ownerCount = computed(() => props.userRows.filter((item) => String(item.role || '').toLowerCase() === 'owner').length);
const cashierCount = computed(() => props.userRows.filter((item) => String(item.role || '').toLowerCase() === 'cashier').length);
const activeCount = computed(() => props.userRows.filter((item) => String(item.status || '').toLowerCase() === 'active').length);

const roleTone = (role) => {
    const normalized = String(role || '').toLowerCase();

    if (normalized === 'owner') {
        return { bg: '#F5F3FF', color: '#7C3AED' };
    }

    if (normalized === 'cashier') {
        return { bg: '#EFF6FF', color: '#2563EB' };
    }

    return { bg: '#F8FAFC', color: '#64748B' };
};
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(135deg, #0F172A 0%, #1E293B 58%, #334155 100%); box-shadow: 0 6px 24px rgba(15,23,42,0.25);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-8 -top-8 h-36 w-36 rounded-full" style="background: rgba(148,163,184,0.22);"></div>
                <div class="absolute right-24 top-4 h-10 w-10 rounded-full" style="background: rgba(203,213,225,0.18);"></div>
            </div>
            <div class="relative flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">Users</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.72);">Owner, cashier, and staff accounts overview.</p>
                </div>
                <a :href="`${panelBaseUrl}/users/create`" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #0F172A;">Invite User</a>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Total Users</p>
                <p class="mt-1 text-2xl font-bold text-[#0F172A]">{{ userRows.length }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Owner</p>
                <p class="mt-1 text-2xl font-bold text-[#7C3AED]">{{ ownerCount }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Cashier</p>
                <p class="mt-1 text-2xl font-bold text-[#2563EB]">{{ cashierCount }}</p>
            </article>
            <article class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
                <p class="text-xs text-[#94A3B8]">Active</p>
                <p class="mt-1 text-2xl font-bold text-[#059669]">{{ activeCount }}</p>
            </article>
        </section>

        <div class="overflow-hidden rounded-2xl border" style="border-color: #E2E8F0; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06), 0 6px 18px rgba(15,23,42,0.06);">
            <table class="w-full">
                <thead>
                    <tr style="border-bottom: 1px solid #E2E8F0; background: #F8FAFC;">
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Name</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Role</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Status</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Source</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(user, index) in userRows" :key="`user-row-${user.name}-${index}`" style="border-bottom: 1px solid #F8FAFC;">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <div class="flex h-8 w-8 items-center justify-center rounded-xl text-xs text-white" style="background: linear-gradient(135deg, #2563EB, #60A5FA);">
                                    {{ initialsFromName(user.name) }}
                                </div>
                                <span class="text-sm text-[#1F2937]">{{ user.name }}</span>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-sm text-[#64748B]">
                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs" :style="{ background: roleTone(user.role).bg, color: roleTone(user.role).color }">
                                <Shield v-if="String(user.role || '').toLowerCase() === 'owner'" class="h-3 w-3" />
                                <UserRound v-else-if="String(user.role || '').toLowerCase() === 'cashier'" class="h-3 w-3" />
                                <UsersRound v-else class="h-3 w-3" />
                                {{ user.role }}
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            <span class="rounded-full px-2 py-0.5 text-xs" :style="String(user.status || '').toLowerCase() === 'active' ? { background: '#ECFDF5', color: '#059669' } : { background: '#F8FAFC', color: '#64748B' }">{{ user.status }}</span>
                        </td>
                        <td class="px-5 py-3.5 text-xs text-[#94A3B8]">{{ user.source }}</td>
                    </tr>

                    <tr v-if="!userRows.length">
                        <td colspan="4" class="px-5 py-10 text-center text-sm text-[#94A3B8]">No user data found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
