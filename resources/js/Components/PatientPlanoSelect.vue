<script setup>
import { computed } from "vue";
import { Label } from "@/Components/ui/label";
import { AlertCircle, HeartPulse, Info, Lock } from "lucide-vue-next";

const props = defineProps({
    planos: { type: Array, default: () => [] },
    // Vínculo de telemedicina já existente (Edit): o plano fica só para leitura.
    planoAtual: { type: Object, default: null },
    error: { type: String, default: null },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    // Sem cartão/cabeçalho próprios, para uso dentro de outra seção.
    bare: { type: Boolean, default: false },
});

const model = defineModel({ type: String, default: "" });

const selecionado = computed(() => props.planos.find((p) => p.value === model.value) ?? null);

// Saldo 0 em todos os planos: não há como cadastrar com plano.
const todosEsgotados = computed(() => props.planos.length > 0 && props.planos.every((p) => p.disponivel < 1));

const inputClass =
    "w-full h-10 px-3 text-sm border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:bg-gray-50";
</script>

<template>
    <div :class="bare ? '' : 'bg-white rounded-xl border border-gray-200 shadow-sm p-6'">
        <div v-if="!bare" class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
            <HeartPulse class="w-5 h-5 text-cyan-600" />
            <h2>Plano / Telemedicina</h2>
        </div>

        <div v-if="planoAtual" class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 border border-gray-200">
            <Lock class="w-4 h-4 text-gray-400 mt-0.5 shrink-0" />
            <div class="text-sm">
                <p class="font-medium text-gray-900">{{ planoAtual.plano_label }}</p>
                <p class="text-gray-500 mt-0.5">
                    Beneficiário já vinculado à telemedicina. Para trocar ou remover o plano, desvincule-o na
                    aba Telemedicina da página do parceiro.
                </p>
            </div>
        </div>

        <div v-else-if="!planos.length" class="flex items-start gap-2 text-sm"
            :class="required ? 'text-amber-700' : 'text-gray-500'">
            <AlertCircle v-if="required" class="w-4 h-4 shrink-0 mt-0.5" />
            <span>
                Nenhum plano habilitado para este parceiro.
                <template v-if="required">O plano é obrigatório: habilite os planos na página do parceiro para
                    poder salvar.</template>
            </span>
        </div>

        <div v-else class="space-y-4">
            <div>
                <Label for="cod_plano" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                    Plano <span v-if="required" class="text-red-600">*</span>
                </Label>
                <select id="cod_plano" v-model="model" :disabled="disabled" :required="required"
                    :class="[inputClass, error ? 'border-red-500 focus:ring-red-500' : '']">
                    <option value="" :disabled="required">{{ required ? 'Selecione um plano' : 'Sem plano' }}</option>
                    <option v-for="plano in planos" :key="plano.value" :value="plano.value"
                        :disabled="plano.disponivel < 1">
                        {{ plano.label }} — {{ plano.disponivel > 0 ? `${plano.disponivel} vaga(s)` : 'sem vagas' }}
                    </option>
                </select>
                <p v-if="error" class="mt-1 text-sm text-red-600">{{ error }}</p>
                <p v-else-if="todosEsgotados" class="mt-1 flex items-start gap-1 text-sm text-amber-700">
                    <AlertCircle class="w-4 h-4 mt-0.5 shrink-0" />
                    <span>Todos os planos estão sem vagas. Aumente a quantidade na aba Planos da página do parceiro para
                        cadastrar.</span>
                </p>
                <p v-else-if="selecionado" class="mt-1 text-xs text-gray-500">
                    {{ selecionado.emUso }} de {{ selecionado.quantidade }} vaga(s) em uso.
                </p>
            </div>

            <div v-if="model" class="flex items-start gap-2 p-3 rounded-lg bg-cyan-50 border border-cyan-200 text-xs text-cyan-800">
                <Info class="w-4 h-4 shrink-0 mt-0.5" />
                <span v-if="selecionado?.siprov === false">
                    Plano próprio do sistema: o beneficiário é vinculado ao <strong>{{ selecionado.label }}</strong>, sem
                    registro na SIPROV. O CPF passa a ser obrigatório.
                </span>
                <span v-else>
                    Ao salvar, o beneficiário será registrado na <strong>SIPROV</strong> (associado com benefício) e
                    adicionado aos associados da <strong>Telemedicina</strong>. O CPF passa a ser obrigatório.
                </span>
            </div>
        </div>
    </div>
</template>
