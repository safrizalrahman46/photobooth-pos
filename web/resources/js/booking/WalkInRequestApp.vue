<script setup>
import { computed, ref, watch } from 'vue';
import PublicBookingNavbar from './PublicBookingNavbar.vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    packages: {
        type: Array,
        default: () => [],
    },
    addOns: {
        type: Array,
        default: () => [],
    },
    oldValues: {
        type: Object,
        default: () => ({}),
    },
    errors: {
        type: Array,
        default: () => [],
    },
    routes: {
        type: Object,
        required: true,
    },
    site: {
        type: Object,
        default: () => ({}),
    },
    csrfToken: {
        type: String,
        required: true,
    },
    submissionKey: {
        type: String,
        required: true,
    },
});

const asString = (value) => (value === null || value === undefined ? '' : String(value));
const digitsOnly = (value) => asString(value).replace(/\D+/g, '');

const oldAddonQty = Array.isArray(props.oldValues.addons)
    ? props.oldValues.addons.reduce((carry, item) => {
        const id = asString(item?.add_on_id || item?.id);
        const qty = Number(item?.qty || 0);

        if (id && qty > 0) {
            carry[id] = qty;
        }

        return carry;
    }, {})
    : {};

const initialBranchId = asString(props.oldValues.branch_id || (props.branches.length === 1 ? props.branches[0]?.id : ''));

const branchId = ref(initialBranchId);
const packageId = ref(asString(props.oldValues.package_id));
const customerName = ref(asString(props.oldValues.customer_name));
const customerPhone = ref(digitsOnly(props.oldValues.customer_phone));
const termsAccepted = ref(Boolean(props.oldValues.terms_accepted));
const addonQty = ref(oldAddonQty);
const isSubmitting = ref(false);

const showBranchSelector = computed(() => props.branches.length > 1);

const filteredPackages = computed(() => {
    if (!branchId.value) {
        return props.packages;
    }

    return props.packages.filter((item) => {
        if (item.branch_id === null || item.branch_id === undefined) {
            return true;
        }

        return asString(item.branch_id) === asString(branchId.value);
    });
});

const selectedPackage = computed(() => props.packages.find((item) => asString(item.id) === packageId.value) ?? null);

watch(branchId, () => {
    if (!packageId.value) {
        return;
    }

    const stillAvailable = filteredPackages.value.some((item) => asString(item.id) === packageId.value);

    if (!stillAvailable) {
        packageId.value = '';
        addonQty.value = {};
    }
});

const filteredAddOns = computed(() => {
    if (!packageId.value) {
        return [];
    }

    return props.addOns.filter((item) => {
        if (item.package_id === null || item.package_id === undefined) {
            return true;
        }

        return asString(item.package_id) === packageId.value;
    });
});

const selectedAddOns = computed(() => filteredAddOns.value
    .map((item) => ({
        ...item,
        qty: Number(addonQty.value[asString(item.id)] || 0),
    }))
    .filter((item) => item.qty > 0));

const subtotal = computed(() => {
    const packagePrice = Number(selectedPackage.value?.base_price || 0);
    const addOnTotal = selectedAddOns.value.reduce((sum, item) => sum + (Number(item.price || 0) * item.qty), 0);

    return packagePrice + addOnTotal;
});

const canSubmit = computed(() => Boolean(
    branchId.value
    && packageId.value
    && customerName.value.trim()
    && /^\d+$/.test(customerPhone.value)
    && termsAccepted.value
    && !isSubmitting.value,
));

const formatRupiah = (value) => {
    const amount = Number(value || 0);

    return `Rp ${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number.isNaN(amount) ? 0 : amount)}`;
};

const onPhoneInput = (event) => {
    const normalized = digitsOnly(event?.target?.value);
    customerPhone.value = normalized;

    if (event?.target) {
        event.target.value = normalized;
    }
};

const selectPackage = (item) => {
    packageId.value = asString(item.id);
    addonQty.value = {};
};

const addQty = (item) => {
    const key = asString(item.id);
    const current = Number(addonQty.value[key] || 0);
    const maxQty = Math.max(1, Number(item.max_qty || 1));

    if (current >= maxQty) {
        return;
    }

    addonQty.value = {
        ...addonQty.value,
        [key]: current + 1,
    };
};

