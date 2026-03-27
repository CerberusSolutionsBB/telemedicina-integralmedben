<script setup>
const props = defineProps({
    field: Object,
    modelValue: [String, Number],
    disabled: Boolean,
    class: String
});

const emit = defineEmits(['update:modelValue']);

const inputTypes = {
    text: 'text',
    email: 'email',
    number: 'number',
    tel: 'tel'
};
</script>

<template>
    <input :type="inputTypes[field.type] || 'text'" :value="modelValue" :placeholder="field.placeholder"
        :disabled="disabled" :required="field.required" :class="class"
        @input="$emit('update:modelValue', $event.target.value)" />
</template>
