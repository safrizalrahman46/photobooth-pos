<script setup>
import { computed, reactive, ref } from 'vue';
import { Pencil, Plus, RefreshCw, Shield, Trash2, UserRound, UsersRound } from 'lucide-vue-next';

const props = defineProps({
    userRows: { type: Array, default: () => [] },
    initialsFromName: { type: Function, required: true },
    panelBaseUrl: { type: String, default: '/admin' },
    roleOptions: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    deletingUserId: { type: [Number, String, null], default: null },
    canManage: { type: Boolean, default: false },
    currentUserEmail: { type: String, default: '' },
    errorMessage: { type: String, default: '' },
});

const emit = defineEmits(['refresh-users', 'create-user', 'update-user', 'delete-user']);

const ownerCount = computed(() => props.userRows.filter((item) => String(item.role || '').toLowerCase() === 'owner').length);
const cashierCount = computed(() => props.userRows.filter((item) => String(item.role || '').toLowerCase() === 'cashier').length);
const activeCount = computed(() => props.userRows.filter((item) => String(item.status || '').toLowerCase() === 'active').length);

const modalOpen = ref(false);
const modalMode = ref('create');
const editingUserId = ref(null);
const localError = ref('');
const form = reactive({
    name: '',
    email: '',
    phone: '',
    password: '',
    role: '',
    is_active: true,
});

const roleTone = (role) => {
    const normalized = String(role || '').toLowerCase();

    if (normalized === 'owner') {
        return { bg: '#F5F3FF', color: '#7C3AED' };
    }

    if (normalized === 'cashier') {
        return { bg: '#EFF6FF', color: '#2563EB' };
    }

    if (normalized === 'admin') {
        return { bg: '#E0EAFF', color: '#1D4ED8' };
    }

    return { bg: '#F8FAFC', color: '#64748B' };
};

const resetForm = () => {
    form.name = '';
    form.email = '';
    form.phone = '';
    form.password = '';
    form.role = '';
    form.is_active = true;
    modalMode.value = 'create';
    editingUserId.value = null;
    localError.value = '';
};

const openAddModal = () => {
    resetForm();
    modalMode.value = 'create';
    editingUserId.value = null;
    modalOpen.value = true;
};

const openEditModal = (user) => {
    modalMode.value = 'edit';
    editingUserId.value = Number(user.id || 0);
    form.name = String(user.name || '');
    form.email = String(user.email || '');
    form.phone = String(user.phone || '');
    form.password = '';
    form.role = String(user.role_key || '').trim();
    form.is_active = Boolean(user.is_active);
    localError.value = '';
    modalOpen.value = true;
};

const isSelfUser = (user) => {
    const currentEmail = String(props.currentUserEmail || '').trim().toLowerCase();
    const targetEmail = String(user?.email || '').trim().toLowerCase();

    return currentEmail !== '' && targetEmail !== '' && currentEmail === targetEmail;
};

const closeModal = () => {
    modalOpen.value = false;
    localError.value = '';
};

const validateForm = () => {
    if (!String(form.name || '').trim()) {
        localError.value = 'Name is required.';
        return false;
    }

    if (!String(form.email || '').trim()) {
        localError.value = 'Email is required.';
        return false;
    }

    const password = String(form.password || '').trim();

    if (modalMode.value === 'create' && password.length < 8) {
        localError.value = 'Password must be at least 8 characters.';
        return false;
    }

    if (modalMode.value === 'edit' && password.length > 0 && password.length < 8) {
        localError.value = 'If filled, password must be at least 8 characters.';
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
        email: String(form.email || '').trim(),
        phone: String(form.phone || '').trim(),
        role: String(form.role || '').trim() || null,
        is_active: Boolean(form.is_active),
        ...(String(form.password || '').trim() !== '' ? { password: String(form.password || '') } : {}),
    };

    try {
        if (modalMode.value === 'create') {
            await emit('create-user', payload);
        } else {
            await emit('update-user', {
                id: editingUserId.value,
                payload,
            });
        }

        modalOpen.value = false;
    } catch {
        // Parent component handles server error messages.
    }
};

