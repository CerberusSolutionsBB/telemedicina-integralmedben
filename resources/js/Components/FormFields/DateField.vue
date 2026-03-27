<script setup>
defineProps({
    field: Object,
    modelValue: String,
    disabled: Boolean,
    class: String
});

defineEmits(['update:modelValue']);
</script>

<template>
    <input type="date" :value="modelValue" :placeholder="field.placeholder" :disabled="disabled"
        :required="field.required" :class="class" @input="$emit('update:modelValue', $event.target.value)" />
</template>
