<!-- components/PomponeteLink.vue -->
<script setup>
defineProps({
    url: {
        type: String,
        required: true
    },
    label: {
        type: String,
        default: null
    },
    color: {
        type: String,
        default: 'cyan',
        validator: (v) => ['cyan', 'emerald', 'rose', 'amber', 'indigo', 'violet'].includes(v)
    }
});

const openInNewTab = (url) => {
    window.open(url, '_blank', 'noopener,noreferrer');
};

const colors = {
    cyan: { bg: 'bg-cyan-50', text: 'text-cyan-700', hover: 'hover:bg-cyan-100', ring: 'focus:ring-cyan-300' },
    emerald: { bg: 'bg-emerald-50', text: 'text-emerald-700', hover: 'hover:bg-emerald-100', ring: 'focus:ring-emerald-300' },
    rose: { bg: 'bg-rose-50', text: 'text-rose-700', hover: 'hover:bg-rose-100', ring: 'focus:ring-rose-300' },
    amber: { bg: 'bg-amber-50', text: 'text-amber-700', hover: 'hover:bg-amber-100', ring: 'focus:ring-amber-300' },
    indigo: { bg: 'bg-indigo-50', text: 'text-indigo-700', hover: 'hover:bg-indigo-100', ring: 'focus:ring-indigo-300' },
    violet: { bg: 'bg-violet-50', text: 'text-violet-700', hover: 'hover:bg-violet-100', ring: 'focus:ring-violet-300' },
};
</script>

<template>
    <button @click="openInNewTab(url)" :title="`Abrir ${url} em nova aba`" :class="[
        colors[color].bg,
        colors[color].text,
        colors[color].hover,
        colors[color].ring,
        'group inline-flex items-center gap-1.5',
        'rounded-md',
        'text-xs font-medium',
        'px-2.5 py-1',
        'transition-colors duration-150',
        'active:scale-95',
        'focus:outline-none focus:ring-2 focus:ring-offset-1',
        'cursor-pointer'
    ]">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
            class="opacity-70 group-hover:opacity-100 transition-opacity duration-150">
            <circle cx="12" cy="12" r="10" />
            <path d="M2 12h20" />
            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z" />
        </svg>

        <span class="font-mono truncate ">
            {{ label || url }}
        </span>

        <svg xmlns="http://www.w3.org/2000/svg" width="10" height="10" viewBox="0 0 24 24" fill="none"
            stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"
            class="opacity-0 group-hover:opacity-60 transition-opacity duration-150 -ml-0.5">
            <path d="M7 17L17 7" />
            <path d="M7 7h10v10" />
        </svg>
    </button>
</template>
