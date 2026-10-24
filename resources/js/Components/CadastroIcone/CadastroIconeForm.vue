<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import ContadorCaracteres from "@/Components/ContadorCaracteres.vue";
import SeletorIcone from "@/Components/Icone/SeletorIcone.vue";
import TituloSecao from "@/Components/CadastroIcone/TituloSecao.vue";
import { AlertCircle, CheckCircle2, CircleOff, Loader2 } from "lucide-vue-next";

/**
 * Formulário dos cadastros com ícone (serviços, convênios, cupons de benefício).
 * Seções numeradas na ordem de preenchimento; os campos próprios do módulo entram
 * pelo slot `campos` (que recebe o número da próxima seção). `modulo` = modulos.js.
 */
const props = defineProps({
    modulo: { type: Object, required: true },
    form: { type: Object, required: true },
    // Sugeridos [{ value, label }]; o seletor busca entre todos os ícones do lucide.
    icones: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
    cancelarHref: { type: String, required: true },
    rotuloSalvar: { type: String, default: "Salvar" },
});

const emit = defineEmits(["submit"]);

const form = props.form;

// Numeração: 1 = dados; as seções do módulo vêm depois; ícone e status no fim.
const secoesExtras = props.modulo.secoesExtras ?? 0;
const numeroIcone = 2 + secoesExtras;

// Mensagens distintas (convênio e serviço compartilham o mesmo aviso de vínculo).
const totalErros = computed(() => new Set(Object.values(form.errors)).size);

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const label = "mb-1 block text-sm font-medium text-gray-800";
const ajudaCampo = "mt-1 text-sm text-gray-600";
const erro = "mt-1 flex items-start gap-1 text-sm text-red-600 dark:text-red-400";
const input = (temErro) => [
    "w-full min-h-11 rounded-lg border bg-white px-3 text-base text-gray-900 placeholder:text-gray-500 focus:outline-none focus-visible:ring-2 sm:text-sm",
    temErro ? "border-red-500 focus-visible:ring-red-500" : "border-gray-300 focus-visible:ring-cyan-500",
];
</script>

<template>
    <form class="w-full space-y-6" @submit.prevent="emit('submit')">
        <p class="text-sm text-gray-600">Campos com <span class="font-semibold text-red-600 dark:text-red-400">*</span> são obrigatórios.</p>

        <!-- Resumo dos erros do envio: o primeiro campo inválido também recebe o foco -->
        <div v-if="totalErros" role="alert"
            class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/40 dark:text-red-300">
            <AlertCircle class="mt-0.5 h-5 w-5 shrink-0" aria-hidden="true" />
            <p>
                <strong>{{ totalErros === 1 ? 'Falta corrigir 1 campo.' : `Faltam corrigir ${totalErros} campos.` }}</strong>
                Eles estão destacados em vermelho, com a explicação logo abaixo.
            </p>
        </div>

        <section :class="card">
            <TituloSecao :numero="1" :titulo="`Dados do ${modulo.singular}`" :ajuda="`Dê um nome fácil de reconhecer e explique o que o ${modulo.singular} oferece.`" />

            <div class="mt-5 grid grid-cols-1 gap-5">
                <div>
                    <label :class="label" for="titulo">Título <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></label>
                    <input id="titulo" v-model="form.titulo" type="text" :maxlength="limites.titulo" required aria-required="true"
                        :placeholder="modulo.exemploTitulo" :class="input(form.errors.titulo)" :aria-invalid="!!form.errors.titulo"
                        aria-describedby="titulo-ajuda titulo-erro" />
                    <div class="mt-1 flex items-start gap-2">
                        <p id="titulo-ajuda" class="text-sm text-gray-600">Aparece nas listas. Use poucas palavras.</p>
                        <ContadorCaracteres :valor="form.titulo" :limite="limites.titulo" />
                    </div>
                    <p v-if="form.errors.titulo" id="titulo-erro" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.titulo }}</p>
                </div>

                <div>
                    <label :class="label" for="descricao">Descrição</label>
                    <textarea id="descricao" v-model="form.descricao" rows="4" :maxlength="limites.descricao" :placeholder="modulo.exemploDescricao"
                        aria-describedby="descricao-ajuda descricao-erro" :aria-invalid="!!form.errors.descricao"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-base leading-relaxed placeholder:text-gray-500 focus:outline-none focus-visible:ring-2 sm:text-sm"
                        :class="form.errors.descricao ? 'border-red-500 focus-visible:ring-red-500' : 'border-gray-300 focus-visible:ring-cyan-500'" />
                    <div class="mt-1 flex items-start gap-2">
                        <p id="descricao-ajuda" class="text-sm text-gray-600">Opcional. Regras, horários ou como usar.</p>
                        <ContadorCaracteres :valor="form.descricao" :limite="limites.descricao" />
                    </div>
                    <p v-if="form.errors.descricao" id="descricao-erro" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.descricao }}</p>
                </div>
            </div>
        </section>

        <!-- Campos próprios do módulo (ex.: desconto e vínculos do cupom de benefício) -->
        <slot name="campos" :form="form" :proximo="2" />

        <section :class="card">
            <TituloSecao id="icone-titulo" :numero="numeroIcone" titulo="Ícone"
                :ajuda="`Escolha uma imagem que lembre o ${modulo.singular}. Ela aparece ao lado do título.`" />
            <SeletorIcone v-model="form.icone" :sugeridos="icones" :error="form.errors.icone" class="mt-5" />
        </section>

        <!-- Última seção: status (padrão) ou a do módulo (ex.: serviços do processo) -->
        <slot name="status" :form="form" :numero="numeroIcone + 1">
            <section :class="card">
                <div class="flex items-start justify-between gap-4">
                    <TituloSecao :numero="numeroIcone + 1" titulo="Status" :ajuda="`Só ${modulo.plural} ativos ficam disponíveis.`" />
                    <AppSwitch v-model="form.status" :sr-text="`${modulo.singular} ativo`" />
                </div>
                <p class="mt-3 flex items-center gap-2 text-sm font-medium"
                    :class="form.status ? 'text-green-700 dark:text-green-400' : 'text-gray-600'">
                    <component :is="form.status ? CheckCircle2 : CircleOff" class="h-4 w-4" aria-hidden="true" />
                    {{ form.status ? `Ativo: o ${modulo.singular} fica disponível.` : `Inativo: o ${modulo.singular} fica guardado, mas indisponível.` }}
                </p>
            </section>
        </slot>

        <!-- Resumo do que foi preenchido, antes de salvar (opcional por módulo) -->
        <slot name="resumo" :form="form" />

        <div class="flex flex-col-reverse gap-3 border-t border-gray-200 pt-5 sm:flex-row sm:justify-end">
            <Link :href="cancelarHref"
                class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2">
                Cancelar
            </Link>
            <button type="submit" :disabled="form.processing"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-5 text-sm font-semibold text-white hover:bg-cyan-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2 disabled:opacity-60 motion-reduce:transition-none">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                {{ form.processing ? 'Salvando…' : rotuloSalvar }}
            </button>
        </div>
    </form>
</template>
