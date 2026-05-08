<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { MoreVertical } from 'lucide-vue-next'

const props = defineProps({
    align: {
        type: String,
        default: 'right', // right | left
    },
})

const open = ref(false)
const dropdownRef = ref(null)

const toggle = () => {
    open.value = !open.value
}

const close = () => {
    open.value = false
}

const handleClickOutside = (event) => {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        close()
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside)
})
</script>

<template>
    <div ref="dropdownRef" class="relative inline-block text-left">
        <button
            type="button"
            class="btn btn-sm btn-ghost btn-circle"
            @click.stop="toggle"
        >
            <MoreVertical class="w-5 h-5" />
        </button>

        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
        >
            <div
                v-if="open"
                class="absolute z-50 mt-2 w-56 rounded-xl bg-white border border-gray-200 shadow-lg overflow-hidden"
                :class="align === 'right' ? 'right-0' : 'left-0'"
            >
                <div class="py-2">
                    <slot :close="close" />
                </div>
            </div>
        </Transition>
    </div>
</template>
