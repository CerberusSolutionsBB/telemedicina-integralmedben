<script setup>
import { computed, watch } from "vue";
import SeletorComModal from "@/Components/SeletorComModal.vue";
import TituloSecao from "@/Components/CadastroIcone/TituloSecao.vue";
import { gerarCodigoCupom } from "@/Composables/CupomBeneficio/codigo";
import { AlertCircle, AlertTriangle, RefreshCw } from "lucide-vue-next";

/**
 * Campos próprios do cupom de benefício no formulário, em duas seções numeradas:
 * código e desconto (código, tipo, valor, prazo) e onde vale (convênio ou serviço
 * obrigatório; plano opcional).
 */
const props = defineProps({
    form: { type: Object, required: true },
    // Número da primeira seção (o formulário genérico informa).
    numero: { type: Number, default: 2 },
    planos: { type: Array, default: () => [] },
    convenios: { type: Array, default: () => [] },
    servicos: { type: Array, default: () => [] },
    tiposDesconto: { type: Array, default: () => [] },
});

const form = props.form;

const hoje = new Date().toISOString().slice(0, 10);
const prazoPassado = computed(() => form.prazo && form.prazo < hoje);
const faltaVinculo = computed(() => form.errors.convenio_id || form.errors.servico_id);

// Escolheu um convênio ou serviço: o aviso de vínculo obrigatório some na hora.
watch(() => [form.convenio_id, form.servico_id], ([convenio, servico]) => {
    if (convenio || servico) {
        form.clearErrors("convenio_id", "servico_id");
    }
});

// Opções dos modais de convênio e serviço; inativos aparecem identificados.
const paraItens = (lista) => lista.map((i) => ({ value: i.id, label: i.titulo, hint: i.status ? null : "Inativo" }));
const itensConvenios = computed(() => paraItens(props.convenios));
const itensServicos = computed(() => paraItens(props.servicos));

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const label = "mb-1 block text-sm font-medium text-gray-800";
const ajuda = "mt-1 text-sm text-gray-600";
const erro = "mt-1 flex items-start gap-1 text-sm text-red-600 dark:text-red-400";
const obrigatorio = "text-red-600 dark:text-red-400";
const input = (temErro) => [
    "w-full min-h-11 rounded-lg border bg-white px-3 text-base text-gray-900 focus:outline-none focus-visible:ring-2 sm:text-sm",
    temErro ? "border-red-500 focus-visible:ring-red-500" : "border-gray-300 focus-visible:ring-cyan-500",
];
const opcao = (ativa) => [
    "flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors duration-150 motion-reduce:transition-none focus-within:ring-2 focus-within:ring-cyan-500",
    ativa ? "border-cyan-500 bg-cyan-50 font-semibold text-cyan-800" : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
];
</script>

