<script setup>
defineProps({
    field: Object,
    modelValue: [String, Number],
    disabled: Boolean,
    class: String
});

defineEmits(['update:modelValue']);
</script>

<template>
    <select :value="modelValue" :disabled="disabled" :required="field.required" :class="class"
        @change="$emit('update:modelValue', $event.target.value)">
        <option value="" disabled selected>
            {{ field.placeholder || 'Selecione uma opção' }}
        </option>
        <option v-for="option in field.options" :key="option" :value="option">
            {{ option }}
        </option>
    </select>
</template>
