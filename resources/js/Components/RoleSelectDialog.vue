<script setup>
import { ref, computed } from 'vue'
import { Shield, ChevronDown, Search, BadgeCheck } from 'lucide-vue-next'
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from '@/Components/ui/dialog'
import { Button } from '@/Components/ui/button'

const props = defineProps({
    modelValue: {
        type: String,
        default: '',
    },
    roles: {
        type: Array,
        default: () => [],
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: 'Clique para selecionar...',
    },
})

const emit = defineEmits(['update:modelValue'])

const dialogOpen = ref(false)
const search = ref('')
const pending = ref(null)

const filteredRoles = computed(() => {
    if (!search.value) return props.roles
    const q = search.value.toLowerCase()
    return props.roles.filter((r) => r.name.toLowerCase().includes(q))
})

const selectedName = computed(() => {
    if (!props.modelValue) return ''
    const found = props.roles.find((r) => r.name === props.modelValue)
    return found ? found.name : ''
})

const open = () => {
    pending.value = props.modelValue
    search.value = ''
    dialogOpen.value = true
}

const confirm = () => {
    emit('update:modelValue', pending.value)
    dialogOpen.value = false
}

const cancel = () => {
    pending.value = null
    dialogOpen.value = false
}
</script>

<template>
    <div>
        <div class="relative cursor-pointer" @click="open">
            <Shield class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
            <input readonly :value="selectedName" type="text" :placeholder="placeholder"
                class="block w-full border border-gray-300 rounded-lg py-2.5 pl-10 pr-10 text-sm bg-white placeholder-gray-500 cursor-pointer focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-shadow"
                :disabled="disabled" />
            <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400 pointer-events-none" />
        </div>

        <Dialog v-model:open="dialogOpen">
            <DialogContent class="sm:max-w-[420px]">
                <DialogHeader>
                    <DialogTitle>Selecionar Perfil</DialogTitle>
                    <DialogDescription>
                        Busque e escolha um perfil de acesso.
                    </DialogDescription>
                </DialogHeader>

                <div class="relative mt-2">
                    <Search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input v-model="search" type="text" placeholder="Buscar perfil..."
                        class="w-full border border-gray-300 rounded-lg py-2 pl-10 pr-4 text-sm bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-shadow"
                        autofocus />
                </div>

                <div v-if="filteredRoles.length" class="space-y-1 max-h-60 overflow-y-auto pr-1 mt-1">
                    <button v-for="role in filteredRoles" :key="role.id" type="button"
                        @click="pending = role.name"
                        class="w-full flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition text-left"
                        :class="pending === role.name
                            ? 'border-cyan-500/30 bg-cyan-50'
                            : 'border-gray-200 bg-gray-50 hover:bg-gray-100'
                            ">
                        <BadgeCheck class="h-4 w-4 shrink-0"
                            :class="pending === role.name ? 'text-cyan-600' : 'text-gray-300'" />
                        <span class="text-sm font-medium text-gray-700">{{ role.name }}</span>
                    </button>
                </div>

                <div v-else class="text-sm text-gray-500 text-center py-8">
                    Nenhum perfil encontrado.
                </div>

                <DialogFooter class="mt-4 gap-2 sm:justify-center">
                    <Button type="button" variant="outline" @click="cancel" class="flex-1">
                        Cancelar
                    </Button>
                    <Button type="button" @click="confirm" :disabled="!pending" class="flex-1">
                        Confirmar
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
