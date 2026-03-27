<script setup>
import { Link } from "@inertiajs/vue3";
import { ChevronRight, Home } from "lucide-vue-next";

defineProps({
    items: {
        type: Array,
        required: true,
        // [{ label: 'Início', href: '/dashboard', icon: Home }, ...]
    }
});
</script>

<template>
    <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4 overflow-x-auto">
        <template v-for="(item, index) in items" :key="index">
            <ChevronRight v-if="index > 0" class="w-4 h-4 flex-shrink-0 text-gray-400" />

            <Link v-if="item.href" :href="item.href"
                class="flex items-center gap-1 hover:text-cyan-600 transition-colors whitespace-nowrap">
                <component :is="item.icon" v-if="item.icon" class="w-4 h-4" />
                <span>{{ item.label }}</span>
            </Link>

            <span v-else class="flex items-center gap-1 text-gray-900 font-medium whitespace-nowrap">
                <component :is="item.icon" v-if="item.icon" class="w-4 h-4" />
                {{ item.label }}
            </span>
        </template>
    </nav>
</template>
