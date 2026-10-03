<script setup>
import { useRelatorioProducao } from "@/Composables/Desempenho/useRelatorioProducao";
import { FileSpreadsheet, FileText, RotateCcw, Users } from "lucide-vue-next";

/**
 * Relatório de produção por usuário: filtros + download em PDF/XLSX.
 */
const props = defineProps({
    // { usuarios, perfis, planos, origens: [{ value, label }], padrao: { de, ate }, maxDias }
    opcoes: { type: Object, required: true },
});

const { filtros, erroPeriodo, url, limpar } = useRelatorioProducao(props.opcoes);

const label = "mb-1 block text-sm font-medium text-gray-700";
const campo = "w-full h-10 rounded-lg border border-gray-300 bg-white px-3 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-cyan-500";
const botao = "inline-flex h-10 items-center gap-2 rounded-lg px-4 text-sm font-semibold";
</script>

<template>
    <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="mb-4">
            <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900">
                <Users class="h-5 w-5 text-cyan-600" aria-hidden="true" />
                Produção por usuário
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                Quantos beneficiários cada usuário registrou em planos no período: ranking, total por plano e por
                origem, evolução por dia ou semana e a lista de registros. Conta os mesmos registros das metas.
            </p>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12">
            <div class="md:col-span-3 xl:col-span-2">
                <label :class="label" for="producao_de">De <span class="text-red-600">*</span></label>
                <input id="producao_de" v-model="filtros.de" type="date" :class="campo" required />
            </div>
            <div class="md:col-span-3 xl:col-span-2">
                <label :class="label" for="producao_ate">Até <span class="text-red-600">*</span></label>
                <input id="producao_ate" v-model="filtros.ate" type="date" :min="filtros.de" :class="campo" required />
            </div>
            <div class="md:col-span-3 xl:col-span-2">
                <label :class="label" for="producao_usuario">Usuário</label>
                <select id="producao_usuario" v-model="filtros.usuario" :class="campo">
                    <option value="">Todos</option>
                    <option v-for="item in opcoes.usuarios" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
            <div class="md:col-span-3 xl:col-span-2">
                <label :class="label" for="producao_perfil">Perfil</label>
                <select id="producao_perfil" v-model="filtros.perfil" :class="campo">
                    <option value="">Todos</option>
                    <option v-for="item in opcoes.perfis" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
            <div class="md:col-span-3 xl:col-span-2">
                <label :class="label" for="producao_plano">Plano</label>
                <select id="producao_plano" v-model="filtros.plano" :class="campo">
                    <option value="">Todos</option>
                    <option v-for="item in opcoes.planos" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
            <div class="md:col-span-3 xl:col-span-2">
                <label :class="label" for="producao_origem">Origem</label>
                <select id="producao_origem" v-model="filtros.origem" :class="campo">
                    <option value="">Todas</option>
                    <option v-for="item in opcoes.origens" :key="item.value" :value="item.value">{{ item.label }}</option>
                </select>
            </div>
        </div>

        <p v-if="erroPeriodo" class="mt-3 text-sm text-red-600" role="alert">{{ erroPeriodo }}</p>

        <div class="mt-5 flex flex-wrap justify-end gap-2">
            <button type="button" :class="[botao, 'text-gray-700 hover:bg-gray-100']" @click="limpar">
                <RotateCcw class="h-4 w-4" />
                Limpar filtros
            </button>
            <a :href="erroPeriodo ? undefined : url('xlsx')" :aria-disabled="Boolean(erroPeriodo)"
                :class="[botao, 'border border-gray-300 text-gray-700 hover:bg-gray-50', erroPeriodo && 'pointer-events-none opacity-50']">
                <FileSpreadsheet class="h-4 w-4" />
                Baixar XLSX
            </a>
            <a :href="erroPeriodo ? undefined : url('pdf')" :aria-disabled="Boolean(erroPeriodo)"
                :class="[botao, 'bg-cyan-600 text-white shadow-sm hover:bg-cyan-700', erroPeriodo && 'pointer-events-none opacity-50']">
                <FileText class="h-4 w-4" />
                Baixar PDF
            </a>
        </div>
    </section>
</template>
