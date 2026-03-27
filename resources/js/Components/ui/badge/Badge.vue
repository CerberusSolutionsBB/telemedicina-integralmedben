<script setup>
defineProps({
    variant: {
        type: String,
        default: 'default', // default, secondary, outline, destructive
    },
});

const variants = {
    default: 'bg-cyan-100 text-cyan-800 hover:bg-cyan-200',
    secondary: 'bg-gray-100 text-gray-800 hover:bg-gray-200',
    outline: 'border border-gray-300 text-gray-600 hover:bg-gray-50',
    destructive: 'bg-red-100 text-red-800 hover:bg-red-200',
};
</script>

<template>
    <span :class="[
        'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium transition-colors',
        variants[variant]
    ]">
        <slot />
    </span>
</template>
