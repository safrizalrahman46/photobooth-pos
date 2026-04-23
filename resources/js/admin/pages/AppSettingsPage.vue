<script setup>
import { reactive, watch } from 'vue';

const props = defineProps({
    groups: {
        type: Object,
        default: () => ({
            general: {},
            booking: {},
            payment: {},
        }),
    },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
    successMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-app-settings', 'update-app-setting']);

const editor = reactive({
    general: '{}',
    booking: '{}',
    payment: '{}',
});

const syncEditor = () => {
    editor.general = JSON.stringify(props.groups?.general || {}, null, 2);
    editor.booking = JSON.stringify(props.groups?.booking || {}, null, 2);
    editor.payment = JSON.stringify(props.groups?.payment || {}, null, 2);
};

const submitGroup = (group) => {
    const key = String(group || '').trim();
    if (!key) return;

    const source = editor[key];
    let value = {};

    try {
        value = JSON.parse(source || '{}');
    } catch {
        window.alert(`Invalid JSON for ${key} settings.`);
        return;
    }

    emit('update-app-setting', { group: key, value });
};

watch(
    () => props.groups,
    () => {
        syncEditor();
    },
    { immediate: true, deep: true },
);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #0F172A 0%, #1E293B 58%, #334155 100%); box-shadow: 0 6px 24px rgba(15,23,42,0.24);">
            <div class="relative flex items-start justify-between gap-4">
                <div>
                    <h2 class="text-[1.35rem] font-bold text-white">App Settings</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.82);">Edit app-wide config groups: general, booking, and payment.</p>
                </div>
                <button type="button" class="rounded-xl border px-3 py-1.5 text-xs text-white" style="border-color: rgba(255,255,255,0.4);" :disabled="loading" @click="emit('refresh-app-settings')">
                    {{ loading ? 'Refreshing...' : 'Refresh' }}
                </button>
            </div>
        </section>

        <p v-if="errorMessage" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ errorMessage }}
        </p>
        <p v-if="successMessage" class="rounded-lg border px-3 py-2 text-sm" style="border-color: #A7F3D0; background: #ECFDF5; color: #047857;">
            {{ successMessage }}
        </p>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
                <h3 class="text-sm font-semibold text-[#1F2937]">General</h3>
                <textarea v-model="editor.general" rows="12" class="mt-2 w-full rounded-lg border px-3 py-2 text-xs font-mono" style="border-color: #CBD5E1;"></textarea>
                <button type="button" class="mt-2 rounded-xl bg-[#334155] px-3 py-1.5 text-xs text-white" :disabled="saving" @click="submitGroup('general')">
                    {{ saving ? 'Saving...' : 'Save General' }}
                </button>
            </div>

            <div class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
                <h3 class="text-sm font-semibold text-[#1F2937]">Booking</h3>
                <textarea v-model="editor.booking" rows="12" class="mt-2 w-full rounded-lg border px-3 py-2 text-xs font-mono" style="border-color: #CBD5E1;"></textarea>
                <button type="button" class="mt-2 rounded-xl bg-[#334155] px-3 py-1.5 text-xs text-white" :disabled="saving" @click="submitGroup('booking')">
                    {{ saving ? 'Saving...' : 'Save Booking' }}
                </button>
            </div>

            <div class="rounded-2xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
                <h3 class="text-sm font-semibold text-[#1F2937]">Payment</h3>
                <textarea v-model="editor.payment" rows="12" class="mt-2 w-full rounded-lg border px-3 py-2 text-xs font-mono" style="border-color: #CBD5E1;"></textarea>
                <button type="button" class="mt-2 rounded-xl bg-[#334155] px-3 py-1.5 text-xs text-white" :disabled="saving" @click="submitGroup('payment')">
                    {{ saving ? 'Saving...' : 'Save Payment' }}
                </button>
            </div>
        </section>
    </div>
</template>

