<script setup>
import { computed, reactive, ref } from 'vue';
import { Pencil, Plus, RefreshCw, Table2, Trash2 } from 'lucide-vue-next';

const props = defineProps({
    addOnRows: { type: Array, default: () => [] },
    packageOptions: { type: Array, default: () => [] },
    formatRupiah: { type: Function, required: true },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingAddOnId: { type: [Number, String, null], default: null },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-add-ons', 'create-add-on', 'update-add-on', 'delete-add-on']);

const modalOpen = ref(false);
const modalMode = ref('create');
const editingAddOnId = ref(null);
const localError = ref('');

const form = reactive({
    package_id: '',
    code: '',
    name: '',
    description: '',
    price: 0,
    max_qty: 1,
    is_physical: false,
    is_active: true,
    sort_order: 0,
});

const stats = computed(() => {
    const total = props.addOnRows.length;
    const active = props.addOnRows.filter((item) => Boolean(item.is_active)).length;
    const global = props.addOnRows.filter((item) => !item.package_id).length;
    const packageSpecific = total - global;
    const physical = props.addOnRows.filter((item) => Boolean(item.is_physical)).length;
    const nonPhysical = total - physical;

    return {
        total,
        active,
        global,
        packageSpecific,
        physical,
        nonPhysical,
    };
});

const physicalAddOnRows = computed(() => {
    return props.addOnRows.filter((item) => Boolean(item.is_physical));
});

const nonPhysicalAddOnRows = computed(() => {
    return props.addOnRows.filter((item) => !Boolean(item.is_physical));
});

const addOnSections = computed(() => ([
    {
        key: 'physical',
        title: 'Physical Add-ons',
        description: 'Items with stock movement (inventory).',
        rows: physicalAddOnRows.value,
    },
    {
        key: 'non-physical',
        title: 'Non-physical Add-ons',
        description: 'Service/benefit add-ons without physical stock.',
        rows: nonPhysicalAddOnRows.value,
    },
]));

const resetForm = () => {
    form.package_id = '';
    form.code = '';
    form.name = '';
    form.description = '';
    form.price = 0;
    form.max_qty = 1;
    form.is_physical = false;
    form.is_active = true;
    form.sort_order = 0;
    editingAddOnId.value = null;
    localError.value = '';
};

const openCreateModal = () => {
    resetForm();
    modalMode.value = 'create';
    modalOpen.value = true;
};

const openEditModal = (addOn) => {
    modalMode.value = 'edit';
    editingAddOnId.value = Number(addOn.id || 0);
    form.package_id = addOn.package_id ? String(addOn.package_id) : '';
    form.code = String(addOn.code || '');
    form.name = String(addOn.name || '');
    form.description = String(addOn.description || '');
    form.price = Number(addOn.price || 0);
    form.max_qty = Math.max(1, Number(addOn.max_qty || 1));
    form.is_physical = Boolean(addOn.is_physical);
    form.is_active = Boolean(addOn.is_active);
    form.sort_order = Number(addOn.sort_order || 0);
    localError.value = '';
    modalOpen.value = true;
};

const closeModal = () => {
    modalOpen.value = false;
    localError.value = '';
};

const validateForm = () => {
    if (!String(form.name || '').trim()) {
        localError.value = 'Add-on name is required.';
        return false;
    }

    if (Number(form.price || 0) < 0) {
        localError.value = 'Price cannot be negative.';
        return false;
    }

    if (Number(form.max_qty || 0) < 1) {
        localError.value = 'Max qty must be at least 1.';
        return false;
    }

    if (Number(form.sort_order || 0) < 0) {
        localError.value = 'Sort order cannot be negative.';
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
        package_id: form.package_id ? Number(form.package_id) : null,
        code: String(form.code || '').trim(),
        name: String(form.name || '').trim(),
        description: String(form.description || '').trim(),
        price: Number(form.price || 0),
        max_qty: Math.max(1, Number(form.max_qty || 1)),
        is_physical: Boolean(form.is_physical),
        is_active: Boolean(form.is_active),
        sort_order: Math.max(0, Number(form.sort_order || 0)),
    };

    try {
        if (modalMode.value === 'create') {
            await emit('create-add-on', payload);
        } else {
            await emit('update-add-on', {
                id: editingAddOnId.value,
                payload,
            });
        }

        modalOpen.value = false;
    } catch {
        // Error text is surfaced by parent component.
    }
};

const requestDelete = async (addOn) => {
    const addOnName = String(addOn.name || 'this add-on');
    const confirmed = window.confirm(`Delete ${addOnName}? This action cannot be undone.`);

    if (!confirmed) {
        return;
    }

    try {
        await emit('delete-add-on', Number(addOn.id || 0));
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
                        <Table2 class="h-3.5 w-3.5" style="color: #BFDBFE;" />
                        <span class="text-xs font-medium" style="color: #BFDBFE;">Add-on management</span>
                    </div>
                    <h2 class="text-[1.35rem] font-bold text-white">Add-ons</h2>
                    <p class="text-sm" style="color: rgba(255,255,255,0.72);">Manage global and package-specific add-ons in a compact table.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold"
                        style="border-color: rgba(255,255,255,0.34); background: rgba(255,255,255,0.1); color: #FFFFFF;"
                        :disabled="loading"
                        @click="emit('refresh-add-ons')"
                    >
                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                        Refresh
                    </button>
                    <button type="button" class="rounded-xl bg-white px-4 py-2 text-sm font-semibold" style="color: #1D4ED8;" @click="openCreateModal">
                        <Plus class="mr-1 inline h-3.5 w-3.5" />
                        Add Add-on
                    </button>
                </div>
            </div>
        </section>

        <p v-if="errorMessage" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ errorMessage }}
        </p>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-6">
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Total Add-ons</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#2563EB]">{{ stats.total }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Active</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#059669]">{{ stats.active }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Global</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#7C3AED]">{{ stats.global }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Package Specific</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#D97706]">{{ stats.packageSpecific }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Physical</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#0F766E]">{{ stats.physical }}</p>
            </article>
            <article class="rounded-2xl border px-4 py-3.5" style="border-color: #E6EBF4; background: #FFFFFF; box-shadow: 0 1px 3px rgba(15,23,42,0.06);">
                <p class="text-sm text-[#94A3B8]">Non-physical</p>
                <p class="mt-0.5 text-[2rem] font-bold text-[#7C3AED]">{{ stats.nonPhysical }}</p>
            </article>
        </div>

        <div v-if="loading" class="rounded-2xl border p-10 text-center text-sm text-[#94A3B8]" style="border-color: #E2E8F0; background: #FFFFFF;">
            Loading add-on data...
        </div>

        <div v-else class="space-y-4">
            <section
                v-for="section in addOnSections"
                :key="`addon-section-${section.key}`"
                class="overflow-hidden rounded-2xl border"
                style="border-color: #DBEAFE; background: #FFFFFF; box-shadow: 0 1px 3px rgba(37,99,235,0.08), 0 6px 18px rgba(37,99,235,0.08);"
            >
                <header class="border-b px-4 py-3" style="border-color: #E2E8F0; background: #F8FAFC;">
                    <h3 class="text-sm font-semibold text-[#1E293B]">{{ section.title }}</h3>
                    <p class="text-xs text-[#64748B]">{{ section.description }}</p>
                </header>

                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead style="background: #EFF6FF; color: #334155;">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Code</th>
                                <th class="px-3 py-2 text-left font-semibold">Name</th>
                                <th class="px-3 py-2 text-left font-semibold">Package</th>
                                <th class="px-3 py-2 text-center font-semibold">Type</th>
                                <th class="px-3 py-2 text-right font-semibold">Price</th>
                                <th class="px-3 py-2 text-center font-semibold">Max</th>
                                <th class="px-3 py-2 text-center font-semibold">Status</th>
                                <th class="px-3 py-2 text-center font-semibold">Sort</th>
                                <th class="px-3 py-2 text-right font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="row in section.rows" :key="`addon-row-${section.key}-${row.id}`" class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#475569]">{{ row.code }}</td>
                                <td class="px-3 py-2">
                                    <p class="font-semibold text-[#1E293B]">{{ row.name }}</p>
                                    <p v-if="row.description" class="text-xs text-[#64748B]">{{ row.description }}</p>
                                </td>
                                <td class="px-3 py-2 text-[#334155]">{{ row.package_name || 'Global' }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :style="row.is_physical ? { background: '#ECFEFF', color: '#0E7490' } : { background: '#F5F3FF', color: '#7C3AED' }">
                                        {{ row.is_physical ? 'Physical' : 'Non-physical' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-right font-semibold text-[#2563EB]">{{ row.price_text || formatRupiah(row.price) }}</td>
                                <td class="px-3 py-2 text-center text-[#334155]">{{ row.max_qty }}</td>
                                <td class="px-3 py-2 text-center">
                                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :style="row.is_active ? { background: '#ECFDF5', color: '#059669' } : { background: '#F8FAFC', color: '#64748B' }">
                                        {{ row.is_active ? 'active' : 'inactive' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-center text-[#334155]">{{ row.sort_order }}</td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-1 text-xs font-semibold"
                                            style="border-color: #2563EB; color: #2563EB;"
                                            @click="openEditModal(row)"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            Edit
                                        </button>
                                        <button
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-1 text-xs font-semibold"
                                            style="border-color: #FECACA; color: #EF4444;"
                                            :disabled="Number(deletingAddOnId || 0) === Number(row.id)"
                                            @click="requestDelete(row)"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!section.rows.length">
                                <td colspan="9" class="px-4 py-8 text-center text-sm text-[#94A3B8]">
                                    No {{ section.title.toLowerCase() }} data available.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-3xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">{{ modalMode === 'create' ? 'Add Add-on' : 'Edit Add-on' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="overflow-hidden rounded-xl border" style="border-color: #E2E8F0;">
                    <table class="min-w-full text-sm">
                        <thead style="background: #F8FAFC; color: #475569;">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Field</th>
                                <th class="px-3 py-2 text-left font-semibold">Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Package</td>
                                <td class="px-3 py-2">
                                    <select v-model="form.package_id" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                        <option value="">Global</option>
                                        <option v-for="pkg in packageOptions" :key="`pkg-option-${pkg.id}`" :value="String(pkg.id)">
                                            {{ pkg.name }}
                                        </option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Code (optional)</td>
                                <td class="px-3 py-2">
                                    <input v-model="form.code" type="text" placeholder="Auto-generated when empty" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Name</td>
                                <td class="px-3 py-2">
                                    <input v-model="form.name" type="text" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Description</td>
                                <td class="px-3 py-2">
                                    <textarea v-model="form.description" rows="2" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;"></textarea>
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Price</td>
                                <td class="px-3 py-2">
                                    <input v-model.number="form.price" type="number" min="0" step="1000" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Max Qty</td>
                                <td class="px-3 py-2">
                                    <input v-model.number="form.max_qty" type="number" min="1" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Type</td>
                                <td class="px-3 py-2">
                                    <select v-model="form.is_physical" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                                        <option :value="false">Non-physical</option>
                                        <option :value="true">Physical</option>
                                    </select>
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Sort Order</td>
                                <td class="px-3 py-2">
                                    <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                                </td>
                            </tr>
                            <tr class="border-t" style="border-color: #E2E8F0;">
                                <td class="px-3 py-2 text-[#334155]">Status</td>
                                <td class="px-3 py-2">
                                    <label class="inline-flex items-center gap-2 text-sm text-[#475569]">
                                        <input v-model="form.is_active" type="checkbox" >
                                        Active add-on
                                    </label>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #2563EB; color: #FFFFFF;"
                        :disabled="saving"
                        @click="submitForm"
                    >
                        {{ saving ? 'Saving...' : (modalMode === 'create' ? 'Create Add-on' : 'Save Changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
