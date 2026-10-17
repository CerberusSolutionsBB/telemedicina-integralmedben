<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import { AlertCircle, Info, Loader2 } from "lucide-vue-next";

/**
 * Formulário do cupom (Create/Edit), no painel central e no do parceiro.
 * `parceiros` só no central (escolher dono); `teto` só no parceiro (desconto máximo em %).
 */
const props = defineProps({
    form: { type: Object, required: true },
    opcoes: { type: Object, required: true },
    parceiros: { type: Array, default: null },
    teto: { type: Number, default: null },
    // Cupom já usado: código, tipo e valor ficam travados.
    usado: { type: Boolean, default: false },
    cancelarHref: { type: String, required: true },
    rotuloSalvar: { type: String, default: "Salvar" },
});

const emit = defineEmits(["submit"]);

const form = props.form;

const alternarPlano = (codigo) => {
    form.planos = form.planos.includes(codigo) ? form.planos.filter((c) => c !== codigo) : [...form.planos, codigo];
};

const valorMaximo = computed(() => (form.tipo === "percentual" ? (props.teto ?? 100) : undefined));

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const titulo = "text-base font-semibold text-gray-900";
const ajuda = "mt-1 text-sm text-gray-600";
const label = "mb-1 block text-sm font-medium text-gray-700";
const erro = "mt-1 flex items-start gap-1 text-sm text-red-600";
const input = (temErro) => [
    "w-full min-h-11 rounded-lg border bg-white px-3 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 disabled:bg-gray-50 disabled:text-gray-500 sm:text-sm",
    temErro ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-cyan-500",
];
const opcao = (ativa) => [
    "flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors duration-150",
    ativa ? "border-cyan-500 bg-cyan-50 font-medium text-cyan-800" : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
];
</script>

