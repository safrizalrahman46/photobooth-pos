<script setup>
import { computed, reactive, ref } from 'vue';
import { Camera, Clock3, Pencil, Plus, RefreshCw, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    packageCards: { type: Array, default: () => [] },
    panelBaseUrl: { type: String, default: '/admin' },
    formatRupiah: { type: Function, required: true },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingPackageId: { type: [Number, String, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-packages', 'create-package', 'update-package', 'delete-package']);

const cardTones = [
    {
        tint: '#DBEAFE',
        dot: 'rgba(37,99,235,0.18)',
        iconBg: '#BFDBFE',
        iconColor: '#2563EB',
        accent: '#2563EB',
        border: '#D6E4FF',
        badgeBg: '#E0EAFF',
        badgeColor: '#2563EB',
    },
    {
        tint: '#EDE9FE',
        dot: 'rgba(124,58,237,0.16)',
        iconBg: '#DDD6FE',
        iconColor: '#7C3AED',
        accent: '#7C3AED',
        border: '#E4D5FF',
        badgeBg: '#7C3AED',
        badgeColor: '#FFFFFF',
    },
    {
        tint: '#FEF3C7',
        dot: 'rgba(217,119,6,0.16)',
        iconBg: '#FDE68A',
        iconColor: '#D97706',
        accent: '#D97706',
        border: '#F9DFA1',
        badgeBg: '#FFE6B3',
        badgeColor: '#D97706',
    },
    {
        tint: '#D1FAE5',
        dot: 'rgba(16,185,129,0.16)',
        iconBg: '#A7F3D0',
        iconColor: '#10B981',
        accent: '#47B990',
        border: '#BFEFD9',
        badgeBg: '#E6F7EF',
        badgeColor: '#8FA3A0',
    },
];

const modalOpen = ref(false);
const modalMode = ref('create');
const editingPackageId = ref(null);
const localError = ref('');

const form = reactive({
    name: '',
    description: '',
    duration_minutes: 30,
    base_price: 0,
    is_active: true,
    sort_order: 0,
});

const toFeatureList = (description) => {
    const raw = String(description || '')
        .replace(/\r/g, '\n')
        .split('\n')
        .flatMap((line) => line.split(/[,;|]/))
        .map((line) => line.replace(/^[-*•\s]+/, '').trim())
        .filter(Boolean);

    return Array.from(new Set(raw));
};

const packageItems = computed(() => {
    return props.packageCards.map((pkg, index) => {
        const tone = cardTones[index % cardTones.length];
        const features = toFeatureList(pkg.description);

        return {
            ...pkg,
            tone,
            features,
            isPopular: index === 0 && Number(pkg.bookings || 0) > 0,
            thisMonthBookings: Number(pkg.bookings || 0),
            totalBookings: Number(pkg.total_bookings || 0),
        };
    });
});

const stats = computed(() => {
    const total = packageItems.value.length;
    const active = packageItems.value.filter((item) => Boolean(item.is_active)).length;
    const bookingsThisMonth = packageItems.value.reduce((sum, item) => sum + Number(item.thisMonthBookings || 0), 0);

    const mostPopular = packageItems.value
        .slice()
        .sort((left, right) => Number(right.thisMonthBookings || 0) - Number(left.thisMonthBookings || 0))[0]?.name || '-';

    return {
        total,
        active,
        bookingsThisMonth,
        mostPopular,
    };
});

const resetForm = () => {
    form.name = '';
    form.description = '';
    form.duration_minutes = 30;
    form.base_price = 0;
    form.is_active = true;
    form.sort_order = 0;
    editingPackageId.value = null;
    localError.value = '';
};

const openCreateModal = () => {
    resetForm();
    modalMode.value = 'create';
    modalOpen.value = true;
};

const openEditModal = (pkg) => {
    modalMode.value = 'edit';
    editingPackageId.value = Number(pkg.id || 0);
    form.name = String(pkg.name || '');
    form.description = String(pkg.description || '');
    form.duration_minutes = Number(pkg.duration_minutes || 30);
    form.base_price = Number(pkg.base_price || 0);
    form.is_active = Boolean(pkg.is_active);
    form.sort_order = Number(pkg.sort_order || 0);
    localError.value = '';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    localError.value = '';
};

const validateForm = () => {
    if (!String(form.name || '').trim()) {
        localError.value = 'Package name is required.';
        return false;
    }

    if (Number(form.duration_minutes || 0) < 5) {
        localError.value = 'Duration must be at least 5 minutes.';
        return false;
    }

    if (Number(form.base_price || 0) < 0) {
        localError.value = 'Base price cannot be negative.';
        return false;
    }

    localError.value = '';
    return true;
};

const submitForm = async () => {
    if (!validateForm()) {
        return;
    }

    const payload = {
        name: String(form.name || '').trim(),
        description: String(form.description || '').trim(),
        duration_minutes: Number(form.duration_minutes || 0),
        base_price: Number(form.base_price || 0),
        is_active: Boolean(form.is_active),
        sort_order: Number(form.sort_order || 0),
    };

    try {
        if (modalMode.value === 'create') {
            await emit('create-package', payload);
        } else {
            await emit('update-package', {
                id: editingPackageId.value,
                payload,
            });
        }

        modalOpen.value = false;
    } catch {
        // Error message is handled by parent and shown in-page.
    }
};

const requestDelete = async (pkg) => {
    const packageName = String(pkg.name || 'this package');
    const confirmed = window.confirm(`Delete ${packageName}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    try {
        await emit('delete-package', Number(pkg.id || 0));
    } catch {
        // Error message is handled by parent and shown in-page.
    }
};
</script>

<template>
    <div class="space-y-5">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-[1.35rem] font-bold text-[#0F172A]">Packages</h2>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold"
                    style="border-color: #E2E8F0; background: #FFFFFF; color: #475569;"
                    :disabled="loading"
                    @click="emit('refresh-packages')"
                >
                    <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                    Refresh
                </button>

                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold"
                    style="background: #2563EB; color: #FFFFFF; box-shadow: 0 6px 18px rgba(37,99,235,0.26);"
                    @click="openCreateModal"
                >
                    <Plus class="mr-1.5 h-3.5 w-3.5" />
                    Add Package
                </button>
            </div>
        </div>

        <p v-if="errorMessage" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ errorMessage }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Total Packages</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#2563EB]">{{ stats.total }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Active</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#059669]">{{ stats.active }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Bookings This Month</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#7C3AED]">{{ stats.bookingsThisMonth }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Most Popular</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#D97706]">{{ stats.mostPopular }}</p>
            </article>
        </div>

        <div v-if="loading" class="rounded-2xl border p-10 text-center text-sm text-[#94A3B8]" style="border-color: #E2E8F0; background: #FFFFFF;">
            Loading package data...
        </div>

        <div v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article
                v-for="(pkg, index) in packageItems"
                :key="`package-card-${pkg.id}-${index}`"
                class="overflow-hidden rounded-2xl border"
                :style="{ borderColor: pkg.tone.border, background: '#FFFFFF', boxShadow: '0 2px 8px rgba(15,23,42,0.08)' }"
            >
                <div
                    class="relative px-5 pb-5 pt-4"
                    :style="{
                        backgroundColor: pkg.tone.tint,
                        backgroundImage: `radial-gradient(${pkg.tone.dot} 1.25px, transparent 1.25px)`,
                        backgroundSize: '16px 16px',
                    }"
                >
                    <div class="mb-3 flex items-start justify-between gap-2">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl" :style="{ background: pkg.tone.iconBg, color: pkg.tone.iconColor }">
                            <Camera class="h-4 w-4" />
                        </span>
                        <span
                            v-if="pkg.isPopular || !pkg.is_active"
                            class="rounded-full px-2.5 py-1 text-xs font-semibold"
                            :style="pkg.isPopular ? { background: pkg.tone.badgeBg, color: pkg.tone.badgeColor } : { background: '#E6F7EF', color: '#8FA3A0' }"
                        >
                            {{ pkg.isPopular ? 'Popular' : 'Inactive' }}
                        </span>
                    </div>

                    <h3 class="text-[2rem] font-bold leading-tight text-[#111827]">{{ pkg.name }}</h3>
                    <p class="text-[2rem] font-bold leading-tight" :style="{ color: pkg.tone.accent }">{{ formatRupiah(pkg.base_price) }}</p>
                </div>

                <div class="space-y-4 px-5 pb-5 pt-4">
                    <div class="flex items-center gap-4 text-[0.95rem] text-[#64748B]">
                        <span class="inline-flex items-center gap-1.5"><Clock3 class="h-4 w-4" /> {{ pkg.duration_minutes }} min</span>
                        <span class="inline-flex items-center gap-1.5">Code: {{ pkg.code }}</span>
                    </div>

                    <ul class="space-y-1 text-[0.95rem] text-[#334155]">
                        <li v-for="(feature, featureIndex) in pkg.features.slice(0, 4)" :key="`feature-${pkg.id}-${featureIndex}`" class="flex items-start gap-2">
                            <span class="mt-0.5 text-xs" :style="{ color: pkg.tone.accent }">✓</span>
                            <span>{{ feature }}</span>
                        </li>
                        <li v-if="!pkg.features.length" class="text-[0.9rem] text-[#94A3B8]">Description not set.</li>
                        <li v-if="pkg.features.length > 4" class="text-[0.9rem]" :style="{ color: pkg.tone.accent }">+{{ pkg.features.length - 4 }} more features</li>
                    </ul>

                    <div class="flex items-center justify-between rounded-xl px-3 py-2 text-sm" style="background: #F8FAFC; color: #94A3B8;">
                        <span>This month</span>
                        <span class="font-semibold" :style="{ color: pkg.tone.accent }">{{ pkg.thisMonthBookings }} bookings</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border py-2 text-sm font-semibold"
                            :style="{ borderColor: pkg.tone.accent, color: pkg.tone.accent }"
                            @click="openEditModal(pkg)"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Edit
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border"
                            style="border-color: #FECACA; color: #EF4444;"
                            :disabled="Number(deletingPackageId || 0) === Number(pkg.id)"
                            @click="requestDelete(pkg)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </article>

            <div v-if="!packageItems.length" class="col-span-full rounded-2xl border border-dashed p-8 text-center text-sm text-[#94A3B8]" style="border-color: #CBD5E1;">
                No package data available.
            </div>
        </div>

        <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">{{ modalMode === 'create' ? 'Add Package' : 'Edit Package' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="text-sm text-[#475569]">
                        Package Name
                        <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Duration (minutes)
                        <input v-model.number="form.duration_minutes" type="number" min="5" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Base Price
                        <input v-model.number="form.base_price" type="number" min="0" step="1000" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Sort Order
                        <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>
                </div>

                <label class="mt-3 block text-sm text-[#475569]">
                    Description / Features (one feature per line)
                    <textarea v-model="form.description" rows="5" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></textarea>
                </label>

                <label class="mt-3 inline-flex items-center gap-2 text-sm text-[#475569]">
                    <input v-model="form.is_active" type="checkbox" >
                    Active package
                </label>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold text-white"
                        style="background: #2563EB;"
                        :disabled="saving"
                        @click="submitForm"
                    >
                        {{ saving ? 'Saving...' : (modalMode === 'create' ? 'Create Package' : 'Save Changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
