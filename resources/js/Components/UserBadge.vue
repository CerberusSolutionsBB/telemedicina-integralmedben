<!-- resources/js/Components/UserBadge.vue -->
<script setup>
import { computed } from 'vue'

const props = defineProps({
    user: {
        type: Object,
        required: true,
    },
    size: {
        type: String,
        default: 'md', // sm | md | lg
    },
    showEmail: {
        type: Boolean,
        default: true,
    },
    avatarUrl: {
        type: String,
        default: null,
    },
    clickable: {
        type: Boolean,
        default: false,
    },
})

const sizeClasses = {
    sm: {
        container: 'gap-3 px-3 py-2',
        avatar: 'w-10 h-10',
        initial: 'text-sm',
        name: 'text-sm',
        email: 'text-xs',
    },

    md: {
        container: 'gap-4 px-4 py-3',
        avatar: 'w-12 h-12',
        initial: 'text-base',
        name: 'text-sm',
        email: 'text-xs',
    },

    lg: {
        container: 'gap-4 px-5 py-4',
        avatar: 'w-14 h-14',
        initial: 'text-lg',
        name: 'text-base',
        email: 'text-sm',
    },
}

const classes = computed(() => sizeClasses[props.size] || sizeClasses.md)

const avatar = computed(() => {
    return props.avatarUrl || props.user?.avatar || null
})

const initial = computed(() => {
    return props.user?.name?.charAt(0)?.toUpperCase() || '?'
})
</script>

<template>
    <div
        class="flex items-center min-w-0 rounded-2xl border border-gray-200 bg-white shadow-sm transition-all duration-200"
        :class="[
            classes.container,
            clickable ? 'hover:shadow-md hover:border-gray-300 cursor-pointer' : ''
        ]"
    >
        <!-- Avatar -->
        <div class="shrink-0">
            <img
                v-if="avatar"
                :src="avatar"
                :alt="user.name"
                class="rounded-full object-cover ring-2 ring-white shadow-sm"
                :class="classes.avatar"
            />

            <div
                v-else
                class="flex items-center justify-center rounded-full bg-gradient-to-br from-gray-100 to-gray-200 text-gray-700 font-bold shadow-inner"
                :class="classes.avatar"
            >
                <span :class="classes.initial">
                    {{ initial }}
                </span>
            </div>
        </div>

        <!-- Info -->
        <div class="min-w-0 text-left">
            <p
                class="truncate font-semibold text-gray-900 leading-tight"
                :class="classes.name"
            >
                {{ user.name || 'Usuário sem nome' }}
            </p>

            <p
                v-if="showEmail && user.email"
                class="truncate text-gray-500 mt-1 leading-tight"
                :class="classes.email"
            >
                {{ user.email }}
            </p>
        </div>
    </div>
</template>
