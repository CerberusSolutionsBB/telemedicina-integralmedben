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
    <textarea :value="modelValue" :placeholder="field.placeholder" :disabled="disabled" :required="field.required"
        :class="class" rows="4" @input="$emit('update:modelValue', $event.target.value)"></textarea>
</template>
