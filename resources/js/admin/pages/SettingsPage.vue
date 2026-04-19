<script setup>
import { computed, reactive, ref, watch } from 'vue';

const props = defineProps({
    settingsTab: { type: String, default: 'branch' },
    settingsTabs: { type: Array, default: () => [] },
    settings: {
        type: Object,
        default: () => ({
            default_branch_id: null,
            branches: [],
        }),
    },
    branchOptions: { type: Array, default: () => [] },
    defaultBranchId: { type: [Number, String, null], default: null },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    errorMessage: { type: String, default: '' },
    successMessage: { type: String, default: '' },
});

const emit = defineEmits([
    'update:settingsTab',
    'refresh-settings',
    'save-default-branch',
    'create-branch',
    'update-branch',
    'remove-branch',
]);

const selectedBranchId = ref('');
const localError = ref('');
const branchDrafts = ref({});
const newBranch = reactive({
    name: '',
    timezone: 'Asia/Jakarta',
    phone: '',
    address: '',
});

const normalizedBranchOptions = computed(() => {
    return (Array.isArray(props.branchOptions) ? props.branchOptions : [])
        .map((branch) => ({
            id: Number(branch?.id || 0),
            name: String(branch?.name || '-'),
            timezone: String(branch?.timezone || 'Asia/Jakarta'),
            phone: String(branch?.phone || ''),
            address: String(branch?.address || ''),
        }))
        .filter((branch) => branch.id > 0);
});

const syncSelectedBranch = () => {
    const fromSettings = Number(props.settings?.default_branch_id || 0);
    const fromProp = Number(props.defaultBranchId || 0);
    const fromOption = Number(normalizedBranchOptions.value[0]?.id || 0);
    const resolved = fromSettings || fromProp || fromOption || 0;

    selectedBranchId.value = resolved > 0 ? String(resolved) : '';
};

const syncBranchDrafts = () => {
    const nextDrafts = {};

    for (const branch of normalizedBranchOptions.value) {
        nextDrafts[branch.id] = {
            name: String(branch.name || ''),
            timezone: String(branch.timezone || 'Asia/Jakarta'),
            phone: String(branch.phone || ''),
            address: String(branch.address || ''),
        };
    }

    branchDrafts.value = nextDrafts;
};

const branchDraftFor = (branch) => {
    const id = Number(branch?.id || 0);

    if (!id) {
        return {
            name: '',
            timezone: 'Asia/Jakarta',
            phone: '',
            address: '',
        };
    }

    if (!branchDrafts.value[id]) {
        branchDrafts.value[id] = {
            name: String(branch?.name || ''),
            timezone: String(branch?.timezone || 'Asia/Jakarta'),
            phone: String(branch?.phone || ''),
            address: String(branch?.address || ''),
        };
    }

    return branchDrafts.value[id];
};

const saveDefaultBranch = () => {
    const branchId = Number(selectedBranchId.value || 0);

    if (branchId <= 0) {
        localError.value = 'Pilih cabang default terlebih dahulu.';
        return;
    }

    localError.value = '';
    emit('save-default-branch', branchId);
};

const saveBranchDraft = (branchId) => {
    const id = Number(branchId || 0);
    const draft = branchDrafts.value[id];

    if (!id || !draft) {
        return;
    }

    const name = String(draft.name || '').trim();

    if (!name) {
        localError.value = 'Nama cabang tidak boleh kosong.';
        return;
    }

    localError.value = '';
    emit('update-branch', {
        id,
        payload: {
            name,
            timezone: String(draft.timezone || 'Asia/Jakarta').trim() || 'Asia/Jakarta',
            phone: String(draft.phone || '').trim(),
            address: String(draft.address || '').trim(),
        },
    });
};

const addNewBranch = () => {
    const name = String(newBranch.name || '').trim();

    if (!name) {
        localError.value = 'Nama cabang baru harus diisi.';
        return;
    }

    localError.value = '';
    emit('create-branch', {
        name,
        timezone: String(newBranch.timezone || 'Asia/Jakarta').trim() || 'Asia/Jakarta',
        phone: String(newBranch.phone || '').trim(),
        address: String(newBranch.address || '').trim(),
    });

    newBranch.name = '';
    newBranch.timezone = 'Asia/Jakarta';
    newBranch.phone = '';
    newBranch.address = '';
};

