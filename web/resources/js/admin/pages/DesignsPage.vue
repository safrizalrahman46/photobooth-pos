<script setup>
import { computed, reactive, ref } from 'vue';
import { Image, Pencil, Plus, RefreshCw, Sparkles, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    designCards: { type: Array, default: () => [] },
    panelBaseUrl: { type: String, default: '/admin' },
    packageOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingDesignId: { type: [Number, String, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-designs', 'create-design', 'update-design', 'delete-design']);

const modalOpen = ref(false);
const modalMode = ref('create');
const editingDesignId = ref(null);
const localError = ref('');

const form = reactive({
    package_id: '',
    name: '',
    theme: '',
    preview_url: '',
    sort_order: 0,
    is_active: true,
});

const stats = computed(() => {
    const total = props.designCards.length;
    const active = props.designCards.filter((item) => Boolean(item.is_active)).length;
    const monthBookings = props.designCards.reduce((sum, item) => sum + Number(item.bookings || 0), 0);
    const mostUsed = props.designCards
        .slice()
        .sort((left, right) => Number(right.bookings || 0) - Number(left.bookings || 0))[0]?.name || '-';

    return {
        total,
        active,
        monthBookings,
        mostUsed,
    };
});

const resetForm = () => {
    form.package_id = '';
    form.name = '';
    form.theme = '';
    form.preview_url = '';
    form.sort_order = 0;
    form.is_active = true;
    editingDesignId.value = null;
    localError.value = '';
};

const openCreateModal = () => {
    resetForm();
    modalMode.value = 'create';
    modalOpen.value = true;
};

const openEditModal = (design) => {
    modalMode.value = 'edit';
    editingDesignId.value = Number(design.id || 0);
    form.package_id = design.package_id ? String(design.package_id) : '';
    form.name = String(design.name || '');
    form.theme = String(design.theme || '');
    form.preview_url = String(design.preview_url || '');
    form.sort_order = Number(design.sort_order || 0);
    form.is_active = Boolean(design.is_active);
    localError.value = '';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    localError.value = '';
};

const validateForm = () => {
    if (!String(form.name || '').trim()) {
        localError.value = 'Design name is required.';
        return false;
    }

    if (String(form.preview_url || '').trim()) {
        try {
            const parsed = new URL(String(form.preview_url || '').trim());

            if (!['http:', 'https:'].includes(parsed.protocol)) {
                throw new Error('Invalid protocol');
            }
        } catch {
            localError.value = 'Preview URL must be a valid http/https URL.';
            return false;
        }
    }

    localError.value = '';
    return true;
};

const submitForm = async () => {
    if (!validateForm()) {
        return;
    }

    const payload = {
        package_id: form.package_id ? Number(form.package_id) : null,
        name: String(form.name || '').trim(),
        theme: String(form.theme || '').trim(),
        preview_url: String(form.preview_url || '').trim(),
        is_active: Boolean(form.is_active),
        sort_order: Number(form.sort_order || 0),
    };

    try {
        if (modalMode.value === 'create') {
            await emit('create-design', payload);
        } else {
            await emit('update-design', {
                id: editingDesignId.value,
                payload,
            });
        }

        modalOpen.value = false;
    } catch {
        // Error text is surfaced by parent component.
    }
};

const requestDelete = async (design) => {
    const designName = String(design.name || 'this design');
    const confirmed = window.confirm(`Delete ${designName}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    try {
        await emit('delete-design', Number(design.id || 0));
    } catch {
        // Error text is surfaced by parent component.
    }
};
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
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold"
                        style="border-color: rgba(255,255,255,0.34); background: rgba(255,255,255,0.1); color: #FFFFFF;"
                        :disabled="loading"
                        @click="emit('refresh-designs')"
                    >
                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                        Refresh
                    </button>
                    <button type="button" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #1D4ED8;" @click="openCreateModal">
                        <Plus class="mr-1 inline h-3.5 w-3.5" />
                        Add Design
                    </button>
                </div>
            </div>
        </section>

        <p v-if="errorMessage" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ errorMessage }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Total Designs</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#2563EB]">{{ stats.total }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Active</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#059669]">{{ stats.active }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Bookings This Month</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#7C3AED]">{{ stats.monthBookings }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Most Used</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#D97706]">{{ stats.mostUsed }}</p>
            </article>
        </div>

        <div v-if="loading" class="rounded-2xl border p-10 text-center text-sm text-[#94A3B8]" style="border-color: #E2E8F0; background: #FFFFFF;">
            Loading design data...
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <article
                v-for="(design, index) in designCards"
                :key="design.id"
                class="overflow-hidden rounded-2xl border"
                style="border-color: #DBEAFE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.08), 0 6px 18px rgba(37,99,235,0.08);"
            >
                <div
                    class="relative h-40"
                    :style="design.preview_url ? { backgroundImage: `linear-gradient(rgba(30,58,138,0.18), rgba(37,99,235,0.18)), url(${design.preview_url})`, backgroundSize: 'cover', backgroundPosition: 'center' } : { background: `linear-gradient(135deg, ${design.tone.accent} 0%, ${design.tone.border} 100%)` }"
                >
                    <span class="absolute left-3 top-3 rounded-full px-2 py-0.5 text-xs font-semibold" :style="design.is_active ? { background: '#ECFDF5', color: '#059669' } : { background: '#F8FAFC', color: '#64748B' }">
                        {{ design.is_active ? 'active' : 'inactive' }}
                    </span>
                </div>

                <div class="space-y-3 p-4">
                    <div class="flex items-center justify-between gap-2">
                        <h3 class="text-sm font-semibold text-[#1F2937]">{{ design.name }}</h3>
                        <span class="rounded-full bg-[#EFF6FF] px-2 py-0.5 text-xs font-medium text-[#2563EB]">{{ design.code }}</span>
                    </div>

                    <p class="text-xs text-[#64748B]">Theme: <span class="font-medium text-[#334155]">{{ design.theme || '-' }}</span></p>
                    <p class="text-xs text-[#64748B]">Package: <span class="font-medium text-[#334155]">{{ design.package_name || '-' }}</span></p>
                    <p class="text-xs text-[#94A3B8]">{{ design.bookings }} bookings this month • {{ design.total_bookings }} total</p>

                    <div class="flex items-center justify-between text-xs text-[#64748B]">
                        <span>Updated {{ design.updated }}</span>
                        <span class="inline-flex items-center gap-1" style="color: #2563EB;">
                            <Image class="h-3 w-3" />
                            {{ design.preview_url ? 'Preview set' : 'No preview' }}
                        </span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-xl border py-2 text-sm font-semibold"
                            style="border-color: #2563EB; color: #2563EB;"
                            @click="openEditModal(design)"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Edit
                        </button>
                        <button
                            type="button"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full border"
                            style="border-color: #FECACA; color: #EF4444;"
                            :disabled="Number(deletingDesignId || 0) === Number(design.id)"
                            @click="requestDelete(design)"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </article>

            <p v-if="!designCards.length" class="col-span-full rounded-xl border border-dashed p-8 text-center text-sm text-[#94A3B8]" style="border-color: #93C5FD;">
                No design data available.
            </p>
        </div>

        <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">{{ modalMode === 'create' ? 'Add Design' : 'Edit Design' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="text-sm text-[#475569]">
                        Design Name
                        <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Theme
                        <input v-model="form.theme" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569] md:col-span-2">
                        Preview URL (optional)
                        <input v-model="form.preview_url" type="url" placeholder="https://..." class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Linked Package
                        <select v-model="form.package_id" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="">No package</option>
                            <option v-for="pkg in packageOptions" :key="`pkg-option-${pkg.id}`" :value="String(pkg.id)">
                                {{ pkg.name }}
                            </option>
                        </select>
                    </label>

                    <label class="text-sm text-[#475569]">
                        Sort Order
                        <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>
                </div>

                <label class="mt-3 inline-flex items-center gap-2 text-sm text-[#475569]">
                    <input v-model="form.is_active" type="checkbox" >
                    Active design
                </label>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #2563EB; color: #FFFFFF;"
                        :disabled="saving"
                        @click="submitForm"
                    >
                        {{ saving ? 'Saving...' : (modalMode === 'create' ? 'Create Design' : 'Save Changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
