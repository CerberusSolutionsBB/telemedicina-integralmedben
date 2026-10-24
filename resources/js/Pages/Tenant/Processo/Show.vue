<script setup>
import CadastroIconeDetalhe from "@/Components/CadastroIcone/CadastroIconeDetalhe.vue";
import ProcessoResumo from "@/Components/Processo/ProcessoResumo.vue";
import ProcessoUsosCupom from "@/Components/Processo/ProcessoUsosCupom.vue";
import { PROCESSOS } from "@/Composables/CadastroIcone/modulos";
import { Ticket } from "lucide-vue-next";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    historico: { type: Array, default: () => [] },
    usosCupom: { type: Array, default: () => [] },
});

// Abas: Visão geral | Usos de cupom | Histórico.
const abas = [{ key: "cupons", label: "Usos de cupom", icon: Ticket, contador: props.usosCupom.length }];
</script>

<template>
    <CadastroIconeDetalhe :modulo="PROCESSOS" :breadcrumbs="breadcrumbs" :item="item" :historico="historico" :abas="abas">
        <template #detalhes>
            <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Valor e serviços</h2>
                <ProcessoResumo :item="item" class="mt-3" />
            </section>
        </template>
        <template #aba-cupons>
            <ProcessoUsosCupom :usos="usosCupom" />
        </template>
    </CadastroIconeDetalhe>
</template>
