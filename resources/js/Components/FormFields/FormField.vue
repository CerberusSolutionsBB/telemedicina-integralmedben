<script setup>
import { computed } from 'vue';
import TextField from './TextField.vue';
import TextareaField from './TextareaField.vue';
import SelectField from './SelectField.vue';
import CheckboxField from './CheckboxField.vue';
import RadioField from './RadioField.vue';
import DateField from './DateField.vue';

const props = defineProps({
    field: {
        type: Object,
        required: true
    },
    modelValue: {
        type: [String, Number, Boolean, Array, Object],
        default: ''
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const fieldComponents = {
    text: TextField,
    email: TextField,
    number: TextField,
    tel: TextField,
    textarea: TextareaField,
    select: SelectField,
    checkbox: CheckboxField,
    radio: RadioField,
    date: DateField,
};

const inputClass = "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:bg-gray-100 disabled:text-gray-400";

const updateValue = (value) => {
    emit('update:modelValue', value);
};
</script>

<template>
    <div class="space-y-2">
        <!-- Label -->
        <label class="block text-sm font-medium text-gray-900">
            {{ field.label }}
            <span v-if="field.required" class="text-red-500">*</span>
        </label>

        <!-- Help text -->
        <p v-if="field.help_text" class="text-xs text-gray-500">
            {{ field.help_text }}
        </p>

        <!-- Componente específico do tipo -->
        <component :is="fieldComponents[field.type]" :field="field" :model-value="modelValue" :disabled="disabled"
            :class="inputClass" @update:model-value="updateValue" />

        <!-- Mensagem de erro (se houver) -->
        <slot name="error" />
    </div>
</template>
