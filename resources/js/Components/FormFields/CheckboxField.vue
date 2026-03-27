<script setup>
import { computed } from 'vue';

const props = defineProps({
    field: Object,
    modelValue: {
        type: Array,
        default: () => []
    },
    disabled: Boolean
});

const emit = defineEmits(['update:modelValue']);

const selectedValues = computed({
    get: () => props.modelValue || [],
    set: (val) => emit('update:modelValue', val)
});

const toggleOption = (option) => {
    const current = [...selectedValues.value];
    const index = current.indexOf(option);

    if (index === -1) {
        current.push(option);
    } else {
        current.splice(index, 1);
    }

    selectedValues.value = current;
};
</script>

<template>
    <div class="space-y-2">
        <label v-for="option in field.options" :key="option"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }">
            <input type="checkbox" :value="option" :checked="selectedValues.includes(option)" :disabled="disabled"
                class="w-5 h-5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500"
                @change="toggleOption(option)" />
            <span class="text-gray-700">{{ option }}</span>
        </label>
    </div>
</template>
