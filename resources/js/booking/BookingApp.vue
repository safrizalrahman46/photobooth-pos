<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PublicBookingNavbar from './PublicBookingNavbar.vue';
import BookingDatePicker from './BookingDatePicker.vue';

const props = defineProps({
    branches: {
        type: Array,
        default: () => [],
    },
    packages: {
        type: Array,
        default: () => [],
    },
    designCatalogs: {
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
    csrfToken: {
        type: String,
        required: true,
    },
});

const stepLabels = ['Paket', 'Tanggal', 'Waktu', 'Add-on'];

const addOnCatalog = [
    { id: 'extra-person', label: '+ 1 orang (include cetak 1 4R)', price: 15000 },
    { id: 'extra-print', label: '+ 1 cetak 4R', price: 15000 },
    { id: 'extra-time', label: '+ 5 menit durasi foto', price: 20000 },
    { id: 'costume', label: 'Sewa 1 kostum', price: 10000 },
    { id: 'ganci-bening', label: 'Ganci bening 1 pcs', price: 10000 },
    { id: 'ganci-besi', label: 'Ganci besi 1 pcs', price: 20000 },
    { id: 'diy', label: 'DIY 1 pcs', price: 5000 },
];

const addOnMax = {
    'extra-person': 5,
    'extra-print': 10,
    'extra-time': 3,
    costume: 5,
    'ganci-bening': 10,
    'ganci-besi': 10,
    diy: 10,
};

const packageAccentTokens = ['#2563eb', '#ec4899', '#22c55e', '#f59e0b', '#0ea5e9', '#8b5cf6'];

const packagePhotoCatalog = {
    basic: [
        {
            src: 'https://images.unsplash.com/photo-1638108413764-3ca5cefc242d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwaG90byUyMHN0dWRpbyUyMHBvcnRyYWl0JTIwYmFzaWMlMjBzZWxmaWUlMjBib290aHxlbnwxfHx8fDE3NzUzODgyMzl8MA&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Portrait Studio',
        },
        {
            src: 'https://images.unsplash.com/photo-1582510870942-6b1b57c94992?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxwaG90byUyMGJvb3RoJTIwZnJpZW5kcyUyMGdyb3VwJTIwcG9ydHJhaXQlMjBzdHVkaW98ZW58MXx8fHwxNzc1Mzg4MjQ0fDA&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Group Photo',
        },
        {
            src: 'https://images.unsplash.com/photo-1675979138868-374ff3176fc1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxzdHVkaW8lMjBwb3J0cmFpdCUyMHBob3RvZ3JhcGh5JTIwcmluZyUyMGxpZ2h0fGVufDF8fHx8MTc3NTM4ODUyNnww&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Ring Light Portrait',
        },
    ],
    'mandi-bola': [
        {
            src: 'https://images.unsplash.com/photo-1622600113744-fe36f577cb6b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxiYWxsJTIwcGl0JTIwY29sb3JmdWwlMjBmdW4lMjBwaG90b3Nob290fGVufDF8fHx8MTc3NTM4ODIzOXww&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Mandi Bola',
        },
        {
            src: 'https://images.unsplash.com/photo-1571513721963-d855fd8df4c2?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHx2aW50YWdlJTIwcmV0cm8lMjBwaG90b3Nob290JTIwYWVzdGhldGljJTIwc3R1ZGlvfGVufDF8fHx8MTc3NTM4ODI0NHww&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Vintage',
        },
        {
            src: 'https://images.unsplash.com/photo-1760727408754-c5c9ef169f8d?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxjb2xvcmZ1bCUyMGJhbGwlMjBwaXQlMjBjaGlsZHJlbiUyMHBsYXklMjBhZXN0aGV0aWN8ZW58MXx8fHwxNzc1Mzg4NTIxfDA&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Colorful Fun',
        },
    ],
    minimarket: [
        {
            src: 'https://images.unsplash.com/photo-1772113726165-623176411022?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxtaW5pbWFya2V0JTIwY29udmVuaWVuY2UlMjBzdG9yZSUyMGFlc3RoZXRpYyUyMHBob3Rvc2hvb3R8ZW58MXx8fHwxNzc1Mzg4MjM5fDA&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Minimarket',
        },
        {
            src: 'https://images.unsplash.com/photo-1645636511736-cb60b1bb0ce1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxzb2ZhJTIwY291Y2glMjBsaWZlc3R5bGUlMjBwaG90b3Nob290JTIwc3R1ZGlvfGVufDF8fHx8MTc3NTM4ODI0NHww&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Sofa',
        },
        {
            src: 'https://images.unsplash.com/photo-1745267199638-e74b14870044?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w3Nzg4Nzd8MHwxfHNlYXJjaHwxfHxncm9jZXJ5JTIwc3RvcmUlMjBhaXNsZSUyMHNoZWx2ZXMlMjBhZXN0aGV0aWN8ZW58MXx8fHwxNzc1Mzg4NTI2fDA&ixlib=rb-4.1.0&q=80&w=1080',
            label: 'Grocery Aesthetic',
        },
    ],
};

const asString = (value) => (value === null || value === undefined ? '' : String(value));
const normalizeTime = (value) => asString(value).slice(0, 5);

const now = new Date();
const minDate = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

const branchId = ref(asString(props.oldValues.branch_id));
const packageId = ref(asString(props.oldValues.package_id));
const designCatalogId = ref(asString(props.oldValues.design_catalog_id));
const bookingDate = ref(asString(props.oldValues.booking_date));
const bookingTime = ref(normalizeTime(props.oldValues.booking_time));
const customerName = ref(asString(props.oldValues.customer_name));
const customerPhone = ref(asString(props.oldValues.customer_phone));
const customerEmail = ref(asString(props.oldValues.customer_email));
const notes = ref(asString(props.oldValues.notes));

const addonQty = ref({});
const slots = ref([]);
const slotLoading = ref(false);
const slotMessage = ref('Pilih cabang, paket, dan tanggal untuk melihat slot.');
const submitError = ref('');

const isMobile = ref(false);
const activeMobileStep = ref(0);

let slotAbortController = null;

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

const filteredDesignCatalogs = computed(() => {
    if (!packageId.value) {
        return [];
    }

    return props.designCatalogs.filter((item) => asString(item.package_id) === asString(packageId.value));
});

const selectedPackage = computed(() => {
    return props.packages.find((item) => asString(item.id) === asString(packageId.value)) ?? null;
});

const showBranchSelector = computed(() => props.branches.length > 1);

const selectedPackagePhotoSet = computed(() => {
    const selected = selectedPackage.value;

    if (!selected) {
        return packagePhotoCatalog.basic;
    }

    const source = `${asString(selected.code)} ${asString(selected.name)}`.toLowerCase();

    if (source.includes('mandi bola') || source.includes('mandi-bola') || source.includes('vintage')) {
        return packagePhotoCatalog['mandi-bola'];
    }

    if (source.includes('mini') || source.includes('sofa') || source.includes('market')) {
        return packagePhotoCatalog.minimarket;
    }

    return packagePhotoCatalog.basic;
});

const selectedBookingDateObject = computed({
    get: () => {
        const value = asString(bookingDate.value);

        if (!value) {
            return null;
        }

        const date = new Date(`${value}T00:00:00`);

        if (Number.isNaN(date.getTime())) {
            return null;
        }

        return date;
    },
    set: (value) => {
        if (!value) {
            bookingDate.value = '';
            return;
        }

        const date = new Date(value);
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        bookingDate.value = `${year}-${month}-${day}`;
    },
});

const selectedBranch = computed(() => {
    return props.branches.find((item) => asString(item.id) === asString(branchId.value)) ?? null;
});

const activeAddons = computed(() => {
    return addOnCatalog.filter((item) => Number(addonQty.value[item.id] || 0) > 0);
});

const addOnTotal = computed(() => {
    return activeAddons.value.reduce((total, item) => {
        return total + item.price * Number(addonQty.value[item.id] || 0);
    }, 0);
});

const basePrice = computed(() => Number(selectedPackage.value?.base_price || 0));

const totalPrice = computed(() => basePrice.value + addOnTotal.value);

const totalPeople = computed(() => 2 + Number(addonQty.value['extra-person'] || 0));

const canSubmit = computed(() => {
    return Boolean(
        branchId.value
        && packageId.value
        && bookingDate.value
        && bookingTime.value
        && customerName.value.trim()
        && customerPhone.value.trim()
    );
});

const canAdvanceStep = computed(() => {
    if (activeMobileStep.value === 0) {
        return Boolean(branchId.value && packageId.value);
    }

    if (activeMobileStep.value === 1) {
        return Boolean(bookingDate.value);
    }

    if (activeMobileStep.value === 2) {
        return Boolean(bookingTime.value);
    }

    return true;
});

const formatRupiah = (value) => {
    const amount = Number(value || 0);
    return `Rp ${new Intl.NumberFormat('id-ID', {
        maximumFractionDigits: 0,
    }).format(Number.isNaN(amount) ? 0 : amount)}`;
};

const formatDuration = (durationMinutes) => {
    const duration = Number(durationMinutes || 0);
    if (!duration) {
        return '-';
    }

    return `${duration} menit`;
};

const formatLongDate = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return '-';
    }

    return value.toLocaleDateString('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
};