<template>
    <section :class="card">
        <TituloSecao :numero="numero" titulo="Código e desconto" ajuda="O código que o beneficiário apresenta, quanto ele economiza e até quando vale." />

        <div class="mt-5">
            <label :class="label" for="codigo">Código do cupom</label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <input id="codigo" v-model="form.codigo" type="text" maxlength="20" autocomplete="off" spellcheck="false"
                    aria-describedby="codigo-ajuda codigo-erro" :aria-invalid="!!form.errors.codigo"
                    @input="form.codigo = form.codigo.toUpperCase().replace(/\s+/g, '')"
                    :class="[input(form.errors.codigo), 'font-mono text-lg font-semibold tracking-widest sm:max-w-xs sm:text-lg']" />
                <button type="button" @click="form.codigo = gerarCodigoCupom()"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                    <RefreshCw class="h-4 w-4" aria-hidden="true" /> Gerar outro
                </button>
            </div>
            <p id="codigo-ajuda" :class="ajuda">Já vem um código pronto. Se preferir, troque por um fácil de lembrar (ex.: FARMACIA15).</p>
            <p v-if="form.errors.codigo" id="codigo-erro" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.codigo }}</p>
        </div>

        <div class="mt-5 grid grid-cols-1 gap-5 border-t border-gray-100 pt-5 md:grid-cols-6 xl:grid-cols-12">
            <fieldset class="min-w-0 md:col-span-6 xl:col-span-4">
                <legend :class="label">Tipo de desconto <span :class="obrigatorio" aria-hidden="true">*</span></legend>
                <div class="grid grid-cols-2 gap-2">
                    <label v-for="tipo in tiposDesconto" :key="tipo.value" :class="opcao(form.tipo_desconto === tipo.value)">
                        <input v-model="form.tipo_desconto" type="radio" :value="tipo.value" class="text-cyan-600 focus:ring-cyan-500" />
                        {{ tipo.label }}
                    </label>
                </div>
                <p :class="ajuda">{{ form.tipo_desconto === 'percentual' ? 'Uma porcentagem do preço (ex.: 15%).' : 'Um valor em reais (ex.: R$ 30,00).' }}</p>
            </fieldset>

            <div class="md:col-span-3 xl:col-span-4">
                <label :class="label" for="valor_desconto">
                    {{ form.tipo_desconto === 'percentual' ? 'Desconto em %' : 'Desconto em R$' }} <span :class="obrigatorio" aria-hidden="true">*</span>
                </label>
                <input id="valor_desconto" v-model="form.valor_desconto" type="number" inputmode="decimal" step="0.01" min="0.01"
                    :max="form.tipo_desconto === 'percentual' ? 100 : undefined" required aria-required="true"
                    aria-describedby="valor_desconto-ajuda valor_desconto-erro"
                    :class="input(form.errors.valor_desconto)" :aria-invalid="!!form.errors.valor_desconto" />
                <p id="valor_desconto-ajuda" :class="ajuda">{{ form.tipo_desconto === 'percentual' ? 'De 0,01 a 100.' : 'Use vírgula ou ponto para os centavos.' }}</p>
                <p v-if="form.errors.valor_desconto" id="valor_desconto-erro" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.valor_desconto }}</p>
            </div>

            <div class="md:col-span-3 xl:col-span-4">
                <label :class="label" for="prazo">Válido até</label>
                <input id="prazo" v-model="form.prazo" type="date" aria-describedby="prazo-ajuda prazo-erro" :class="input(form.errors.prazo)" />
                <p id="prazo-ajuda" :class="ajuda">Deixe vazio se o cupom não tem prazo para acabar.</p>
                <p v-if="form.errors.prazo" id="prazo-erro" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.prazo }}</p>
                <p v-else-if="prazoPassado" class="mt-1 flex items-start gap-1 text-sm text-amber-700 dark:text-amber-400">
                    <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" /> Essa data já passou: o cupom fica vencido.
                </p>
            </div>
        </div>
    </section>

    <section :class="card">
        <TituloSecao :numero="numero + 1" titulo="Onde vale"
            ajuda="Escolha o convênio, o serviço ou os dois. Sem plano, vale para beneficiários de qualquer plano." />

        <div class="mt-5 grid grid-cols-1 gap-5 md:grid-cols-3">
            <div>
                <label :class="label" for="convenio_id">Convênio <span :class="obrigatorio" aria-hidden="true">*</span></label>
                <SeletorComModal id="convenio_id" v-model="form.convenio_id" :items="itensConvenios" placeholder="Escolher convênio"
                    titulo="Selecionar convênio" descricao="Convênio onde o desconto vale." busca-placeholder="Buscar convênio..."
                    vazio="Nenhum convênio encontrado." limpavel :error="!!faltaVinculo" />
                <p :class="ajuda">{{ convenios.length ? 'Ex.: farmácia, ótica, laboratório.' : 'Nenhum convênio cadastrado ainda.' }}</p>
            </div>
            <div>
                <label :class="label" for="servico_id">Serviço <span :class="obrigatorio" aria-hidden="true">*</span></label>
                <SeletorComModal id="servico_id" v-model="form.servico_id" :items="itensServicos" placeholder="Escolher serviço"
                    titulo="Selecionar serviço" descricao="Serviço onde o desconto vale." busca-placeholder="Buscar serviço..."
                    vazio="Nenhum serviço encontrado." limpavel :error="!!faltaVinculo" />
                <p :class="ajuda">{{ servicos.length ? 'Ex.: teleconsulta, exames.' : 'Nenhum serviço cadastrado ainda.' }}</p>
            </div>
            <div>
                <label :class="label" for="cod_plano">Plano do beneficiário</label>
                <select id="cod_plano" v-model="form.cod_plano" :class="input(form.errors.cod_plano)">
                    <option value="">Qualquer plano</option>
                    <option v-for="p in planos" :key="p.value" :value="p.value">{{ p.label }}</option>
                </select>
                <p :class="ajuda">Opcional. Limita o cupom a um plano.</p>
            </div>
        </div>
        <p class="mt-3 text-sm text-gray-600"><span :class="obrigatorio">*</span> Preencha pelo menos um: convênio ou serviço.</p>
        <p v-if="faltaVinculo" :class="erro"><AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.convenio_id || form.errors.servico_id }}</p>
    </section>
</template>
