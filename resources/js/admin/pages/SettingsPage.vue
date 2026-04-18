<script setup>
defineProps({
    settingsTab: { type: String, default: 'business' },
    settingsTabs: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:settingsTab']);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #3F3F46 0%, #52525B 58%, #71717A 100%); box-shadow: 0 6px 24px rgba(63,63,70,0.24);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full" style="background: rgba(228,228,231,0.2);"></div>
                <div class="absolute left-8 top-4 h-8 w-8 rounded-full" style="background: rgba(244,244,245,0.2);"></div>
            </div>
            <div class="relative">
                <h2 class="text-[1.35rem] font-bold text-white">Settings</h2>
                <p class="text-sm" style="color: rgba(255,255,255,0.8);">Business and operational preferences for owner module.</p>
            </div>
        </section>

        <div class="grid grid-cols-1 gap-5 lg:grid-cols-[220px_1fr]">
            <div class="overflow-hidden rounded-2xl border" style="border-color: #E4E4E7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(63,63,70,0.09), 0 8px 20px rgba(63,63,70,0.08);">
                <button
                    v-for="tab in settingsTabs"
                    :key="`settings-tab-${tab.id}`"
                    type="button"
                    class="w-full border-b px-4 py-3 text-left text-sm"
                    :style="{
                        borderColor: '#F1F5F9',
                        background: settingsTab === tab.id ? '#EFF6FF' : '#FFFFFF',
                        color: settingsTab === tab.id ? '#2563EB' : '#64748B',
                    }"
                    @click="emit('update:settingsTab', tab.id)"
                >
                    {{ tab.label }}
                </button>
            </div>

            <div class="rounded-2xl border p-5" style="border-color: #E4E4E7; background: #FFFFFF; box-shadow: 0 1px 3px rgba(63,63,70,0.09), 0 8px 20px rgba(63,63,70,0.08);">
                <div v-if="settingsTab === 'business'" class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm text-[#374151]">Business Name</label>
                        <input type="text" value="Ready To Pict" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-[#374151]">Business Email</label>
                        <input type="email" value="hello@readytopict.id" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-[#374151]">Address</label>
                        <textarea rows="3" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;">Jl. Photobooth No. 123, Jakarta</textarea>
                    </div>
                </div>

                <div v-else-if="settingsTab === 'hours'" class="space-y-3">
                    <p class="text-sm text-[#64748B]">Operating hours by day</p>
                    <div v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']" :key="`hours-${day}`" class="grid grid-cols-[120px_1fr] items-center gap-3 rounded-lg border px-3 py-2" style="border-color: #EEF2FF;">
                        <span class="text-sm text-[#374151]">{{ day }}</span>
                        <div class="flex items-center gap-2">
                            <input type="time" value="10:00" class="rounded border px-2 py-1 text-xs" style="border-color: #E2E8F0;" >
                            <span class="text-xs text-[#94A3B8]">to</span>
                            <input type="time" value="21:00" class="rounded border px-2 py-1 text-xs" style="border-color: #E2E8F0;" >
                        </div>
                    </div>
                </div>

                <div v-else-if="settingsTab === 'payment'" class="space-y-3">
                    <p class="text-sm text-[#64748B]">Accepted methods</p>
                    <div v-for="method in ['QRIS', 'Cash', 'Transfer']" :key="`payment-${method}`" class="flex items-center justify-between rounded-lg border px-3 py-2" style="border-color: #EEF2FF;">
                        <span class="text-sm text-[#374151]">{{ method }}</span>
                        <span class="rounded-full bg-[#ECFDF5] px-2 py-0.5 text-xs text-[#059669]">Enabled</span>
                    </div>
                    <div class="rounded-lg border px-3 py-2" style="border-color: #EEF2FF;">
                        <label class="mb-1 block text-xs text-[#94A3B8]">Minimum DP (%)</label>
                        <input type="range" min="10" max="100" value="50" class="w-full" >
                    </div>
                </div>

                <div v-else-if="settingsTab === 'notifications'" class="space-y-3">
                    <div v-for="label in ['New Booking', 'Payment Received', 'Booking Cancelled', 'Daily Summary']" :key="`notif-${label}`" class="flex items-center justify-between rounded-lg border px-3 py-2" style="border-color: #EEF2FF;">
                        <span class="text-sm text-[#374151]">{{ label }}</span>
                        <span class="rounded-full bg-[#EFF6FF] px-2 py-0.5 text-xs text-[#2563EB]">On</span>
                    </div>
                </div>

                <div v-else class="space-y-3">
                    <div>
                        <label class="mb-1 block text-sm text-[#374151]">Current Password</label>
                        <input type="password" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
                    </div>
                    <div>
                        <label class="mb-1 block text-sm text-[#374151]">New Password</label>
                        <input type="password" class="w-full rounded-lg border px-3 py-2 text-sm" style="border-color: #E2E8F0;" >
                    </div>
                    <div class="rounded-lg border px-3 py-3 text-xs" style="border-color: #FECACA; background: #FEF2F2; color: #DC2626;">
                        Danger Zone: reset all settings and sessions.
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    <button type="button" class="rounded-xl bg-[#2563EB] px-5 py-2 text-sm text-white">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
</template>