const formatShortDate = (value) => {
    if (!value) {
        return 'Belum dipilih';
    }

    const date = new Date(`${value}T00:00:00`);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleDateString('id-ID', {
        weekday: 'short',
        day: 'numeric',
        month: 'short',
    });
};

const packageAccent = (pkg, index) => {
    if (!pkg) {
        return packageAccentTokens[index % packageAccentTokens.length];
    }

    if (pkg?.code && pkg.code.length > 0) {
        const sum = pkg.code.split('').reduce((acc, char) => acc + char.charCodeAt(0), 0);
        return packageAccentTokens[sum % packageAccentTokens.length];
    }

    return packageAccentTokens[index % packageAccentTokens.length];
};

const slotRange = (slot) => {
    const start = normalizeTime(slot.start_label || slot.start_time);
    const end = normalizeTime(slot.end_label || slot.end_time);

    return `${start} - ${end}`;
};

const slotKey = (slot) => {
    if (slot.slot_id !== undefined && slot.slot_id !== null) {
        return asString(slot.slot_id);
    }

    return `${slot.start_time}-${slot.end_time}`;
};

const slotStart = (slot) => normalizeTime(slot.start_time || slot.start_label);

const updateMobileState = () => {
    isMobile.value = window.innerWidth < 1024;
};