<template>
    <form class="w-full space-y-6" @submit.prevent="emit('submit')">
        <!-- Desconto -->
        <section :class="card">
            <h2 :class="titulo">Desconto</h2>
            <p :class="ajuda">O código é o que o vendedor ou o beneficiário digita na venda.</p>

            <p v-if="usado" class="mt-3 flex items-start gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                <Info class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                Este cupom já foi usado: código, tipo e valor não podem mais ser alterados.
            </p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-3 xl:col-span-4">
                    <label :class="label" for="codigo">Código <span class="font-normal text-gray-500">(vazio = gerar automático)</span></label>
                    <input id="codigo" v-model="form.codigo" type="text" :maxlength="opcoes.limites.codigo" placeholder="Ex.: OUTUBRO10"
                        :disabled="usado" :class="[input(form.errors.codigo), 'uppercase']" :aria-invalid="!!form.errors.codigo" />
                    <p v-if="form.errors.codigo" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.codigo }}</p>
                </div>

                <fieldset class="min-w-0 md:col-span-3 xl:col-span-4">
                    <legend :class="label">Tipo <span class="text-red-600" aria-hidden="true">*</span></legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="tipo in opcoes.tipos" :key="tipo.value" :class="opcao(form.tipo === tipo.value)">
                            <input v-model="form.tipo" type="radio" :value="tipo.value" :disabled="usado" class="text-cyan-600 focus:ring-cyan-500" />
                            {{ tipo.label }}
                        </label>
                    </div>
                </fieldset>

                <div class="md:col-span-3 xl:col-span-4">
                    <label :class="label" for="valor">
                        {{ form.tipo === 'percentual' ? 'Desconto (%)' : 'Desconto (R$)' }} <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input id="valor" v-model="form.valor" type="number" step="0.01" min="0.01" :max="valorMaximo" required
                        :disabled="usado" :class="input(form.errors.valor)" :aria-invalid="!!form.errors.valor" />
                    <p v-if="teto !== null" class="mt-1 text-xs text-gray-500">
                        Máximo permitido para este parceiro: {{ teto }}%.
                        <template v-if="form.tipo === 'valor_fixo'">Valores fixos são limitados a {{ teto }}% do plano na venda.</template>
                    </p>
                    <p v-if="form.errors.valor" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.valor }}</p>
                </div>

                <div v-if="parceiros" class="md:col-span-3 xl:col-span-4">
                    <label :class="label" for="tenant_id">Parceiro</label>
                    <select id="tenant_id" v-model="form.tenant_id" :class="input(form.errors.tenant_id)">
                        <option value="">Todos os parceiros (global)</option>
                        <option v-for="parceiro in parceiros" :key="parceiro.id" :value="parceiro.id">{{ parceiro.nome }}</option>
                    </select>
                    <p v-if="form.errors.tenant_id" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.tenant_id }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-4">
                    <label :class="label" for="origem">Origem</label>
                    <select id="origem" v-model="form.origem" :class="input(form.errors.origem)">
                        <option v-for="origem in opcoes.origens" :key="origem.value" :value="origem.value">{{ origem.label }}</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label :class="label" for="texto_desconto">Texto abaixo do desconto (aparece no PDF)</label>
                <input id="texto_desconto" v-model="form.texto_desconto" type="text" :maxlength="opcoes.limites.texto_desconto"
                    placeholder="na adesão ao plano de telemedicina" aria-describedby="texto_desconto-ajuda"
                    :class="input(form.errors.texto_desconto)" :aria-invalid="!!form.errors.texto_desconto" />
                <div class="mt-1 flex items-start gap-2">
                    <p id="texto_desconto-ajuda" class="text-sm text-gray-600">
                        Diga em que o cliente ganha o desconto (ex.: "na consulta com nutricionista"). Vazio = "na adesão ao plano de telemedicina".
                    </p>
                    <span class="ml-auto shrink-0 text-xs tabular-nums text-gray-600">{{ (form.texto_desconto || '').length }}/{{ opcoes.limites.texto_desconto }}</span>
                </div>
                <p class="mt-2 rounded-lg bg-gray-50 px-3 py-2 text-sm text-gray-700">
                    Prévia no PDF: <strong>{{ form.tipo === 'percentual' ? `${form.valor || '0'}% OFF` : `R$ ${form.valor || '0'} OFF` }}</strong>
                    {{ form.texto_desconto || 'na adesão ao plano de telemedicina' }}
                </p>
                <p v-if="form.errors.texto_desconto" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.texto_desconto }}</p>
            </div>
        </section>

        <!-- Validade e limites -->
        <section :class="card">
            <h2 :class="titulo">Validade e limites</h2>
            <p :class="ajuda">Fora do período ou depois de atingir o limite, o cupom deixa de valer sozinho.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="inicio_em">Início <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="inicio_em" v-model="form.inicio_em" type="datetime-local" required :class="input(form.errors.inicio_em)" />
                    <p v-if="form.errors.inicio_em" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.inicio_em }}</p>
                </div>
                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="expira_em">Expira em <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="expira_em" v-model="form.expira_em" type="datetime-local" required :class="input(form.errors.expira_em)" />
                    <p v-if="form.errors.expira_em" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.expira_em }}</p>
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="limite_uso_total">Limite total <span class="font-normal text-gray-500">(vazio = ilimitado)</span></label>
                    <input id="limite_uso_total" v-model="form.limite_uso_total" type="number" min="1" step="1" :class="input(form.errors.limite_uso_total)" />
                    <p v-if="form.errors.limite_uso_total" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.limite_uso_total }}</p>
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="limite_uso_por_cpf">Usos por CPF <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="limite_uso_por_cpf" v-model="form.limite_uso_por_cpf" type="number" min="1" step="1" required :class="input(form.errors.limite_uso_por_cpf)" />
                    <p v-if="form.errors.limite_uso_por_cpf" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.limite_uso_por_cpf }}</p>
                </div>
                <div class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="valor_minimo">Valor mínimo do plano (R$)</label>
                    <input id="valor_minimo" v-model="form.valor_minimo" type="number" min="0" step="0.01" :class="input(form.errors.valor_minimo)" />
                    <p v-if="form.errors.valor_minimo" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.valor_minimo }}</p>
                </div>
            </div>
        </section>

        <!-- Onde vale -->
        <section :class="card">
            <h2 :class="titulo">Onde vale</h2>
            <p :class="ajuda">Restrinja a planos específicos ou ao primeiro contrato do CPF neste parceiro.</p>

            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                <label v-for="aplicavel in opcoes.aplicaveis" :key="aplicavel.value" :class="opcao(form.aplicavel_a === aplicavel.value)">
                    <input v-model="form.aplicavel_a" type="radio" :value="aplicavel.value" class="text-cyan-600 focus:ring-cyan-500" />
                    {{ aplicavel.label }}
                </label>
            </div>

            <div v-if="form.aplicavel_a === 'planos'" class="mt-4">
                <p :class="label">Planos <span class="text-red-600" aria-hidden="true">*</span></p>
                <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-4">
                    <label v-for="plano in opcoes.planos" :key="plano.value" :class="opcao(form.planos.includes(plano.value))">
                        <input type="checkbox" :checked="form.planos.includes(plano.value)" class="rounded text-cyan-600 focus:ring-cyan-500"
                            @change="alternarPlano(plano.value)" />
                        {{ plano.label }}
                    </label>
                </div>
                <p v-if="form.errors.planos" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />{{ form.errors.planos }}</p>
            </div>

            <div class="mt-4">
                <label :class="label" for="observacoes">Observações <span class="font-normal text-gray-500">(opcional)</span></label>
                <textarea id="observacoes" v-model="form.observacoes" rows="3" :maxlength="opcoes.limites.observacoes"
                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-base focus:outline-none focus:ring-2 focus:ring-cyan-500 sm:text-sm" />
            </div>
        </section>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <Link :href="cancelarHref"
                class="inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </Link>
            <button type="submit" :disabled="form.processing"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-5 text-sm font-semibold text-white hover:bg-cyan-700 disabled:opacity-60">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" aria-hidden="true" />
                {{ rotuloSalvar }}
            </button>
        </div>
    </form>
</template>
