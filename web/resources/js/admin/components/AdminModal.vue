<script setup>
defineProps({
    show: { type: Boolean, required: true },
    zClass: { type: String, default: 'z-40' },
    panelClass: { type: String, default: 'max-w-2xl' },
    panelPaddingClass: { type: String, default: 'p-5' },
    panelRadiusClass: { type: String, default: 'rounded-2xl' },
});
</script>

<template>
    <div
        v-if="show"
        class="fixed inset-0 flex items-start justify-center overflow-y-auto p-4 sm:py-6"
        :class="zClass"
        style="background: rgba(15,23,42,0.45);"
    >
        <div
            class="w-full border bg-white"
            :class="[panelRadiusClass, panelPaddingClass, panelClass]"
            style="max-height: calc(100dvh - 2rem); overflow-y: auto; border-color: #E2E8F0; box-shadow: 0 18px 40px rgba(15,23,42,0.2);"
        >
            <slot />
        </div>
    </div>
</template>
