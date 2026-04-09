<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const MONTHS_ID = [
    'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember',
];

const DAYS_ID = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const props = defineProps({
    modelValue: {
        type: Date,
        default: null,
    },
    minDate: {
        type: Date,
        default: null,
    },
});

const emit = defineEmits(['update:modelValue']);

const containerRef = ref(null);
const open = ref(false);
const isMobile = ref(false);

const today = new Date();
today.setHours(0, 0, 0, 0);

const toDayStamp = (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return Number.NaN;
    }

    return Date.UTC(value.getFullYear(), value.getMonth(), value.getDate());
};

const fallbackDate = props.modelValue ? new Date(props.modelValue) : new Date();
const viewYear = ref(fallbackDate.getFullYear());
const viewMonth = ref(fallbackDate.getMonth());

const normalizedMinDate = computed(() => {
    if (!(props.minDate instanceof Date) || Number.isNaN(props.minDate.getTime())) {
        return null;
    }

    const date = new Date(props.minDate);
    date.setHours(0, 0, 0, 0);
    return date;
});

const effectiveMinDate = computed(() => {
    const baseDate = new Date(today);

    if (normalizedMinDate.value && normalizedMinDate.value > baseDate) {
        return normalizedMinDate.value;
    }

    return baseDate;
});

const normalizedValue = computed(() => {
    if (!(props.modelValue instanceof Date) || Number.isNaN(props.modelValue.getTime())) {
        return null;
    }

    const date = new Date(props.modelValue);
    date.setHours(0, 0, 0, 0);
    return date;
});

const displayText = computed(() => {
    if (!normalizedValue.value) {
        return 'Pilih tanggal';
    }

    return `${normalizedValue.value.getDate()} ${MONTHS_ID[normalizedValue.value.getMonth()]} ${normalizedValue.value.getFullYear()}`;
});

const isSameDay = (first, second) => {
    return first.getFullYear() === second.getFullYear()
        && first.getMonth() === second.getMonth()
        && first.getDate() === second.getDate();
};

const getDaysInMonth = (year, month) => {
    return new Date(year, month + 1, 0).getDate();
};

const getFirstDayOfMonth = (year, month) => {
    const day = new Date(year, month, 1).getDay();
    return day === 0 ? 6 : day - 1;
};

const allDays = computed(() => {
    const year = viewYear.value;
    const month = viewMonth.value;

    const daysInMonth = getDaysInMonth(year, month);
    const firstDay = getFirstDayOfMonth(year, month);

    const prevMonthDays = getDaysInMonth(year, month - 1);
    const trailingDays = Array.from({ length: firstDay }, (_, index) => ({
        day: prevMonthDays - firstDay + 1 + index,
        current: false,
        date: new Date(year, month - 1, prevMonthDays - firstDay + 1 + index),
    }));

    const currentDays = Array.from({ length: daysInMonth }, (_, index) => ({
        day: index + 1,
        current: true,
        date: new Date(year, month, index + 1),
    }));

    const totalCells = trailingDays.length + currentDays.length;
    const remaining = totalCells % 7 === 0 ? 0 : 7 - (totalCells % 7);

    const leadingDays = Array.from({ length: remaining }, (_, index) => ({
        day: index + 1,
        current: false,
        date: new Date(year, month + 1, index + 1),
    }));

    return [...trailingDays, ...currentDays, ...leadingDays];
});

const checkMobile = () => {
    isMobile.value = window.innerWidth < 640;
};

const closePicker = () => {
    open.value = false;
};

const openPicker = () => {
    if (normalizedValue.value) {
        viewYear.value = normalizedValue.value.getFullYear();
        viewMonth.value = normalizedValue.value.getMonth();
    }

    open.value = !open.value;
};

const goToPrevMonth = () => {
    if (viewMonth.value === 0) {
        viewMonth.value = 11;
        viewYear.value -= 1;
        return;
    }

    viewMonth.value -= 1;
};

