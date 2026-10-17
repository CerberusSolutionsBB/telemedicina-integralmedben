<script setup>
import CadastroIconeDetalhe from "@/Components/CadastroIcone/CadastroIconeDetalhe.vue";
import CupomBeneficioResumo from "@/Components/CupomBeneficio/CupomBeneficioResumo.vue";
import FraseCupom from "@/Components/CupomBeneficio/FraseCupom.vue";
import CodigoCupom from "@/Components/CupomBeneficio/CodigoCupom.vue";
import { CUPONS_BENEFICIO } from "@/Composables/CadastroIcone/modulos";

defineProps({
    breadcrumbs: { type: Array, required: true },
    item: { type: Object, required: true },
    historico: { type: Array, default: () => [] },
});
</script>

<template>
    <CadastroIconeDetalhe :modulo="CUPONS_BENEFICIO" :breadcrumbs="breadcrumbs" :item="item" :historico="historico">
        <template #detalhes>
            <FraseCupom titulo="O que o cupom dá" :tipo="item.tipo_desconto" :valor="item.valor_desconto" :plano="item.plano"
                :convenio="item.convenio?.titulo" :servico="item.servico?.titulo" :prazo="item.prazo" :codigo="item.codigo" />
            <CodigoCupom :codigo="item.codigo" />
            <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
                <h2 class="text-base font-semibold text-gray-900">Regras do cupom</h2>
                <p v-if="item.vencido" class="mt-2 text-sm font-medium text-red-700 dark:text-red-400">Este cupom venceu em {{ item.prazo_formatado }} e não vale mais.</p>
                <CupomBeneficioResumo :item="item" class="mt-3" />
            </section>
        </template>
    </CadastroIconeDetalhe>
</template>
