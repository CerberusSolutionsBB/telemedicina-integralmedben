<script setup>
defineProps({
    field: Object,
    modelValue: [String, Number],
    disabled: Boolean
});

defineEmits(['update:modelValue']);
</script>

<template>
    <div class="space-y-2">
        <label v-for="option in field.options" :key="option"
            class="flex items-center gap-3 p-2 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors"
            :class="{ 'opacity-50 cursor-not-allowed': disabled }">
            <input type="radio" :name="field.id" :value="option" :checked="modelValue === option" :disabled="disabled"
                :required="field.required" class="w-5 h-5 border-gray-300 text-cyan-600 focus:ring-cyan-500"
                @change="$emit('update:modelValue', option)" />
            <span class="text-gray-700">{{ option }}</span>
        </label>
    </div>
</template>
