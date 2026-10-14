<script setup>
import { useForm } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { Loader2, Ticket } from 'lucide-vue-next'

const props = defineProps({
    tenantId: { type: [String, Number], required: true },
    // % máximo de desconto dos cupons do parceiro; null = parceiro sem cupons.
    teto: { type: Number, default: null },
})

const form = useForm({ teto: props.teto ?? '' })

const salvar = () => {
    form.transform((dados) => ({ teto: dados.teto === '' ? null : dados.teto }))
        .put(route('pagina.configuracao.cupom-teto', props.tenantId), {
            preserveScroll: true,
            onSuccess: () => showToast('Teto de desconto dos cupons atualizado!', 'success'),
            onError: () => showToast('Erro ao salvar o teto de desconto.', 'error'),
        })
}
</script>

<template>
    <div class="space-y-3">
        <div>
            <h2 class="text-base font-semibold flex items-center gap-2 text-gray-800">
                <Ticket class="w-5 h-5 text-cyan-500" />
                Cupons do parceiro
            </h2>
            <p class="text-sm text-gray-500 mt-0.5">
                Desconto máximo (%) dos cupons que o próprio parceiro cria. Deixe vazio para não permitir cupons no painel dele.
            </p>
        </div>

        <form class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6" @submit.prevent="salvar">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-end">
                <div class="w-full sm:max-w-xs">
                    <label for="cupom-teto" class="mb-1 block text-sm font-medium text-gray-700">Desconto máximo (%)</label>
                    <input id="cupom-teto" v-model="form.teto" type="number" min="0" max="100" step="0.01" placeholder="Sem cupons"
                        class="w-full min-h-11 rounded-lg border border-gray-300 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                    <p v-if="form.errors.teto" class="mt-1 text-sm text-red-600">{{ form.errors.teto }}</p>
                </div>
                <button type="submit" :disabled="form.processing"
                    class="inline-flex h-11 items-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-700 disabled:opacity-50">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Salvar teto
                </button>
            </div>
        </form>
    </div>
</template>
