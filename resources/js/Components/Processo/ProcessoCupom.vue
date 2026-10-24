<script setup>
import { ref, watch } from "vue";
import { fmtMoeda } from "@/Composables/Cupom/situacao";
import { AlertCircle, CheckCircle2, ClipboardCheck, Loader2, Ticket } from "lucide-vue-next";

/**
 * Código do cupom de benefício no cartão do processo: "Aplicar" mostra a prévia do
 * desconto (rota processos.cupom, nada é gravado) e "Registrar uso" grava o uso com
 * o usuário logado (rota processos.cupom.usar), que aparece no detalhe do processo.
 */
const props = defineProps({
    processo: { type: Object, required: true },
});

const codigo = ref("");
const carregando = ref(false);
const resultado = ref(null);
const registrando = ref(false);
// Uso gravado: { usuario, data, ... } (o botão some para não registrar duas vezes).
const registrado = ref(null);
const erroRegistro = ref("");

const id = `cupom-processo-${props.processo.id}`;

// Mudou o código: a prévia e o registro anteriores deixam de valer.
watch(codigo, () => {
    resultado.value = null;
    registrado.value = null;
    erroRegistro.value = "";
});

const mensagemErro = (e, padrao) => (e.response?.status === 429
    ? "Muitas tentativas. Aguarde um minuto e tente de novo."
    : e.response?.data?.mensagem ?? e.response?.data?.message ?? padrao);

const registrar = async () => {
    registrando.value = true;
    erroRegistro.value = "";
    try {
        const { data } = await window.axios.post(route("processos.cupom.usar", props.processo.id), { cupom: codigo.value });
        registrado.value = data.uso;
    } catch (e) {
        erroRegistro.value = mensagemErro(e, "Não foi possível registrar o uso do cupom.");
    } finally {
        registrando.value = false;
    }
};

const aplicar = async () => {
    if (!codigo.value.trim()) return;

    carregando.value = true;
    try {
        const { data } = await window.axios.post(route("processos.cupom", props.processo.id), { cupom: codigo.value });
        resultado.value = data;
    } catch (e) {
        resultado.value = { valido: false, mensagem: mensagemErro(e, "Não foi possível validar o cupom.") };
    } finally {
        carregando.value = false;
    }
};
</script>

<template>
    <div class="mx-4 mb-4">
        <label :for="id" class="mb-1 block text-sm font-medium text-gray-800">Código do cupom</label>
        <div class="flex gap-2">
            <div class="relative min-w-0 flex-1">
                <Ticket class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input :id="id" v-model="codigo" type="text" maxlength="20" placeholder="Ex.: FARMACIA15" autocomplete="off" spellcheck="false"
                    :aria-describedby="`${id}-resultado`"
                    class="w-full min-h-11 rounded-lg border border-gray-300 bg-white pl-9 pr-3 font-mono text-base uppercase tracking-wider placeholder:font-sans placeholder:normal-case placeholder:tracking-normal placeholder:text-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 sm:text-sm"
                    @input="codigo = codigo.toUpperCase().replace(/\s+/g, '')" @keydown.enter.prevent="aplicar" />
            </div>
            <button type="button" :disabled="carregando || !codigo.trim()" @click="aplicar"
                class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-lg border border-cyan-600 px-4 text-sm font-semibold text-cyan-700 hover:bg-cyan-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 disabled:opacity-50">
                <Loader2 v-if="carregando" class="h-4 w-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                Aplicar
            </button>
        </div>

        <div :id="`${id}-resultado`" aria-live="polite">
            <div v-if="resultado?.valido" class="mt-2 rounded-lg border border-green-200 bg-green-50 p-3 text-sm text-green-800 dark:border-green-900 dark:bg-green-950/40 dark:text-green-300">
                <p class="flex items-start gap-1.5 font-semibold">
                    <CheckCircle2 class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    {{ resultado.cupom }}: {{ resultado.desconto_texto }} de desconto
                </p>
                <p class="mt-1 tabular-nums">
                    De <span class="line-through">{{ fmtMoeda(resultado.valor_original) }}</span>
                    por <strong class="text-base">{{ fmtMoeda(resultado.valor_final) }}</strong>
                    (economia de {{ fmtMoeda(resultado.desconto) }}).
                </p>
                <p class="mt-1">Vale para o serviço <strong>{{ resultado.servico }}</strong>, que faz parte deste processo.</p>
                <p v-if="resultado.plano" class="mt-1 text-xs">Vale só para beneficiários do plano {{ resultado.plano }}.</p>

                <p v-if="registrado" class="mt-2 flex items-start gap-1.5 border-t border-green-200 pt-2 font-medium dark:border-green-900">
                    <ClipboardCheck class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                    Uso registrado por {{ registrado.usuario ?? 'você' }} em {{ registrado.data }}.
                </p>
                <button v-else type="button" :disabled="registrando" @click="registrar"
                    class="mt-3 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 disabled:opacity-60">
                    <Loader2 v-if="registrando" class="h-4 w-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                    <ClipboardCheck v-else class="h-4 w-4" aria-hidden="true" />
                    {{ registrando ? 'Registrando…' : 'Registrar uso' }}
                </button>
                <p v-if="erroRegistro" class="mt-2 flex items-start gap-1 text-sm text-red-600 dark:text-red-400">
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ erroRegistro }}
                </p>
            </div>
            <p v-else-if="resultado" class="mt-2 flex items-start gap-1 text-sm text-red-600 dark:text-red-400">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ resultado.mensagem }}
            </p>
        </div>
    </div>
</template>
