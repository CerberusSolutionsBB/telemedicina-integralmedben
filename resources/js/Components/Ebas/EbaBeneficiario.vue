<script setup>
import { ACOES_BENEFICIARIO, useBeneficiarioPermissoes } from '@/Composables/Pagina/useBeneficiarioPermissoes'
import AppSwitch from '@/Components/ui/switch/Switch.vue'
import { Loader2, UserCog } from 'lucide-vue-next'

const props = defineProps({
    tenantId: { type: [String, Number], required: true },
    // { create, edit, delete, status }
    permissoes: { type: Object, default: () => ({}) },
})

const { form, salvar } = useBeneficiarioPermissoes(props)
</script>

<template>
    <div class="space-y-5">
        <div>
            <h2 class="text-base font-semibold flex items-center gap-2 text-gray-800">
                <UserCog class="w-5 h-5 text-cyan-500" />
                Beneficiário
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Escolha quais ações do cadastro de beneficiários este parceiro pode usar.
            </p>
        </div>

        <form class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6" @submit.prevent="salvar">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label v-for="acao in ACOES_BENEFICIARIO" :key="acao.key"
                    class="flex cursor-pointer items-start justify-between gap-3 rounded-lg border p-4 transition-colors"
                    :class="form[acao.key] ? 'border-cyan-300 bg-cyan-50' : 'border-gray-200 bg-white'">
                    <span>
                        <span class="block text-sm font-semibold text-gray-900">{{ acao.label }}</span>
                        <span class="mt-0.5 block text-xs text-gray-500">{{ acao.descricao }}</span>
                        <span class="mt-2 inline-block text-xs font-medium"
                            :class="form[acao.key] ? 'text-cyan-700' : 'text-gray-400'">
                            {{ form[acao.key] ? 'Habilitado' : 'Desabilitado' }}
                        </span>
                    </span>
                    <AppSwitch v-model="form[acao.key]" :sr-text="acao.label" />
                </label>
            </div>

            <div class="mt-5 flex justify-end">
                <button type="submit" :disabled="form.processing || !form.isDirty"
                    class="inline-flex h-10 items-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-700 disabled:opacity-50">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Salvar permissões
                </button>
            </div>
        </form>
    </div>
</template>