const reduceQty = (item) => {
    const key = asString(item.id);
    const current = Number(addonQty.value[key] || 0);

    if (current <= 0) {
        return;
    }

    addonQty.value = {
        ...addonQty.value,
        [key]: current - 1,
    };
};

const onSubmit = () => {
    isSubmitting.value = true;
};
</script>

<template>
    <div class="min-h-screen bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" :site="props.site" />

        <main class="mx-auto w-full max-w-7xl px-4 pb-20 pt-8 sm:px-6">
            <div class="mb-8 rounded-2xl border border-[#2563EB]/10 bg-white p-5 shadow-sm">
                <p class="text-xs uppercase tracking-[0.2em] text-[#2563EB]" style="font-weight: 700;">Self Walk-in QR</p>
                <h1 class="mt-2 text-[#1F2937]" style="font-size: 1.875rem; font-weight: 800;">Daftar walk-in hari ini</h1>
                <p class="mt-2 max-w-2xl text-sm text-gray-500">Isi data, pilih paket, lalu tunjukkan kode ke kasir untuk bayar tunai dan masuk antrean.</p>
            </div>

            <form :action="props.routes.submit" method="post" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_360px]" @submit="onSubmit">
                <input type="hidden" name="_token" :value="props.csrfToken">
                <input type="hidden" name="submission_key" :value="props.submissionKey">

                <div class="space-y-6">
                    <div v-if="props.errors.length" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        <ul class="space-y-1">
                            <li v-for="(message, index) in props.errors" :key="`server-${index}`">
                                {{ message }}
                            </li>
                        </ul>
                    </div>

                    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h2 class="text-[#1F2937]" style="font-size: 1.125rem; font-weight: 700;">Data Customer</h2>
                        </div>

                        <div class="grid gap-4 p-4 sm:grid-cols-2 sm:p-6">
                            <label class="space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Nama</span>
                                <input v-model="customerName" name="customer_name" required maxlength="120" type="text" class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none transition focus:border-[#2563EB]">
                            </label>

                            <label class="space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Nomor HP</span>
                                <input v-model="customerPhone" name="customer_phone" required maxlength="30" type="tel" inputmode="numeric" pattern="[0-9]*" class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none transition focus:border-[#2563EB]" @input="onPhoneInput">
                            </label>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h2 class="text-[#1F2937]" style="font-size: 1.125rem; font-weight: 700;">Pilih Cabang & Paket</h2>
                        </div>

                        <div class="space-y-5 p-4 sm:p-6">
                            <label v-if="showBranchSelector" class="block space-y-1.5 text-sm">
                                <span class="text-[#1F2937]" style="font-weight: 500;">Cabang</span>
                                <select v-model="branchId" name="branch_id" required class="h-11 w-full rounded-xl border border-slate-300 px-3 text-sm outline-none transition focus:border-[#2563EB]">
                                    <option value="">Pilih cabang</option>
                                    <option v-for="branch in props.branches" :key="branch.id" :value="asString(branch.id)">
                                        {{ branch.name }}
                                    </option>
                                </select>
                            </label>
                            <input v-else type="hidden" name="branch_id" :value="branchId || asString(props.branches[0]?.id || '')">

                            <input type="hidden" name="package_id" :value="packageId">

                            <div class="grid gap-4 md:grid-cols-2">
                                <button
                                    v-for="item in filteredPackages"
                                    :key="item.id"
                                    type="button"
                                    class="rounded-2xl border p-4 text-left transition"
                                    :class="packageId === asString(item.id) ? 'border-[#2563EB] bg-[#2563EB]/5 shadow-sm' : 'border-slate-200 bg-white hover:border-[#2563EB]/40'"
                                    @click="selectPackage(item)"
                                >
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <h3 class="text-[#1F2937]" style="font-weight: 800;">{{ item.name }}</h3>
                                            <p class="mt-1 text-xs text-gray-500">{{ item.duration_minutes }} menit</p>
                                        </div>
                                        <span class="rounded-full bg-[#2563EB]/10 px-3 py-1 text-xs text-[#2563EB]" style="font-weight: 700;">{{ formatRupiah(item.base_price) }}</span>
                                    </div>
                                    <p v-if="item.description" class="mt-3 line-clamp-2 text-sm text-gray-500">{{ item.description }}</p>
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="overflow-hidden rounded-xl bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-6 py-5">
                            <h2 class="text-[#1F2937]" style="font-size: 1.125rem; font-weight: 700;">Add-on</h2>
                            <p class="mt-1 text-sm text-gray-500">Opsional, bisa dilewati.</p>
                        </div>

                        <div class="space-y-3 p-4 sm:p-6">
                            <p v-if="!packageId" class="text-sm text-gray-500">Pilih paket dulu untuk melihat add-on.</p>
                            <p v-else-if="filteredAddOns.length === 0" class="text-sm text-gray-500">Tidak ada add-on untuk paket ini.</p>

                            <div v-for="item in filteredAddOns" :key="item.id" class="flex items-center justify-between gap-4 rounded-xl border border-slate-200 p-4">
                                <div>
                                    <h3 class="text-sm text-[#1F2937]" style="font-weight: 700;">{{ item.name }}</h3>
                                    <p class="mt-1 text-sm text-gray-500">{{ formatRupiah(item.price) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="h-9 w-9 rounded-full border border-slate-300 text-lg" @click="reduceQty(item)">-</button>
                                    <span class="w-8 text-center text-sm" style="font-weight: 700;">{{ addonQty[asString(item.id)] || 0 }}</span>
                                    <button type="button" class="h-9 w-9 rounded-full border border-[#2563EB] bg-[#2563EB] text-lg text-white" @click="addQty(item)">+</button>
                                </div>
                            </div>

                            <template v-for="(item, index) in selectedAddOns" :key="`selected-${item.id}`">
                                <input type="hidden" :name="`addons[${index}][add_on_id]`" :value="item.id">
                                <input type="hidden" :name="`addons[${index}][qty]`" :value="item.qty">
                            </template>
                        </div>
                    </section>
                </div>

                <aside class="h-fit rounded-xl bg-white p-5 shadow-sm lg:sticky lg:top-24">
                    <h2 class="text-[#1F2937]" style="font-size: 1.125rem; font-weight: 800;">Ringkasan</h2>

                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-gray-500">Paket</span>
                            <span class="text-right text-[#1F2937]" style="font-weight: 600;">{{ selectedPackage?.name || '-' }}</span>
                        </div>
                        <div v-for="item in selectedAddOns" :key="`summary-${item.id}`" class="flex justify-between gap-4">
                            <span class="text-gray-500">{{ item.name }} x{{ item.qty }}</span>
                            <span class="text-[#1F2937]" style="font-weight: 600;">{{ formatRupiah(Number(item.price || 0) * item.qty) }}</span>
                        </div>
                        <div class="border-t border-dashed border-slate-300 pt-3">
                            <div class="flex justify-between text-base">
                                <span class="text-[#1F2937]" style="font-weight: 800;">Total</span>
                                <span class="text-[#1F2937]" style="font-weight: 900;">{{ formatRupiah(subtotal) }}</span>
                            </div>
                            <p class="mt-2 rounded-lg bg-amber-50 px-3 py-2 text-xs text-amber-800">Belum masuk antrean sebelum kasir menerima pembayaran tunai.</p>
                        </div>
                    </div>

                    <label class="mt-5 flex items-start gap-3 text-sm text-[#1F2937]">
                        <input v-model="termsAccepted" name="terms_accepted" type="checkbox" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-[#2563EB] focus:ring-[#2563EB]">
                        <span>Saya berada di lokasi dan akan membayar tunai di kasir hari ini.</span>
                    </label>

                    <button type="submit" :disabled="!canSubmit" class="mt-5 h-12 w-full rounded-xl bg-[#2563EB] text-sm text-white transition disabled:cursor-not-allowed disabled:bg-slate-300" style="font-weight: 800;">
                        {{ isSubmitting ? 'Mengirim...' : 'Dapatkan Kode Walk-in' }}
                    </button>
                </aside>
            </form>
        </main>
    </div>
</template>
