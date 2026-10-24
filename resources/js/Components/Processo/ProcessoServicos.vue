<script setup>
import { computed } from "vue";
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import SeletorMultiploModal from "@/Components/SeletorMultiploModal.vue";
import TituloSecao from "@/Components/CadastroIcone/TituloSecao.vue";
import { AlertCircle, X } from "lucide-vue-next";

/**
 * Serviços vinculados ao processo: escolhidos num modal (um ou vários, opcional)
 * e listados abaixo, com opção de remover um a um.
 */
const props = defineProps({
    form: { type: Object, required: true },
    // Número da seção (o formulário genérico informa).
    numero: { type: Number, required: true },
    // [{ id, titulo, icone, status }]
    servicos: { type: Array, default: () => [] },
});

const form = props.form;

// Inativos aparecem identificados.
const itens = computed(() => props.servicos.map((s) => ({ value: s.id, label: s.titulo, icone: s.icone, hint: s.status ? null : "Inativo" })));
const escolhidos = computed(() => props.servicos.filter((s) => form.servicos.some((id) => String(id) === String(s.id))));

const remover = (id) => (form.servicos = form.servicos.filter((v) => String(v) !== String(id)));

// Erros em "servicos" ou "servicos.N" (serviço que não existe mais).
const erroServicos = computed(() => Object.entries(form.errors).find(([campo]) => campo === "servicos" || campo.startsWith("servicos."))?.[1]);

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const etiqueta = "inline-flex min-h-9 items-center gap-1.5 rounded-full border px-3 text-sm";
</script>

<template>
    <section :class="card">
        <TituloSecao :numero="numero" titulo="Serviços" ajuda="Opcional. Escolha um ou mais serviços que fazem parte do processo." />

        <div class="mt-5">
            <label for="servicos" class="mb-1 block text-sm font-medium text-gray-800">Serviços do processo</label>
            <SeletorMultiploModal id="servicos" v-model="form.servicos" :items="itens" placeholder="Escolher serviços"
                titulo="Selecionar serviços" descricao="Marque os serviços que fazem parte do processo e confirme."
                busca-placeholder="Buscar serviço..." vazio="Nenhum serviço encontrado." rotulo-todos="Todos os serviços"
                permite-vazio :error="!!erroServicos" />
            <p class="mt-1 text-sm text-gray-600">{{ servicos.length ? 'Ex.: teleconsulta, exames.' : 'Nenhum serviço cadastrado ainda.' }}</p>
            <p v-if="erroServicos" class="mt-1 flex items-start gap-1 text-sm text-red-600 dark:text-red-400">
                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ erroServicos }}
            </p>
        </div>

        <ul v-if="escolhidos.length" class="mt-4 flex flex-wrap gap-2" aria-label="Serviços escolhidos">
            <li v-for="servico in escolhidos" :key="servico.id"
                :class="[etiqueta, servico.status ? 'border-cyan-200 bg-cyan-50 text-cyan-800' : 'border-gray-300 bg-gray-100 text-gray-700']">
                <IconeLucide :nome="servico.icone" class="h-4 w-4" />
                {{ servico.titulo }}<span v-if="!servico.status" class="text-xs">(inativo)</span>
                <button type="button" :aria-label="`Remover ${servico.titulo}`" title="Remover" @click="remover(servico.id)"
                    class="-mr-1 rounded-full p-1 hover:bg-white/70 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                    <X class="h-3.5 w-3.5" aria-hidden="true" />
                </button>
            </li>
        </ul>
    </section>
</template>