const removeBranch = (branchId) => {
    const id = Number(branchId || 0);

    if (!id) {
        return;
    }

    const confirmed = window.confirm('Hapus cabang ini dari daftar aktif?');

    if (!confirmed) {
        return;
    }

    localError.value = '';
    emit('remove-branch', id);
};

watch(
    () => [
        props.settings?.default_branch_id,
        props.defaultBranchId,
        normalizedBranchOptions.value.length,
    ],
    () => {
        syncSelectedBranch();
        syncBranchDrafts();
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-5">
        <section class="relative overflow-hidden rounded-2xl px-6 py-5" style="background: linear-gradient(130deg, #0F766E 0%, #0D9488 58%, #14B8A6 100%); box-shadow: 0 6px 24px rgba(15,118,110,0.24);">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -right-10 -top-10 h-36 w-36 rounded-full" style="background: rgba(240,253,250,0.2);"></div>
                <div class="absolute left-8 top-4 h-8 w-8 rounded-full" style="background: rgba(204,251,241,0.2);"></div>
            </div>
            <div class="relative">
                <h2 class="text-[1.35rem] font-bold text-white">Settings</h2>
                <p class="text-sm" style="color: rgba(255,255,255,0.8);">Pengaturan branch default dan daftar cabang aktif.</p>
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
                <div class="mb-4 flex items-center justify-between rounded-xl border px-3 py-2" style="border-color: #D1FAE5; background: #ECFDF5;">
                    <p class="text-sm text-[#0F766E]">Owner bisa ganti default branch dan menghapus branch dari daftar aktif.</p>
                    <button
                        type="button"
                        class="rounded-lg border px-3 py-1 text-xs"
                        style="border-color: #99F6E4; color: #0F766E;"
                        :disabled="loading"
                        @click="emit('refresh-settings')"
                    >
                        {{ loading ? 'Refreshing...' : 'Refresh' }}
                    </button>
                </div>

                <p v-if="errorMessage || localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError || errorMessage }}
                </p>

                <p v-if="successMessage" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #A7F3D0; background: #ECFDF5; color: #047857;">
                    {{ successMessage }}
                </p>

                <div v-if="settingsTab === 'branch'" class="space-y-5">
                    <div class="rounded-xl border p-4" style="border-color: #E2E8F0; background: #F8FAFC;">
                        <label class="mb-1 block text-sm text-[#374151]">Default Branch</label>
                        <div class="flex flex-col gap-2 sm:flex-row">
                            <select
                                v-model="selectedBranchId"
                                class="w-full rounded-lg border px-3 py-2 text-sm"
                                style="border-color: #E2E8F0;"
                            >
                                <option value="">Select branch</option>
                                <option
                                    v-for="branch in normalizedBranchOptions"
                                    :key="`settings-branch-${branch.id}`"
                                    :value="String(branch.id)"
                                >
                                    {{ branch.name }}
                                </option>
                            </select>
                            <button
                                type="button"
                                class="rounded-xl bg-[#0F766E] px-4 py-2 text-sm text-white"
                                :disabled="saving || loading"
                                @click="saveDefaultBranch"
                            >
                                {{ saving ? 'Saving...' : 'Simpan Default' }}
                            </button>
                        </div>
                    </div>

                    <div class="rounded-xl border p-4" style="border-color: #E2E8F0; background: #FFFFFF;">
                        <h3 class="text-sm font-semibold text-[#1F2937]">Daftar Cabang Aktif</h3>

                        <div class="mt-3 space-y-3">
                            <div
                                v-for="branch in normalizedBranchOptions"
                                :key="`manage-branch-${branch.id}`"
                                class="rounded-lg border p-3"
                                style="border-color: #E2E8F0; background: #F8FAFC;"
                            >
                                <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                    <label class="text-xs text-[#64748B]">
                                        Nama Cabang
                                        <input
                                            v-model="branchDraftFor(branch).name"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm"
                                            style="border-color: #CBD5E1;"
                                        >
                                    </label>

                                    <label class="text-xs text-[#64748B]">
                                        Timezone
                                        <input
                                            v-model="branchDraftFor(branch).timezone"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm"
                                            style="border-color: #CBD5E1;"
                                        >
                                    </label>

                                    <label class="text-xs text-[#64748B]">
                                        Phone
                                        <input
                                            v-model="branchDraftFor(branch).phone"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm"
                                            style="border-color: #CBD5E1;"
                                        >
                                    </label>

                                    <label class="text-xs text-[#64748B]">
                                        Address
                                        <input
                                            v-model="branchDraftFor(branch).address"
                                            type="text"
                                            class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm"
                                            style="border-color: #CBD5E1;"
                                        >
                                    </label>
                                </div>

                                <div class="mt-2 flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs"
                                        style="border-color: #93C5FD; color: #1D4ED8;"
                                        :disabled="saving || loading"
                                        @click="saveBranchDraft(branch.id)"
                                    >
                                        Update
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-lg border px-3 py-1.5 text-xs"
                                        style="border-color: #FCA5A5; color: #B91C1C;"
                                        :disabled="saving || loading"
                                        @click="removeBranch(branch.id)"
                                    >
                                        Hapus
                                    </button>
                                </div>
                            </div>

                            <p v-if="!normalizedBranchOptions.length" class="text-xs text-[#64748B]">Belum ada cabang aktif.</p>
                        </div>
                    </div>

                    <div class="rounded-xl border p-4" style="border-color: #E2E8F0; background: #F8FAFC;">
                        <h3 class="text-sm font-semibold text-[#1F2937]">Tambah Cabang</h3>
                        <div class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-2">
                            <label class="text-xs text-[#64748B]">
                                Nama Cabang
                                <input v-model="newBranch.name" type="text" class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                            </label>
                            <label class="text-xs text-[#64748B]">
                                Timezone
                                <input v-model="newBranch.timezone" type="text" class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                            </label>
                            <label class="text-xs text-[#64748B]">
                                Phone
                                <input v-model="newBranch.phone" type="text" class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                            </label>
                            <label class="text-xs text-[#64748B]">
                                Address
                                <input v-model="newBranch.address" type="text" class="mt-1 w-full rounded-lg border px-2 py-1.5 text-sm" style="border-color: #CBD5E1;" >
                            </label>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <button
                                type="button"
                                class="rounded-xl bg-[#0F766E] px-4 py-2 text-sm text-white"
                                :disabled="saving || loading"
                                @click="addNewBranch"
                            >
                                {{ saving ? 'Saving...' : 'Tambah Cabang' }}
                            </button>
                        </div>
                    </div>
                </div>

                <div v-else-if="settingsTab === 'hours'" class="space-y-3">
                    <p class="text-sm text-[#64748B]">Operating hours by day</p>
                    <div
                        v-for="day in ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']"
                        :key="`hours-${day}`"
                        class="grid grid-cols-[120px_1fr] items-center gap-3 rounded-lg border px-3 py-2"
                        style="border-color: #EEF2FF;"
                    >
                        <span class="text-sm text-[#374151]">{{ day }}</span>
                        <div class="flex items-center gap-2">
                            <input type="time" value="10:00" class="rounded border px-2 py-1 text-xs" style="border-color: #E2E8F0;" >
                            <span class="text-xs text-[#94A3B8]">to</span>
                            <input type="time" value="21:00" class="rounded border px-2 py-1 text-xs" style="border-color: #E2E8F0;" >
                        </div>
                    </div>
                </div>

                <div v-else-if="settingsTab === 'security'" class="space-y-3">
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

                    <div class="mt-5 flex justify-end">
                        <button
                            type="button"
                            class="rounded-xl bg-[#0F766E] px-5 py-2 text-sm text-white"
                            :disabled="saving || loading"
                        >
                            {{ saving ? 'Saving...' : 'Save Security Settings' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