const resetSlots = (message) => {
    slots.value = [];
    bookingTime.value = '';
    slotLoading.value = false;
    slotMessage.value = message;
};

const loadAvailability = async () => {
    if (!branchId.value || !packageId.value || !bookingDate.value) {
        resetSlots('Pilih cabang, paket, dan tanggal untuk melihat slot.');
        return;
    }

    if (slotAbortController) {
        slotAbortController.abort();
    }

    const controller = new AbortController();
    slotAbortController = controller;

    slotLoading.value = true;
    slotMessage.value = 'Memuat slot...';

    const params = new URLSearchParams({
        branch_id: asString(branchId.value),
        package_id: asString(packageId.value),
        date: bookingDate.value,
    });

    try {
        const response = await fetch(`${props.routes.availability}?${params.toString()}`, {
            method: 'GET',
            headers: {
                Accept: 'application/json',
            },
            signal: controller.signal,
        });

        const payload = await response.json();

        if (!response.ok || !payload?.success) {
            slots.value = [];
            bookingTime.value = '';
            slotMessage.value = payload?.message || 'Gagal memuat slot untuk jadwal ini.';
            return;
        }

        const loadedSlots = Array.isArray(payload.data) ? payload.data : [];
        slots.value = loadedSlots;

        if (!loadedSlots.length) {
            bookingTime.value = '';
            slotMessage.value = 'Tidak ada slot tersedia pada kombinasi ini.';
            return;
        }

        slotMessage.value = 'Klik salah satu jam untuk memilih slot.';

        const existing = loadedSlots.find((item) => slotStart(item) === bookingTime.value && item.is_available);

        if (!existing) {
            bookingTime.value = '';
        }
    } catch (error) {
        if (error?.name === 'AbortError') {
            return;
        }

        slots.value = [];
        bookingTime.value = '';
        slotMessage.value = 'Tidak dapat memuat slot saat ini.';
    } finally {
        if (slotAbortController === controller) {
            slotLoading.value = false;
        }
    }
};

const choosePackage = (id) => {
    packageId.value = asString(id);
};

const chooseSlot = (slot) => {
    if (!slot?.is_available) {
        return;
    }

    bookingTime.value = slotStart(slot);
};

const incAddon = (addonId) => {
    const current = Number(addonQty.value[addonId] || 0);
    addonQty.value = {
        ...addonQty.value,
        [addonId]: Math.min(current + 1, Number(addOnMax[addonId] || 5)),
    };
};

const decAddon = (addonId) => {
    const current = Number(addonQty.value[addonId] || 0);

    if (current <= 1) {
        const clone = { ...addonQty.value };
        delete clone[addonId];
        addonQty.value = clone;
        return;
    }

    addonQty.value = {
        ...addonQty.value,
        [addonId]: current - 1,
    };
};

const nextStep = () => {
    if (activeMobileStep.value >= stepLabels.length - 1) {
        return;
    }

    if (!canAdvanceStep.value) {
        return;
    }

    activeMobileStep.value += 1;
};

const prevStep = () => {
    if (activeMobileStep.value <= 0) {
        return;
    }

    activeMobileStep.value -= 1;
};

const validateBeforeSubmit = () => {
    if (!branchId.value) {
        submitError.value = 'Cabang harus dipilih.';
        activeMobileStep.value = 0;
        return false;
    }

    if (!packageId.value) {
        submitError.value = 'Paket harus dipilih.';
        activeMobileStep.value = 0;
        return false;
    }

    if (!bookingDate.value) {
        submitError.value = 'Tanggal booking harus dipilih.';
        activeMobileStep.value = 1;
        return false;
    }

    if (!bookingTime.value) {
        submitError.value = 'Silakan pilih slot waktu yang tersedia.';
        activeMobileStep.value = 2;
        return false;
    }

    if (!customerName.value.trim()) {
        submitError.value = 'Nama pemesan harus diisi.';
        activeMobileStep.value = 3;
        return false;
    }

    if (!customerPhone.value.trim()) {
        submitError.value = 'Nomor HP harus diisi.';
        activeMobileStep.value = 3;
        return false;
    }

    submitError.value = '';
    return true;
};

