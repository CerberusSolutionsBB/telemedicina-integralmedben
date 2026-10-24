<script setup>
import InputMoeda from "@/Components/InputMoeda.vue";
import TituloSecao from "@/Components/CadastroIcone/TituloSecao.vue";
import { AlertCircle } from "lucide-vue-next";

/**
 * Campos próprios do processo no formulário: valor.
 */
const props = defineProps({
    form: { type: Object, required: true },
    // Número da seção (o formulário genérico informa).
    numero: { type: Number, default: 2 },
});

const form = props.form;

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const label = "mb-1 block text-sm font-medium text-gray-800";
const ajuda = "mt-1 text-sm text-gray-600";
const erro = "mt-1 flex items-start gap-1 text-sm text-red-600 dark:text-red-400";
</script>

<template>
    <section :class="card">
        <TituloSecao :numero="numero" titulo="Valor" ajuda="Quanto custa o processo." />

        <div class="mt-5">
            <label :class="label" for="valor">Valor <span class="text-red-600 dark:text-red-400" aria-hidden="true">*</span></label>
            <InputMoeda id="valor" v-model="form.valor" :invalido="!!form.errors.valor" required aria-required="true"
                aria-describedby="valor-ajuda valor-erro" />
            <p id="valor-ajuda" :class="ajuda">Digite só os números; os centavos entram sozinhos. Use 0,00 se o processo não tem custo.</p>
            <p v-if="form.errors.valor" id="valor-erro" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.valor }}</p>
        </div>
    </section>
</template>
