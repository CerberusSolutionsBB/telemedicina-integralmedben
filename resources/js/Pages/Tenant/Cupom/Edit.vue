<script setup>
import CadastroIconeEdicao from "@/Components/CadastroIcone/CadastroIconeEdicao.vue";
import CupomBeneficioCampos from "@/Components/CupomBeneficio/CupomBeneficioCampos.vue";
import FraseCupom from "@/Components/CupomBeneficio/FraseCupom.vue";
import { CUPONS_BENEFICIO } from "@/Composables/CadastroIcone/modulos";

const props = defineProps({
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    icones: { type: Array, default: () => [] },
    limites: { type: Object, required: true },
    planos: { type: Array, default: () => [] },
    convenios: { type: Array, default: () => [] },
    servicos: { type: Array, default: () => [] },
    tiposDesconto: { type: Array, default: () => [] },
});

// Nomes para a frase "Como o cupom fica" (o formulário guarda só ids e códigos).
const nome = (lista, id) => lista.find((i) => String(i.id) === String(id))?.titulo ?? null;
const nomePlano = (codigo) => props.planos.find((p) => p.value === codigo)?.label ?? null;
</script>

<template>
    <CadastroIconeEdicao :modulo="CUPONS_BENEFICIO" :breadcrumbs="breadcrumbs" :item="item" :icones="icones" :limites="limites">
        <template #campos="{ form, proximo }">
            <CupomBeneficioCampos :form="form" :numero="proximo" :planos="planos" :convenios="convenios" :servicos="servicos"
                :tipos-desconto="tiposDesconto" />
        </template>
        <template #resumo="{ form }">
            <FraseCupom :tipo="form.tipo_desconto" :valor="form.valor_desconto" :plano="nomePlano(form.cod_plano)"
                :convenio="nome(convenios, form.convenio_id)" :servico="nome(servicos, form.servico_id)" :prazo="form.prazo || null" :codigo="form.codigo || null" />
        </template>
    </CadastroIconeEdicao>
</template>