const handleSubmit = (event) => {
    if (!validateBeforeSubmit()) {
        event.preventDefault();
    }
};

watch(branchId, () => {
    const packageStillAllowed = filteredPackages.value.some((item) => asString(item.id) === asString(packageId.value));

    if (!packageStillAllowed) {
        packageId.value = '';
        designCatalogId.value = '';
    }

    submitError.value = '';
});

watch(packageId, () => {
    const designAllowed = filteredDesignCatalogs.value.some((item) => asString(item.id) === asString(designCatalogId.value));

    if (!designAllowed) {
        designCatalogId.value = '';
    }

    submitError.value = '';
});

watch([branchId, packageId, bookingDate], () => {
    loadAvailability();
}, { immediate: true });

onMounted(() => {
    if (!branchId.value && props.branches.length) {
        branchId.value = asString(props.branches[0].id);
    }

    updateMobileState();
    window.addEventListener('resize', updateMobileState);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', updateMobileState);

    if (slotAbortController) {
        slotAbortController.abort();
    }
});
</script>

<template>
    <div class="min-h-[calc(100vh-4rem)] bg-[#F8FAFC]">
        <PublicBookingNavbar :routes="props.routes" />

        <div class="pointer-events-none absolute inset-0 overflow-hidden">
            <div class="absolute -top-20 -right-20 h-64 w-64 rounded-full bg-[#2563EB]/5" />
            <div class="absolute top-60 -left-10 h-32 w-32 rotate-45 rounded-lg bg-[#60A5FA]/5" />
            <div class="absolute bottom-40 right-1/4 h-24 w-24 rounded-full bg-[#EC4899]/5" />
            <div class="absolute top-1/3 right-10 h-16 w-16 rotate-12 rounded-md bg-[#22C55E]/5" />
        </div>

        <main class="relative mx-auto w-full max-w-7xl px-4 pb-40 pt-8 sm:px-6 sm:pb-28">
            <div class="mb-8">
                <h1 class="text-[#1F2937]" style="font-size: 1.75rem; font-weight: 700;">Booking Sesi Foto</h1>
                <p class="mt-1 text-gray-500" style="font-size: 0.875rem;">Pilih paket, tanggal, dan waktu untuk reservasi sesi foto kamu</p>
            </div>

            <form :action="props.routes.payment" method="post" @submit="handleSubmit">
                <input type="hidden" name="_token" :value="props.csrfToken">
                <input type="hidden" name="branch_id" :value="branchId">
                <input type="hidden" name="package_id" :value="packageId">
                <input type="hidden" name="design_catalog_id" :value="designCatalogId">
                <input type="hidden" name="booking_date" :value="bookingDate">
                <input type="hidden" name="booking_time" :value="bookingTime">
                <input type="hidden" name="customer_name" :value="customerName">
                <input type="hidden" name="customer_phone" :value="customerPhone">
                <input type="hidden" name="customer_email" :value="customerEmail">
                <input type="hidden" name="notes" :value="notes">

                <div
                    v-if="props.errors.length || submitError"
                    class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                >
                    <ul class="space-y-1">
                        <li v-for="(message, index) in props.errors" :key="`server-${index}`">
                            {{ message }}
                        </li>
                        <li v-if="submitError">{{ submitError }}</li>
                    </ul>
                </div>

                <div v-if="showBranchSelector" class="mb-6 rounded-xl border-0 bg-white p-4 shadow-sm sm:p-6">
                    <div class="grid gap-4 sm:grid-cols-[1fr_auto] sm:items-end">
                        <label class="space-y-1.5 text-sm">
                            <span class="text-[#1F2937]" style="font-weight: 500;">Cabang</span>
                            <select
                                v-model="branchId"
                                required
                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                            >
                                <option value="">Pilih cabang</option>
                                <option v-for="branch in props.branches" :key="branch.id" :value="String(branch.id)">
                                    {{ branch.name }}
                                </option>
                            </select>
                        </label>

                        <div class="rounded-xl bg-[#F8FAFC] px-3 py-2 text-xs text-gray-500 sm:max-w-[260px]">
                            <p class="text-[#1F2937]" style="font-weight: 600;">Alamat cabang</p>
                            <p class="mt-1">{{ selectedBranch?.address || 'Pilih cabang untuk melihat detail lokasi.' }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="isMobile" class="mb-6 space-y-3 lg:hidden">
                    <div class="flex items-center gap-1.5">
                        <div
                            v-for="(label, index) in stepLabels"
                            :key="`step-progress-${label}`"
                            class="h-1.5 flex-1 rounded-full transition-all duration-300"
                            :class="index <= activeMobileStep ? 'bg-[#2563EB]' : 'bg-gray-200'"
                            :style="index === activeMobileStep ? { opacity: 0.55 } : undefined"
                        />
                    </div>
                    <div class="flex items-center justify-between rounded-xl bg-[#1F2937] px-3 py-2 text-xs text-white">
                        <span>Langkah {{ activeMobileStep + 1 }} dari {{ stepLabels.length }}</span>
                        <span style="font-weight: 600;">{{ stepLabels[activeMobileStep] }}</span>
                    </div>
                </div>

                <div class="grid gap-8 lg:grid-cols-[1fr_380px]">
                    <div class="space-y-6">
                        <section
                            v-show="!isMobile || activeMobileStep === 0"
                            class="overflow-hidden rounded-xl border-0 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-200 px-6 pb-6 pt-6">
                                <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8l7-5h8l7 5z" />
                                        <circle cx="12" cy="13" r="4" />
                                    </svg>
                                    Pilih Paket
                                </h2>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div class="grid gap-3 sm:grid-cols-3">
                                    <button
                                        v-for="(pkg, index) in filteredPackages"
                                        :key="pkg.id"
                                        type="button"
                                        class="relative rounded-xl border-2 p-4 text-left transition-all duration-200"
                                        :class="packageId === String(pkg.id)
                                            ? 'scale-[1.02] shadow-md'
                                            : 'border-slate-200 bg-white hover:border-slate-400 hover:shadow-sm'"
                                        :style="packageId === String(pkg.id)
                                            ? {
                                                borderColor: packageAccent(pkg, index),
                                                backgroundColor: `${packageAccent(pkg, index)}08`,
                                                boxShadow: `0 4px 14px ${packageAccent(pkg, index)}20`,
                                            }
                                            : undefined"
                                        @click="choosePackage(pkg.id)"
                                    >
                                        <div
                                            v-if="packageId === String(pkg.id)"
                                            class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full text-white"
                                            :style="{ backgroundColor: packageAccent(pkg, index) }"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M20 6L9 17l-5-5" />
                                            </svg>
                                        </div>

                                        <div
                                            class="mb-2 flex h-8 w-8 items-center justify-center rounded-lg"
                                            :style="{ backgroundColor: `${packageAccent(pkg, index)}15`, color: packageAccent(pkg, index) }"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8l7-5h8l7 5z" />
                                                <circle cx="12" cy="13" r="4" />
                                            </svg>
                                        </div>

                                        <p class="mb-1 text-[#1F2937]" style="font-size: 0.875rem; font-weight: 600;">{{ pkg.name }}</p>
                                        <p :style="{ fontSize: '1.125rem', fontWeight: 700, color: packageAccent(pkg, index) }">
                                            {{ formatRupiah(pkg.base_price) }}
                                            <span class="text-gray-400" style="font-size: 0.7rem; font-weight: 400;"> / sesi</span>
                                        </p>

                                        <div class="mt-3 space-y-1">
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">1–2 orang</div>
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">Durasi {{ formatDuration(pkg.duration_minutes) }}</div>
                                            <div class="flex items-center gap-1.5 text-xs text-gray-500">1 cetak 4R + all soft file</div>
                                        </div>

                                        <div class="mt-2 w-fit rounded-md bg-gray-100 px-2 py-0.5 text-[0.65rem] text-gray-400">
                                            {{ index % 2 === 0 ? 'Device 1' : 'Device 2' }}
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </section>

                        <section
                            v-show="!isMobile || activeMobileStep === 1"
                            class="overflow-visible rounded-xl border-0 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-200 px-6 pb-6 pt-6">
                                <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                    Tanggal Booking
                                </h2>
                            </div>

                            <div class="space-y-4 p-4 sm:p-6">
                                <BookingDatePicker
                                    v-model="selectedBookingDateObject"
                                    :min-date="new Date(`${minDate}T00:00:00`)"
                                />

                                <div
                                    v-if="selectedBookingDateObject"
                                    class="flex items-center gap-2 rounded-xl bg-[#2563EB]/5 p-3 text-sm text-[#2563EB]"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                    <span style="font-weight: 500;">{{ formatLongDate(selectedBookingDateObject) }}</span>
                                </div>
                            </div>
                        </section>

                        <section
                            v-show="!isMobile || activeMobileStep === 2"
                            class="overflow-hidden rounded-xl border-0 bg-white shadow-sm"
                        >
                            <div class="border-b border-slate-200 px-6 pb-6 pt-6">
                                <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                    Pilih Waktu
                                </h2>
                            </div>

                            <div class="p-4 sm:p-6">
                                <div v-if="slotLoading" class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                                    <div
                                        v-for="n in 18"
                                        :key="`slot-skeleton-${n}`"
                                        class="h-10 animate-pulse rounded-lg bg-gray-100"
                                    />
                                </div>

                                <div v-else-if="!bookingDate" class="flex flex-col items-center justify-center py-10 text-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="mb-3 h-10 w-10 text-gray-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                                        <line x1="16" y1="2" x2="16" y2="6" />
                                        <line x1="8" y1="2" x2="8" y2="6" />
                                        <line x1="3" y1="10" x2="21" y2="10" />
                                    </svg>
                                    <p class="text-sm text-gray-400" style="font-weight: 500;">Pilih tanggal terlebih dahulu</p>
                                </div>

                                <template v-else>
                                    <div class="mb-4 flex flex-wrap gap-4 text-xs text-gray-500">
                                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded border border-[#2563EB]/30" /> Tersedia</span>
                                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-[#2563EB]" /> Dipilih</span>
                                        <span class="flex items-center gap-1.5"><span class="h-3 w-3 rounded bg-gray-200" /> Penuh</span>
                                    </div>

                                    <div v-if="slots.length" class="grid grid-cols-3 gap-2 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                                        <button
                                            v-for="slot in slots"
                                            :key="slotKey(slot)"
                                            type="button"
                                            class="min-h-[44px] rounded-lg border px-3 py-3 text-sm transition-all duration-200"
                                            :class="!slot.is_available
                                                ? 'cursor-not-allowed border-gray-200 bg-gray-100 text-gray-400 opacity-60'
                                                : bookingTime === slotStart(slot)
                                                    ? 'scale-[1.02] border-[#2563EB] bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/25'
                                                    : 'border-[#2563EB]/30 bg-white text-[#2563EB] hover:border-[#2563EB] hover:bg-[#2563EB]/5 hover:shadow-sm'"
                                            :disabled="!slot.is_available"
                                            @click="chooseSlot(slot)"
                                        >
                                            {{ slotStart(slot) }}
                                        </button>
                                    </div>

                                    <p v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-500">
                                        {{ slotMessage }}
                                    </p>
                                </template>
                            </div>
                        </section>

                        <section
                            v-show="!isMobile || activeMobileStep === 3"
                            class="overflow-hidden rounded-xl border-0 bg-white shadow-sm"
                        >
                            <div class="border-b border-dashed border-slate-200 px-6 pb-6 pt-6">
                                <h2 class="flex items-center gap-2 text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-[#F59E0B]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 2v20" />
                                        <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6" />
                                    </svg>
                                    Add-on (Opsional)
                                </h2>
                            </div>

                            <div class="space-y-4 p-4 sm:p-6">
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <div
                                        v-for="addon in addOnCatalog"
                                        :key="addon.id"
                                        class="flex items-center justify-between rounded-xl border border-slate-300 px-4 py-3 transition-all duration-200"
                                        :class="Number(addonQty[addon.id] || 0) > 0 ? 'border-[#2563EB] bg-[#2563EB]/5' : 'border-gray-200 bg-white'"
                                    >
                                        <div class="mr-3 min-w-0 flex-1">
                                            <p class="truncate text-sm text-[#1F2937]">{{ addon.label }}</p>
                                            <p class="text-xs text-[#2563EB]" style="font-weight: 600;">
                                                {{ formatRupiah(addon.price) }}
                                                <span class="text-gray-400" style="font-weight: 400;"> / item</span>
                                            </p>
                                        </div>

                                        <div class="flex shrink-0 items-center gap-1">
                                            <button
                                                type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg transition-all"
                                                :class="Number(addonQty[addon.id] || 0) > 0 ? 'bg-[#2563EB]/10 text-[#2563EB] hover:bg-[#2563EB]/20' : 'cursor-not-allowed bg-gray-100 text-gray-300'"
                                                :disabled="Number(addonQty[addon.id] || 0) === 0"
                                                @click="decAddon(addon.id)"
                                            >
                                                -
                                            </button>
                                            <span class="w-8 text-center text-sm" :class="Number(addonQty[addon.id] || 0) > 0 ? 'text-[#1F2937]' : 'text-gray-400'" style="font-weight: 600;">{{ Number(addonQty[addon.id] || 0) }}</span>
                                            <button
                                                type="button"
                                                class="flex h-8 w-8 items-center justify-center rounded-lg transition-all"
                                                :class="Number(addonQty[addon.id] || 0) >= Number(addOnMax[addon.id] || 5) ? 'cursor-not-allowed bg-gray-100 text-gray-300' : 'bg-[#2563EB] text-white shadow-sm hover:bg-[#2563EB]/90'"
                                                :disabled="Number(addonQty[addon.id] || 0) >= Number(addOnMax[addon.id] || 5)"
                                                @click="incAddon(addon.id)"
                                            >
                                                +
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4 rounded-xl border border-gray-100 bg-gray-50/80 p-4">
                                    <h3 class="text-[#1F2937]" style="font-size: 0.875rem; font-weight: 600;">Data Pemesan</h3>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="space-y-1.5 text-sm">
                                            <span class="text-[#1F2937]" style="font-weight: 500;">Nama Pemesan</span>
                                            <input
                                                v-model="customerName"
                                                required
                                                maxlength="120"
                                                type="text"
                                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                            >
                                        </label>

                                        <label class="space-y-1.5 text-sm">
                                            <span class="text-[#1F2937]" style="font-weight: 500;">Nomor HP</span>
                                            <input
                                                v-model="customerPhone"
                                                required
                                                maxlength="30"
                                                type="text"
                                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                            >
                                        </label>
                                    </div>

                                    <div class="grid gap-4 sm:grid-cols-2">
                                        <label class="space-y-1.5 text-sm">
                                            <span class="text-[#1F2937]" style="font-weight: 500;">Email (opsional)</span>
                                            <input
                                                v-model="customerEmail"
                                                type="email"
                                                class="h-11 w-full rounded-xl border border-gray-200 bg-white px-3 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                            >
                                        </label>

                                        <label class="space-y-1.5 text-sm">
                                            <span class="text-[#1F2937]" style="font-weight: 500;">Catatan (opsional)</span>
                                            <textarea
                                                v-model="notes"
                                                maxlength="1000"
                                                rows="2"
                                                class="w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-[#1F2937] outline-none transition focus:border-[#2563EB]"
                                            ></textarea>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <aside class="hidden lg:sticky lg:top-20 lg:block lg:self-start">
                        <div class="overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                            <div class="bg-gradient-to-br from-[#2563EB] to-[#3B82F6] px-6 pb-6 pt-6">
                                <h2 class="text-white" style="font-size: 1.125rem; font-weight: 600;">Ringkasan Booking</h2>
                            </div>

                            <div class="space-y-3 p-6">
                                <div class="rounded-lg border border-slate-200 bg-gray-50 p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Paket</span>
                                        <span
                                            class="rounded-full px-2 py-0.5 text-sm text-white"
                                            :style="{ backgroundColor: packageAccent(selectedPackage, 0), fontWeight: 500 }"
                                        >
                                            {{ selectedPackage?.name || '-' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between border border-slate-300 rounded-lg bg-gray-50 p-3">
                                    <span class="text-sm text-gray-600">Tanggal</span>
                                    <span class="text-sm text-[#1F2937]" style="font-weight: 600;">{{ formatShortDate(bookingDate) }}</span>
                                </div>

                                <div class="flex items-center justify-between border border-slate-300 rounded-lg bg-gray-50 p-3">
                                    <span class="text-sm text-gray-600">Waktu</span>
                                    <span class="text-sm text-[#1F2937]" style="font-weight: 600;">{{ bookingTime || 'Belum dipilih' }}</span>
                                </div>

                                <div class="flex items-center justify-between border border-slate-300 rounded-lg bg-gray-50 p-3">
                                    <span class="text-sm text-gray-600">Jumlah Orang</span>
                                    <span class="text-sm text-[#1F2937]" style="font-weight: 600;">{{ totalPeople }} orang</span>
                                </div>

                                <div v-if="selectedBranch" class="flex items-center justify-between border border-slate-300 rounded-lg bg-gray-700 p-3">
                                    <span class="text-sm text-gray-600">Cabang</span>
                                    <span class="text-sm text-[#1F2937]" style="font-weight: 600;">{{ selectedBranch.name }}</span>
                                </div>

                                <div v-if="activeAddons.length" class="rounded-lg border border-slate-00 bg-gray-50 p-3">
                                    <p class="mb-1.5 text-sm text-gray-500">Add-on:</p>
                                    <div
                                        v-for="addon in activeAddons"
                                        :key="`desktop-addon-${addon.id}`"
                                        class="flex justify-between text-sm text-gray-600"
                                    >
                                        <span>{{ addon.label }} x{{ Number(addonQty[addon.id] || 0) }}</span>
                                        <span style="font-weight: 500;">{{ formatRupiah(addon.price * Number(addonQty[addon.id] || 0)) }}</span>
                                    </div>
                                </div>

                                <div class="rounded-lg border border-dashed border-slate-400 p-3">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-600">Total</span>
                                        <span class="text-[#1F2937]" style="font-size: 1.25rem; font-weight: 700;">{{ formatRupiah(totalPrice) }}</span>
                                    </div>
                                </div>

                                <p v-if="bookingTime" class="flex items-center gap-2 rounded-lg bg-[#F59E0B]/10 p-3 text-xs text-[#F59E0B]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10" />
                                        <path d="M12 6v6l4 2" />
                                    </svg>
                                    Slot ditahan 10 menit. Selesaikan pembayaran untuk konfirmasi.
                                </p>

                                <button
                                    type="submit"
                                    class="h-11 w-full rounded-xl bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/20 transition hover:bg-[#2563EB]/90"
                                    :disabled="!canSubmit"
                                    :class="canSubmit ? '' : 'cursor-not-allowed opacity-70'"
                                >
                                    Lanjut ke Pembayaran
                                </button>
                            </div>
                        </div>

                        <div class="mt-4 overflow-hidden rounded-xl border-0 bg-white shadow-sm">
                            <div class="border-b border-slate-300 bg-white px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <h3 class="flex items-center gap-2 text-[#1F2937]" style="font-size: 0.875rem; font-weight: 600;">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-[#2563EB]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                                            <circle cx="8.5" cy="8.5" r="1.5" />
                                            <path d="M21 15l-5-5L5 21" />
                                        </svg>
                                        Hasil Foto - {{ selectedPackage?.name || 'Paket' }}
                                    </h3>
                                    <span class="flex items-center gap-1 text-[0.65rem] text-gray-400">geser</span>
                                </div>
                            </div>
                            <div class="p-0">
                                <div
                                    class="photo-scroll flex snap-x snap-mandatory gap-3 overflow-x-auto px-3 py-3"
                                    style="-ms-overflow-style: none; scrollbar-width: none;"
                                >
                                    <div
                                        v-for="(photo, index) in selectedPackagePhotoSet"
                                        :key="`photo-${index}`"
                                        class="group relative h-[200px] w-[280px] flex-shrink-0 snap-center overflow-hidden rounded-xl"
                                    >
                                        <img :src="photo.src" :alt="photo.label" class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105">
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent" />
                                        <div class="absolute inset-x-0 bottom-0 px-3 pb-3">
                                            <span class="text-xs text-white" style="font-weight: 600;">{{ photo.label }}</span>
                                            <p class="mt-0.5 text-[0.6rem] text-white/70">Paket {{ selectedPackage?.name || '-' }}</p>
                                        </div>
                                        <div class="absolute right-2 top-2 rounded-full bg-black/40 px-2 py-0.5 text-[0.6rem] text-white">
                                            {{ index + 1 }}/{{ selectedPackagePhotoSet.length }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex justify-center gap-1.5 pb-3">
                                    <div
                                        v-for="(photo, index) in selectedPackagePhotoSet"
                                        :key="`photo-indicator-${index}`"
                                        class="h-1.5 rounded-full bg-gray-200 transition-all"
                                        :style="index === 0 ? { width: '16px', backgroundColor: '#2563EB' } : { width: '6px' }"
                                    />
                                </div>
                            </div>
                        </div>
                    </aside>
                </div>

                <div v-if="isMobile" class="fixed inset-x-0 bottom-0 z-40 border-t border-gray-100 bg-white/95 px-4 pb-[calc(0.75rem+env(safe-area-inset-bottom,0px))] pt-3 backdrop-blur-lg lg:hidden">
                    <div class="mb-3 flex flex-wrap items-center gap-2 text-xs text-gray-500">
                        <span
                            class="rounded-full px-2 py-0.5 text-white"
                            :style="{ backgroundColor: packageAccent(selectedPackage, 0), fontSize: '0.65rem', fontWeight: 500 }"
                        >
                            {{ selectedPackage?.name || 'Paket' }}
                        </span>
                        <span v-if="bookingDate" class="rounded-full bg-gray-100 px-2 py-0.5" style="font-size: 0.65rem;">{{ formatShortDate(bookingDate) }}</span>
                        <span v-if="bookingTime" class="rounded-full bg-gray-100 px-2 py-0.5" style="font-size: 0.65rem;">{{ bookingTime }}</span>
                        <span v-if="activeAddons.length" class="rounded-full bg-gray-100 px-2 py-0.5" style="font-size: 0.65rem;">
                            +{{ activeAddons.reduce((sum, item) => sum + Number(addonQty[item.id] || 0), 0) }} add-on
                        </span>
                        <span class="ml-auto text-[#1F2937]" style="font-size: 0.8rem; font-weight: 700;">{{ formatRupiah(totalPrice) }}</span>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="inline-flex h-12 items-center justify-center rounded-xl border border-gray-200 px-4 text-sm text-gray-600 transition hover:bg-gray-100"
                            :class="activeMobileStep === 0 ? 'cursor-not-allowed opacity-50' : ''"
                            :disabled="activeMobileStep === 0"
                            @click="prevStep"
                        >
                            Kembali
                        </button>

                        <button
                            v-if="activeMobileStep < stepLabels.length - 1"
                            type="button"
                            class="inline-flex h-12 flex-1 items-center justify-center rounded-xl bg-[#2563EB] px-4 text-sm text-white transition"
                            :class="canAdvanceStep ? 'hover:bg-[#2563EB]/90' : 'cursor-not-allowed bg-slate-300'"
                            :disabled="!canAdvanceStep"
                            @click="nextStep"
                        >
                            Lanjut
                        </button>

                        <button
                            v-else
                            type="submit"
                            class="inline-flex h-12 flex-1 items-center justify-center rounded-xl bg-[#2563EB] px-4 text-sm text-white shadow-md shadow-[#2563EB]/20 transition"
                            :class="canSubmit ? 'hover:bg-[#2563EB]/90' : 'cursor-not-allowed opacity-70'"
                            :disabled="!canSubmit"
                        >
                            Lanjut ke Pembayaran
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</template>

<style scoped>
.photo-scroll::-webkit-scrollbar {
    display: none;
}
</style>