const goToNextMonth = () => {
    if (viewMonth.value === 11) {
        viewMonth.value = 0;
        viewYear.value += 1;
        return;
    }

    viewMonth.value += 1;
};

const isDisabled = (value) => {
    const valueStamp = toDayStamp(value);
    const minStamp = toDayStamp(effectiveMinDate.value);

    if (Number.isNaN(valueStamp) || Number.isNaN(minStamp)) {
        return false;
    }

    return valueStamp < minStamp;
};

const canSelectToday = computed(() => !isDisabled(today));

const handleSelect = (entry) => {
    if (isDisabled(entry.date)) {
        return;
    }

    emit('update:modelValue', new Date(entry.date));
    closePicker();
};

const selectToday = () => {
    emit('update:modelValue', new Date(today));
    closePicker();
};

const selectTomorrow = () => {
    const value = new Date(today);
    value.setDate(value.getDate() + 1);
    emit('update:modelValue', value);
    closePicker();
};

const selectNextWeek = () => {
    const value = new Date(today);
    value.setDate(value.getDate() + 7);
    emit('update:modelValue', value);
    closePicker();
};

const handleDocumentClick = (event) => {
    if (!open.value || isMobile.value) {
        return;
    }

    if (containerRef.value && !containerRef.value.contains(event.target)) {
        closePicker();
    }
};

watch(() => props.modelValue, (value) => {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) {
        return;
    }

    viewYear.value = value.getFullYear();
    viewMonth.value = value.getMonth();
});

watch([open, isMobile], ([isOpen, mobile]) => {
    if (!mobile) {
        document.body.style.overflow = '';
        return;
    }

    document.body.style.overflow = isOpen ? 'hidden' : '';
});

onMounted(() => {
    checkMobile();
    window.addEventListener('resize', checkMobile);
    document.addEventListener('mousedown', handleDocumentClick);
});

onBeforeUnmount(() => {
    window.removeEventListener('resize', checkMobile);
    document.removeEventListener('mousedown', handleDocumentClick);
    document.body.style.overflow = '';
});
</script>

