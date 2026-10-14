<script setup>
import { ref, watch } from "vue";
import { fmtMoeda } from "@/Composables/Cupom/situacao";
import { AlertCircle, CheckCircle2, Loader2, Ticket } from "lucide-vue-next";

/**
 * Campo de cupom com prévia do desconto ("Aplicar"). A validação definitiva
 * acontece no servidor ao salvar; a prévia só informa o usuário.
 * `urlValidacao` recebe { cupom, cod_plano?, cpf } e responde { valido, desconto, valor_final | mensagem }.
 */
const props = defineProps({
    modelValue: { type: String, default: "" },
    urlValidacao: { type: String, required: true },
    codPlano: { type: [String, Number], default: null },
    cpf: { type: String, default: "" },
    error: { type: String, default: "" },
    // Sem plano escolhido não há sobre o que aplicar o desconto.
    disabled: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue"]);

const carregando = ref(false);
const resultado = ref(null);

// Mudou o código, o plano ou o CPF: a prévia anterior deixa de valer.
watch(() => [props.modelValue, props.codPlano, props.cpf], () => (resultado.value = null));

const aplicar = async () => {
    if (!props.modelValue?.trim()) return;

    carregando.value = true;
    try {
        const { data } = await window.axios.post(props.urlValidacao, {
            cupom: props.modelValue,
            cod_plano: props.codPlano,
            cpf: props.cpf,
        });
        resultado.value = data;
    } catch (e) {
        resultado.value = e.response?.status === 429
            ? { valido: false, mensagem: "Muitas tentativas. Aguarde um minuto e tente de novo." }
            : (e.response?.data?.mensagem ? e.response.data : { valido: false, mensagem: e.response?.data?.message ?? "Não foi possível validar o cupom." });
    } finally {
        carregando.value = false;
    }
};
</script>

<template>
    <div>
        <label for="cupom" class="mb-1 block text-sm font-medium text-gray-700">Cupom de desconto <span class="font-normal text-gray-500">(opcional)</span></label>
        <div class="flex gap-2">
            <div class="relative flex-1">
                <Ticket class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input id="cupom" :value="modelValue" type="text" maxlength="50" placeholder="Ex.: OUTUBRO10" :disabled="disabled"
                    aria-describedby="cupom-ajuda"
                    class="w-full min-h-11 rounded-lg border bg-white pl-9 pr-3 text-base uppercase focus:outline-none focus:ring-2 disabled:bg-gray-50 disabled:text-gray-400 sm:text-sm"
                    :class="error ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-cyan-500'"
                    @input="emit('update:modelValue', $event.target.value.toUpperCase())" @keydown.enter.prevent="aplicar" />
            </div>
            <button type="button" :disabled="disabled || carregando || !modelValue?.trim()"
                class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-cyan-600 px-4 text-sm font-semibold text-cyan-700 hover:bg-cyan-50 disabled:opacity-50"
                @click="aplicar">
                <Loader2 v-if="carregando" class="h-4 w-4 animate-spin" aria-hidden="true" />
                Aplicar
            </button>
        </div>

        <p v-if="disabled" id="cupom-ajuda" class="mt-1 text-xs text-gray-500">Escolha o plano para aplicar um cupom.</p>
        <p v-else-if="error" class="mt-1 flex items-start gap-1 text-sm text-red-600">
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ error }}
        </p>
        <p v-else-if="resultado?.valido" class="mt-1 flex items-start gap-1 text-sm text-green-700" aria-live="polite">
            <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            Desconto de {{ fmtMoeda(resultado.desconto) }}: de {{ fmtMoeda(resultado.valor_original) }} por {{ fmtMoeda(resultado.valor_final) }}.
        </p>
        <p v-else-if="resultado" class="mt-1 flex items-start gap-1 text-sm text-red-600" aria-live="polite">
            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ resultado.mensagem }}
        </p>
    </div>
</template>
