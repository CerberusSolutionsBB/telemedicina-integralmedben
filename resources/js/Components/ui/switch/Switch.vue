<script setup>
import { Switch } from '@headlessui/vue';
// ... resto
</script>

<template>
    <Switch v-if="formData" v-model="formData.is_public" :class="formData.is_public ? 'bg-cyan-600' : 'bg-gray-200'"
        class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-opacity-75">
        <span class="sr-only">Use setting</span>
        <span aria-hidden="true" :class="formData.is_public ? 'translate-x-5' : 'translate-x-0'"
            class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out" />
    </Switch>
</template>