<template>
    <div ref="containerRef" class="relative">
        <button
            type="button"
            class="flex w-full items-center gap-3 rounded-xl border bg-[#FFFBF0] px-4 py-3.5 text-left transition-all duration-200"
            :class="open
                ? 'border-[#2563EB] shadow-[0_0_0_3px_rgba(37,99,235,0.1)]'
                : 'border-[#E5D5B0] hover:border-[#D4C49A] hover:shadow-sm'"
            @click="openPicker"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="h-5 w-5 shrink-0 transition-colors"
                :class="open ? 'text-[#2563EB]' : 'text-[#9CA3AF]'"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true"
            >
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2" />
                <line x1="16" y1="2" x2="16" y2="6" />
                <line x1="8" y1="2" x2="8" y2="6" />
                <line x1="3" y1="10" x2="21" y2="10" />
            </svg>

            <span
                :class="normalizedValue ? 'text-[#1F2937]' : 'text-[#9CA3AF]'"
                style="font-size: 0.9375rem; font-weight: 500;"
            >
                {{ displayText }}
            </span>
        </button>

        <div
            v-if="open && !isMobile"
            class="absolute left-0 right-0 z-[60] mt-2 rounded-2xl border border-slate-300 bg-white p-5 shadow-xl shadow-black/10"
            style="min-width: 320px;"
        >
            <div class="mb-4 flex items-center justify-between">
                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-gray-400 transition-all hover:bg-[#2563EB]/5 hover:text-[#2563EB] active:scale-95 sm:h-9 sm:w-9"
                    @click="goToPrevMonth"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M15 18l-6-6 6-6" />
                    </svg>
                </button>

                <div class="text-center">
                    <span class="text-[#1F2937]" style="font-size: 1rem; font-weight: 600;">{{ MONTHS_ID[viewMonth] }}</span>
                    <span class="ml-1.5 text-[#6B7280]" style="font-size: 1rem; font-weight: 400;">{{ viewYear }}</span>
                </div>

                <button
                    type="button"
                    class="flex h-10 w-10 items-center justify-center rounded-xl text-gray-400 transition-all hover:bg-[#2563EB]/5 hover:text-[#2563EB] active:scale-95 sm:h-9 sm:w-9"
                    @click="goToNextMonth"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M9 18l6-6-6-6" />
                    </svg>
                </button>
            </div>

            <div class="mb-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                <div
                    v-for="day in DAYS_ID"
                    :key="day"
                    class="flex h-9 items-center justify-center text-[#9CA3AF]"
                    style="font-size: 0.75rem; font-weight: 500;"
                >
                    {{ day }}
                </div>
            </div>

            <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
                <button
                    v-for="(entry, index) in allDays"
                    :key="`day-${index}`"
                    type="button"
                    class="relative flex h-11 w-full items-center justify-center rounded-xl text-sm transition-all duration-150 sm:h-10"
                    :class="isDisabled(entry.date)
                        ? 'pointer-events-none cursor-not-allowed select-none bg-slate-5 text-slate-400'
                        : normalizedValue && isSameDay(entry.date, normalizedValue)
                            ? 'bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/25'
                            : isSameDay(entry.date, today) && entry.current
                                ? 'bg-[#2563EB]/8 text-[#2563EB]'
                                : entry.current
                                    ? 'text-[#374151] hover:bg-[#F3F4F6]'
                                    : 'text-[#D1D5DB] hover:bg-[#F9FAFB]'"
                    :style="{ fontWeight: (normalizedValue && isSameDay(entry.date, normalizedValue)) || (isSameDay(entry.date, today) && entry.current) ? 600 : 400 }"
                    :disabled="isDisabled(entry.date)"
                    @click="handleSelect(entry)"
                >
                    {{ entry.day }}
                    <span
                        v-if="isSameDay(entry.date, today) && !(normalizedValue && isSameDay(entry.date, normalizedValue)) && entry.current"
                        class="absolute bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-[#2563EB]"
                    />
                </button>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-2 border-d border-slate-200 pt-3">
                <button
                    type="button"
                    class="rounded-lg px-3 py-2 text-xs transition-colors sm:py-1.5"
                    :class="canSelectToday
                        ? 'bg-[#2563EB]/5 text-[#2563EB] hover:bg-[#2563EB]/10'
                        : 'pointer-events-none cursor-not-allowed select-none bg-gray-100 text-gray-300 opacity-80'"
                    style="font-weight: 500;"
                    :disabled="!canSelectToday"
                    @click="selectToday"
                >
                    Hari ini
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 transition-colors hover:bg-gray-100 sm:py-1.5"
                    style="font-weight: 500;"
                    @click="selectTomorrow"
                >
                    Besok
                </button>
                <button
                    type="button"
                    class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 transition-colors hover:bg-gray-100 sm:py-1.5"
                    style="font-weight: 500;"
                    @click="selectNextWeek"
                >
                    Minggu depan
                </button>
            </div>
        </div>

        <template v-if="open && isMobile">
            <div class="fixed inset-0 z-[100] bg-black/40" @click="closePicker" />

            <div class="fixed inset-x-0 bottom-0 z-[101] max-h-[85vh] overflow-y-auto rounded-t-3xl bg-white px-5 pb-8 pt-3 shadow-2xl">
                <div class="mb-3 flex justify-center">
                    <div class="h-1.5 w-10 rounded-full bg-gray-200" />
                </div>

                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-[#1F2937]" style="font-size: 1.125rem; font-weight: 600;">Pilih Tanggal</h3>
                    <button
                        type="button"
                        class="flex h-9 w-9 items-center justify-center rounded-full bg-gray-50 text-gray-400 transition-colors hover:bg-gray-100"
                        @click="closePicker"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M18 6L6 18" />
                            <path d="M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="mb-4 flex items-center justify-between">
                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-gray-400 transition-all hover:bg-[#2563EB]/5 hover:text-[#2563EB] active:scale-95 sm:h-9 sm:w-9"
                        @click="goToPrevMonth"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 18l-6-6 6-6" />
                        </svg>
                    </button>

                    <div class="text-center">
                        <span class="text-[#1F2937]" style="font-size: 1rem; font-weight: 600;">{{ MONTHS_ID[viewMonth] }}</span>
                        <span class="ml-1.5 text-[#6B7280]" style="font-size: 1rem; font-weight: 400;">{{ viewYear }}</span>
                    </div>

                    <button
                        type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-xl text-gray-400 transition-all hover:bg-[#2563EB]/5 hover:text-[#2563EB] active:scale-95 sm:h-9 sm:w-9"
                        @click="goToNextMonth"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M9 18l6-6-6-6" />
                        </svg>
                    </button>
                </div>

                <div class="mb-1 grid grid-cols-7 gap-0.5 sm:gap-1">
                    <div
                        v-for="day in DAYS_ID"
                        :key="`mobile-${day}`"
                        class="flex h-9 items-center justify-center text-[#9CA3AF]"
                        style="font-size: 0.75rem; font-weight: 500;"
                    >
                        {{ day }}
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-0.5 sm:gap-1">
                    <button
                        v-for="(entry, index) in allDays"
                        :key="`mobile-day-${index}`"
                        type="button"
                        class="relative flex h-11 w-full items-center justify-center rounded-xl text-sm transition-all duration-150 sm:h-10"
                        :class="isDisabled(entry.date)
                            ? 'pointer-events-none cursor-not-allowed select-none bg-slate-100 text-slate-400'
                            : normalizedValue && isSameDay(entry.date, normalizedValue)
                                ? 'bg-[#2563EB] text-white shadow-md shadow-[#2563EB]/25'
                                : isSameDay(entry.date, today) && entry.current
                                    ? 'bg-[#2563EB]/8 text-[#2563EB]'
                                    : entry.current
                                        ? 'text-[#374151] hover:bg-[#F3F4F6]'
                                        : 'text-[#D1D5DB] hover:bg-[#F9FAFB]'"
                        :style="{ fontWeight: (normalizedValue && isSameDay(entry.date, normalizedValue)) || (isSameDay(entry.date, today) && entry.current) ? 600 : 400 }"
                        :disabled="isDisabled(entry.date)"
                        @click="handleSelect(entry)"
                    >
                        {{ entry.day }}
                        <span
                            v-if="isSameDay(entry.date, today) && !(normalizedValue && isSameDay(entry.date, normalizedValue)) && entry.current"
                            class="absolute bottom-1.5 left-1/2 h-1 w-1 -translate-x-1/2 rounded-full bg-[#2563EB]"
                        />
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 border-t border-gray-50 pt-3">
                    <button
                        type="button"
                        class="rounded-lg px-3 py-2 text-xs transition-colors sm:py-1.5"
                        :class="canSelectToday
                            ? 'bg-[#2563EB]/5 text-[#2563EB] hover:bg-[#2563EB]/10'
                            : 'pointer-events-none cursor-not-allowed select-none bg-gray-100 text-gray-300 opacity-80'"
                        style="font-weight: 500;"
                        :disabled="!canSelectToday"
                        @click="selectToday"
                    >
                        Hari ini
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 transition-colors hover:bg-gray-100 sm:py-1.5"
                        style="font-weight: 500;"
                        @click="selectTomorrow"
                    >
                        Besok
                    </button>
                    <button
                        type="button"
                        class="rounded-lg bg-gray-50 px-3 py-2 text-xs text-gray-500 transition-colors hover:bg-gray-100 sm:py-1.5"
                        style="font-weight: 500;"
                        @click="selectNextWeek"
                    >
                        Minggu depan
                    </button>
                </div>

                <div class="h-[env(safe-area-inset-bottom,0px)]" />
            </div>
        </template>
    </div>
</template>
