<!-- Components/PaginationSimple.vue -->
<script setup>
import { router } from '@inertiajs/vue3'
import { computed } from 'vue'

const props = defineProps({
    data: {
        type: Object,
        required: true,
    },
    links: {
        type: Array,
        required: true,
    },
    hasData: {
        type: Boolean,
        default: true,
    },
    label: {
        type: String,
        default: 'registros',
    },
})

const normalizedLinks = computed(() => {
    return props.links.map((link, index) => {
        let label = link.label

        if (index === 0) {
            label = '‹'
        } else if (index === props.links.length - 1) {
            label = '›'
        } else {
            const num = parseInt(label, 10)
            if (!isNaN(num)) {
                label = String(num)
            } else {
                label = label.replace(/&laquo;|&raquo;/g, '')
                    .replace(/pagination\.(previous|next)/g, '')
                    .trim() || '…'
            }
        }

        return { ...link, label }
    })
})
</script>

<template>
    <div v-if="hasData && links.length > 3"
        class="flex flex-col sm:flex-row items-center justify-between px-6 py-4 border-t border-gray-200 bg-gray-50 gap-4">
        <!-- Info -->
        <div class="text-sm text-gray-500">
            Mostrando
            <span class="font-medium">{{ data.from || 0 }}</span>
            a
            <span class="font-medium">{{ data.to || 0 }}</span>
            de
            <span class="font-medium">{{ data.total || 0 }}</span>
            {{ label }}
        </div>

        <!-- Links -->
        <div class="flex gap-1 flex-wrap">
            <template v-for="(link, index) in normalizedLinks" :key="index">

                <button v-if="link.url" @click="router.visit(link.url, { preserveScroll: true, preserveState: true })"
                    :disabled="link.active" :class="[
                        'px-3 py-1.5 rounded-lg text-sm font-medium transition-all',
                        link.active
                            ? 'bg-cyan-600 text-white shadow-sm cursor-default'
                            : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200 cursor-pointer'
                    ]">{{ link.label }}</button>

                <span v-else
                    class="px-3 py-1.5 rounded-lg text-sm text-gray-400 bg-gray-100 border border-gray-200 cursor-default">{{ link.label }}</span>
            </template>
        </div>
    </div>
</template>
