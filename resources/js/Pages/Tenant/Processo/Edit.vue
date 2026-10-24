<script setup>
import CadastroIconeEdicao from "@/Components/CadastroIcone/CadastroIconeEdicao.vue";
import ProcessoCampos from "@/Components/Processo/ProcessoCampos.vue";
import ProcessoServicos from "@/Components/Processo/ProcessoServicos.vue";
import { PROCESSOS } from "@/Composables/CadastroIcone/modulos";

defineProps({
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    icones: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
    servicos: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeEdicao :modulo="PROCESSOS" :breadcrumbs="breadcrumbs" :item="item" :icones="icones" :limites="limites">
        <template #campos="{ form, proximo }">
            <ProcessoCampos :form="form" :numero="proximo" />
        </template>
        <!-- No lugar do status: ativar/inativar fica no detalhe do processo -->
        <template #status="{ form, numero }">
            <ProcessoServicos :form="form" :numero="numero" :servicos="servicos" />
        </template>
    </CadastroIconeEdicao>
</template>
