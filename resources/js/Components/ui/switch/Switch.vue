<script setup>
import { Switch as HeadlessSwitch } from '@headlessui/vue';

const props = defineProps({
    modelValue: { type: Boolean, default: false },
    label: { type: String, default: '' },
    srText: { type: String, default: 'Alternar' },
});

const emit = defineEmits(['update:modelValue']);

const toggle = () => emit('update:modelValue', !props.modelValue);
</script>

<template>
    <HeadlessSwitch :model-value="modelValue" @update:model-value="toggle"
        :class="modelValue ? 'bg-cyan-600' : 'bg-gray-200'"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
        <span class="sr-only">{{ srText }}</span>
        <span aria-hidden="true" :class="modelValue ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
    </HeadlessSwitch>
</template>
