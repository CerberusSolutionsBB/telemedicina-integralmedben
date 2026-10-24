<script setup>
import { computed } from "vue";

/**
 * Campo de dinheiro (R$) com máscara: a pessoa digita só números e eles entram
 * pelos centavos, como no caixa ("150000" -> 1.500,00). O v-model recebe um
 * número (1500) ou "" quando vazio.
 */
// required, aria-* etc. vão para o <input>, não para a div do prefixo.
defineOptions({ inheritAttrs: false });

const props = defineProps({
    id: { type: String, required: true },
    invalido: { type: Boolean, default: false },
    // Teto em reais (o padrão é o max:99999999 das validações de valor).
    maximo: { type: Number, default: 99999999 },
});

const valor = defineModel({ type: [Number, String], default: "" });

const formatar = (numero) => Number(numero).toLocaleString("pt-BR", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

const exibido = computed(() => (valor.value === "" || valor.value === null ? "" : formatar(valor.value)));

const digitar = (evento) => {
    const digitos = evento.target.value.replace(/\D/g, "").replace(/^0+(?=\d)/, "");

    if (!digitos) {
        valor.value = "";
        evento.target.value = "";
        return;
    }

    const numero = Math.min(Number(digitos) / 100, props.maximo);
    valor.value = numero;
    // Reescreve mesmo se o número não mudou (ex.: letra digitada), mantendo a máscara.
    evento.target.value = formatar(numero);
};
</script>

<template>
    <div class="relative">
        <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-base font-medium text-gray-500 sm:text-sm" aria-hidden="true">R$</span>
        <input v-bind="$attrs" :id="id" :value="exibido" type="text" inputmode="numeric" autocomplete="off" placeholder="0,00"
            :aria-invalid="invalido" @input="digitar"
            class="w-full min-h-11 rounded-lg border bg-white pl-10 pr-3 text-base tabular-nums text-gray-900 placeholder:text-gray-500 focus:outline-none focus-visible:ring-2 sm:text-sm"
            :class="invalido ? 'border-red-500 focus-visible:ring-red-500' : 'border-gray-300 focus-visible:ring-cyan-500'" />
    </div>
</template>