const requestDelete = async (user) => {
    if (isSelfUser(user)) {
        localError.value = 'Akun sendiri tidak bisa dihapus.';
        return;
    }

    const confirmed = window.confirm(`Hapus user ${String(user.name || 'ini')}?`);

    if (!confirmed) {
        return;
    }

    try {
        await emit('delete-user', Number(user.id || 0));
    } catch {
        // Parent component handles server error messages.
    }
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
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="inline-flex items-center justify-center rounded-xl border px-3 py-2 text-sm font-semibold"
                        style="border-color: rgba(255,255,255,0.34); background: rgba(255,255,255,0.1); color: #FFFFFF;"
                        :disabled="loading"
                        @click="emit('refresh-users')"
                    >
                        <RefreshCw class="mr-1.5 h-3.5 w-3.5" :class="loading ? 'animate-spin' : ''" />
                        Refresh
                    </button>
                    <button
                        v-if="canManage"
                        type="button"
                        class="rounded-xl bg-white px-4 py-2 text-sm font-semibold"
                        style="color: #0F172A;"
                        @click="openAddModal"
                    >
                        <Plus class="mr-1 inline h-3.5 w-3.5" />
                        Add User
                    </button>
                </div>
            </div>
        </section>

        <p v-if="errorMessage" class="rounded-xl border px-4 py-3 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
            {{ errorMessage }}
        </p>

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
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Email</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Role</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Status</th>
                        <th class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Source</th>
                        <th v-if="canManage" class="px-5 py-3 text-left text-xs uppercase tracking-wider text-[#94A3B8]">Actions</th>
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
                        <td class="px-5 py-3.5 text-sm text-[#334155]">{{ user.email || '-' }}</td>
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
                        <td v-if="canManage" class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium"
                                    style="border-color: #BFDBFE; color: #1D4ED8; background: #EFF6FF;"
                                    @click="openEditModal(user)"
                                >
                                    <Pencil class="h-3 w-3" />
                                    Edit
                                </button>
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1 text-xs font-medium"
                                    style="border-color: #FECACA; color: #DC2626; background: #FEF2F2;"
                                    :disabled="Number(deletingUserId || 0) === Number(user.id || 0) || isSelfUser(user)"
                                    @click="requestDelete(user)"
                                >
                                    <Trash2 class="h-3 w-3" />
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="!userRows.length">
                        <td :colspan="canManage ? 6 : 5" class="px-5 py-10 text-center text-sm text-[#94A3B8]">No user data found.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="modalOpen" class="fixed inset-0 z-40 flex items-center justify-center p-4" style="background: rgba(15,23,42,0.45);">
            <div class="w-full max-w-xl rounded-2xl border bg-white p-5" style="border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-[#0F172A]">{{ modalMode === 'create' ? 'Add User' : 'Edit User' }}</h3>
                    <button type="button" class="rounded-lg px-2 py-1 text-sm text-[#64748B]" @click="closeModal">Close</button>
                </div>

                <p v-if="localError" class="mb-3 rounded-lg border px-3 py-2 text-sm" style="border-color: #FECACA; background: #FEF2F2; color: #B91C1C;">
                    {{ localError }}
                </p>

                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                    <label class="text-sm text-[#475569]">
                        Full Name
                        <input v-model="form.name" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Email
                        <input v-model="form.email" type="email" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Phone (optional)
                        <input v-model="form.phone" type="text" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Password {{ modalMode === 'edit' ? '(optional)' : '' }}
                        <input v-model="form.password" type="password" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;" >
                    </label>

                    <label class="text-sm text-[#475569]">
                        Role
                        <select v-model="form.role" class="mt-1 w-full rounded-lg border px-3 py-2" style="border-color: #E2E8F0;">
                            <option value="">No role</option>
                            <option v-for="role in roleOptions" :key="`role-option-${role.value}`" :value="role.value">
                                {{ role.label }}
                            </option>
                        </select>
                    </label>
                </div>

                <label class="mt-3 inline-flex items-center gap-2 text-sm text-[#475569]">
                    <input v-model="form.is_active" type="checkbox" >
                    Active user
                </label>

                <div class="mt-5 flex items-center justify-end gap-2">
                    <button type="button" class="rounded-xl border px-4 py-2 text-sm" style="border-color: #E2E8F0; color: #64748B;" @click="closeModal">Cancel</button>
                    <button
                        type="button"
                        class="rounded-xl px-4 py-2 text-sm font-semibold"
                        style="background: #0F172A; color: #FFFFFF;"
                        :disabled="saving"
                        @click="submitForm"
                    >
                        {{ saving ? 'Saving...' : (modalMode === 'create' ? 'Create User' : 'Save Changes') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
