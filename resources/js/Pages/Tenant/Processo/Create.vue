<script setup>
import CadastroIconeEdicao from "@/Components/CadastroIcone/CadastroIconeEdicao.vue";
import ProcessoCampos from "@/Components/Processo/ProcessoCampos.vue";
import ProcessoServicos from "@/Components/Processo/ProcessoServicos.vue";
import { PROCESSOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    breadcrumbs: { type: Array, required: true },
    icones: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
    servicos: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeEdicao :modulo="PROCESSOS" :breadcrumbs="breadcrumbs" :icones="icones" :limites="limites">
        <template #campos="{ form, proximo }">
            <ProcessoCampos :form="form" :numero="proximo" />
        </template>
        <!-- No lugar do status: o processo nasce ativo e é inativado pelo detalhe -->
        <template #status="{ form, numero }">
            <ProcessoServicos :form="form" :numero="numero" :servicos="servicos" />
        </template>
    </CadastroIconeEdicao>
</template>
