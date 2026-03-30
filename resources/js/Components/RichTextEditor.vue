<script setup>
import { QuillEditor } from '@vueup/vue-quill';
import '@vueup/vue-quill/dist/vue-quill.snow.css';

const props = defineProps({
    modelValue: {
        type: String,
        default: ''
    },
    placeholder: {
        type: String,
        default: 'Digite aqui...'
    },
    disabled: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['update:modelValue']);

const options = {
    theme: 'snow',
    placeholder: props.placeholder,
    modules: {
        toolbar: [
            [{ 'header': [1, 2, 3, false] }],
            ['bold', 'italic', 'underline', 'strike'],
            ['blockquote', 'code-block'],
            [{ 'list': 'ordered' }, { 'list': 'bullet' }],
            [{ 'indent': '-1' }, { 'indent': '+1' }],
            [{ 'color': [] }, { 'background': [] }],
            ['clean']
        ]
    }
};
</script>

<template>
    <div :class="{ 'opacity-50': disabled }">
        <QuillEditor :content="modelValue" content-type="html" :options="options"
            @update:content="$emit('update:modelValue', $event)" :disabled="disabled" class="bg-white rounded-lg" />
    </div>
</template>

<style>
.ql-editor {
    min-height: 200px;
}

.ql-toolbar {
    border-top-left-radius: 0.5rem;
    border-top-right-radius: 0.5rem;
    background-color: #f9fafb;
}

.ql-container {
    border-bottom-left-radius: 0.5rem;
    border-bottom-right-radius: 0.5rem;
}
</style>
