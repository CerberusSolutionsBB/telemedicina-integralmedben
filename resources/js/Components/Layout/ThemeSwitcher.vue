<script setup>
import { Moon, Sun } from 'lucide-vue-next';
import { useTema } from '@/Composables/Layout/useTema';

const { escuro, alternar } = useTema();
</script>

<template>
    <button type="button" @click="alternar" :aria-pressed="escuro"
        :aria-label="escuro ? 'Usar tema claro' : 'Usar tema escuro'" :title="escuro ? 'Tema claro' : 'Tema escuro'"
        class="p-2.5 rounded-lg text-gray-500 hover:text-gray-700 hover:bg-gray-100 dark:text-gray-400 dark:hover:text-gray-200 dark:hover:bg-gray-800 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
        <Sun v-if="escuro" class="w-5 h-5" />
        <Moon v-else class="w-5 h-5" />
    </button>
</template>
