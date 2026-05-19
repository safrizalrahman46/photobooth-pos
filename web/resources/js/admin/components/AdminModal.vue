<script setup>
defineProps({
    show: { type: Boolean, required: true },
    zClass: { type: String, default: 'z-40' },
    panelClass: { type: String, default: 'max-w-2xl' },
    panelPaddingClass: { type: String, default: 'p-4 sm:p-5' },
    panelRadiusClass: { type: String, default: 'rounded-xl sm:rounded-2xl' },
});
</script>

<template>
    <div
        v-if="show"
        class="rtp-admin-modal-overlay fixed inset-0 flex items-start justify-center"
        :class="zClass"
    >
        <div
            class="rtp-admin-modal-panel w-full border bg-white"
            :class="[panelRadiusClass, panelPaddingClass, panelClass]"
        >
            <slot />
        </div>
    </div>
</template>
