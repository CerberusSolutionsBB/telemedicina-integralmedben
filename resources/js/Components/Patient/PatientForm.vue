<script setup>
import { Link } from "@inertiajs/vue3";
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import PatientPlanoSelect from "@/Components/PatientPlanoSelect.vue";
import UfCidadeSelect from "@/Components/UfCidadeSelect.vue";
import { useCamposPaciente } from "@/Composables/Patient/useCamposPaciente";
import { useCepPaciente } from "@/Composables/Patient/useCepPaciente";
import { usePermissoesBeneficiario } from "@/Composables/Patient/usePermissoesBeneficiario";
import { MAX_FAMILIARES, useFamiliaresPaciente } from "@/Composables/Patient/useFamiliaresPaciente";
import { Loader2, Plus, Trash2 } from "lucide-vue-next";

/**
 * Formulário de beneficiário (Create/Edit). A lógica fica nos composables de Patient.
 */
const props = defineProps({
    form: { type: Object, required: true },
    planos: { type: Array, default: () => [] },
    // Vínculo de telemedicina existente (Edit): plano só leitura e não obrigatório.
    planoAtual: { type: Object, default: null },
    // Tipos de membro da família (MAE, PAI...) do plano familiar.
    tiposFamiliares: { type: Array, default: () => [] },
    cancelarHref: { type: String, required: true },
    rotuloSalvar: { type: String, default: "Salvar" },
});

const emit = defineEmits(["submit"]);

const form = props.form;
const isEdit = form.status !== undefined;
const { podeAlterarStatus } = usePermissoesBeneficiario();
// Plano obrigatório (exceto quem já tem vínculo); o CPF segue o plano.
const exigePlano = !props.planoAtual;

const { avisoCpf, avisoEmail } = useCamposPaciente(form);

// Limite do campo de nascimento (não pode estar no futuro).
const hoje = new Date().toISOString().slice(0, 10);
const { buscandoCep, cepNaoEncontrado } = useCepPaciente(form);
const { planoFamiliar, podeAdicionar, adicionarFamiliar, removerFamiliar, erroFamiliar, avisoCpfFamiliar } = useFamiliaresPaciente(form, {
    planos: props.planos,
    planoAtual: props.planoAtual,
});

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const titulo = "text-base font-semibold text-gray-900";
const label = "mb-1 block text-sm font-medium text-gray-700";
const input = (erro) => [
    "w-full h-10 rounded-lg border bg-white px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 disabled:bg-gray-50",
    erro ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-cyan-500",
];
</script>

<template>
    <form class="w-full space-y-6" @submit.prevent="emit('submit')">
        <!-- Dados do beneficiário -->
        <section :class="card">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <h2 :class="titulo">Dados do beneficiário</h2>
                <label v-if="isEdit && podeAlterarStatus" class="flex items-center gap-2 text-sm font-medium"
                    :class="form.status ? 'text-green-700' : 'text-red-700'">
                    <AppSwitch v-model="form.status" />
                    {{ form.status ? "Ativo" : "Inativo" }}
                </label>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-6 xl:col-span-6">
                    <label :class="label" for="nome">Nome <span class="text-red-600">*</span></label>
                    <input id="nome" v-model="form.nome" type="text" autocomplete="name" :class="input(form.errors.nome)" required />
                    <p v-if="form.errors.nome" class="mt-1 text-sm text-red-600">{{ form.errors.nome }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="cpf">CPF <span v-if="exigePlano" class="text-red-600">*</span></label>
                    <input id="cpf" v-model="form.cpf" type="text" inputmode="numeric" maxlength="14"
                        placeholder="000.000.000-00" autocomplete="off" :class="input(form.errors.cpf)" :required="exigePlano" />
                    <p v-if="form.errors.cpf || avisoCpf" class="mt-1 text-sm"
                        :class="form.errors.cpf ? 'text-red-600' : 'text-amber-700'">{{ form.errors.cpf || avisoCpf }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="rg">RG</label>
                    <input id="rg" v-model="form.rg" type="text" autocomplete="off" :class="input(form.errors.rg)" />
                    <p v-if="form.errors.rg" class="mt-1 text-sm text-red-600">{{ form.errors.rg }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-2">
                    <label :class="label" for="data_nascimento">Data de nascimento <span class="text-red-600">*</span></label>
                    <input id="data_nascimento" v-model="form.data_nascimento" type="date" :max="hoje"
                        :class="input(form.errors.data_nascimento)" required />
                    <p v-if="form.errors.data_nascimento" class="mt-1 text-sm text-red-600">{{ form.errors.data_nascimento }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-2">
                    <label :class="label" for="sexo">Sexo</label>
                    <select id="sexo" v-model="form.sexo" :class="input(form.errors.sexo)">
                        <option value="">Selecione</option>
                        <option value="masculino">Masculino</option>
                        <option value="feminino">Feminino</option>
                    </select>
                    <p v-if="form.errors.sexo" class="mt-1 text-sm text-red-600">{{ form.errors.sexo }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-5">
                    <label :class="label" for="email">E-mail</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" placeholder="nome@exemplo.com"
                        :class="input(form.errors.email)" />
                    <p v-if="form.errors.email || avisoEmail" class="mt-1 text-sm"
                        :class="form.errors.email ? 'text-red-600' : 'text-amber-700'">{{ form.errors.email || avisoEmail }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="numero">Celular</label>
                    <input id="numero" v-model="form.numero" type="tel" inputmode="numeric" maxlength="15"
                        placeholder="(00) 00000-0000" autocomplete="tel-national" :class="input(form.errors.numero)" />
                    <p v-if="form.errors.numero" class="mt-1 text-sm text-red-600">{{ form.errors.numero }}</p>
                </div>
            </div>
        </section>

        <!-- Endereço + Plano -->
        <section class="grid w-full grid-cols-1 gap-6 xl:grid-cols-3">
            <div :class="card" class="xl:col-span-2">
                <h2 :class="titulo">Endereço</h2>
                <p class="mb-4 text-sm text-gray-500">Digite o CEP para preencher o endereço automaticamente.</p>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-6">
                    <div class="md:col-span-2">
                        <label :class="label" for="cep">CEP</label>
                        <div class="relative">
                            <input id="cep" v-model="form.enderecos.cep" type="text" inputmode="numeric" maxlength="9"
                                placeholder="00000-000" autocomplete="postal-code" :class="[input(form.errors['enderecos.cep']), 'pr-9']" />
                            <Loader2 v-if="buscandoCep" class="absolute right-3 top-3 h-4 w-4 animate-spin text-gray-400" />
                        </div>
                        <p v-if="form.errors['enderecos.cep'] || cepNaoEncontrado" class="mt-1 text-sm"
                            :class="form.errors['enderecos.cep'] ? 'text-red-600' : 'text-amber-700'">
                            {{ form.errors['enderecos.cep'] || "CEP não encontrado. Preencha manualmente." }}
                        </p>
                    </div>

                    <div class="md:col-span-4">
                        <label :class="label" for="logradouro">Logradouro</label>
                        <input id="logradouro" v-model="form.enderecos.logradouro" type="text" placeholder="Rua, avenida..."
                            :class="input(form.errors['enderecos.logradouro'])" />
                    </div>

                    <div class="md:col-span-2">
                        <label :class="label" for="numero_endereco">Número</label>
                        <input id="numero_endereco" v-model="form.enderecos.numero" type="text" placeholder="Nº"
                            :class="input(form.errors['enderecos.numero'])" />
                    </div>

                    <div class="md:col-span-4">
                        <label :class="label" for="complemento">Complemento</label>
                        <input id="complemento" v-model="form.enderecos.complemento" type="text" placeholder="Apto, bloco..."
                            :class="input(form.errors['enderecos.complemento'])" />
                    </div>

                    <div class="md:col-span-2">
                        <label :class="label" for="bairro">Bairro</label>
                        <input id="bairro" v-model="form.enderecos.bairro" type="text" :class="input(form.errors['enderecos.bairro'])" />
                    </div>

                    <UfCidadeSelect v-model:uf="form.enderecos.estado" v-model:cidade="form.enderecos.cidade" class="md:col-span-4"
                        :error-uf="form.errors['enderecos.estado']" :error-cidade="form.errors['enderecos.cidade']" />
                </div>
            </div>

            <div :class="card">
                <h2 :class="[titulo, 'mb-4']">Plano / Telemedicina <span v-if="exigePlano" class="text-red-600">*</span></h2>
                <PatientPlanoSelect v-model="form.cod_plano" :planos="planos" :plano-atual="planoAtual"
                    :error="form.errors.cod_plano" :required="exigePlano" bare />
            </div>
        </section>

        <!-- Membros da família (plano familiar) -->
        <section v-if="planoFamiliar" :class="card">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 :class="titulo">Membros da família</h2>
                    <p class="text-sm text-gray-500">
                        Até {{ MAX_FAMILIARES }} familiares vinculados ao plano familiar do beneficiário
                        ({{ form.familiares.length }}/{{ MAX_FAMILIARES }}).
                    </p>
                </div>
                <button type="button" :disabled="!podeAdicionar" @click="adicionarFamiliar"
                    class="inline-flex h-10 items-center gap-2 rounded-lg border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50">
                    <Plus class="h-4 w-4" />
                    Adicionar familiar
                </button>
            </div>

            <p v-if="form.errors.familiares" class="mb-2 text-sm text-red-600">{{ form.errors.familiares }}</p>
            <p v-if="!form.familiares.length" class="text-sm text-gray-500">Nenhum familiar adicionado.</p>

            <div v-for="(familiar, i) in form.familiares" :key="familiar.id ?? `novo-${i}`"
                class="grid grid-cols-1 gap-4 border-t border-gray-100 py-4 first-of-type:border-t-0 first-of-type:pt-0 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-6 xl:col-span-4">
                    <label :class="label" :for="`familiar_nome_${i}`">Nome <span class="text-red-600">*</span></label>
                    <input :id="`familiar_nome_${i}`" v-model="familiar.nome" type="text" autocomplete="off"
                        :class="input(erroFamiliar(i, 'nome'))" required />
                    <p v-if="erroFamiliar(i, 'nome')" class="mt-1 text-sm text-red-600">{{ erroFamiliar(i, 'nome') }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" :for="`familiar_tipo_${i}`">Vínculo <span class="text-red-600">*</span></label>
                    <select :id="`familiar_tipo_${i}`" v-model="familiar.tipo" :class="input(erroFamiliar(i, 'tipo'))" required>
                        <option value="" disabled>Selecione</option>
                        <option v-for="tipo in tiposFamiliares" :key="tipo.value" :value="tipo.value">{{ tipo.label }}</option>
                    </select>
                    <p v-if="erroFamiliar(i, 'tipo')" class="mt-1 text-sm text-red-600">{{ erroFamiliar(i, 'tipo') }}</p>
                </div>

                <div class="md:col-span-3 xl:col-span-2">
                    <label :class="label" :for="`familiar_cpf_${i}`">CPF</label>
                    <input :id="`familiar_cpf_${i}`" v-model="familiar.cpf" type="text" inputmode="numeric" maxlength="14"
                        placeholder="000.000.000-00" autocomplete="off"
                        :class="input(erroFamiliar(i, 'cpf') || avisoCpfFamiliar(i))" />
                    <p v-if="erroFamiliar(i, 'cpf') || avisoCpfFamiliar(i)" class="mt-1 text-sm text-red-600">
                        {{ erroFamiliar(i, 'cpf') || avisoCpfFamiliar(i) }}
                    </p>
                </div>

                <div class="md:col-span-4 xl:col-span-2">
                    <label :class="label" :for="`familiar_nascimento_${i}`">Data de nascimento</label>
                    <input :id="`familiar_nascimento_${i}`" v-model="familiar.data_nascimento" type="date" :max="hoje"
                        :class="input(erroFamiliar(i, 'data_nascimento'))" />
                    <p v-if="erroFamiliar(i, 'data_nascimento')" class="mt-1 text-sm text-red-600">
                        {{ erroFamiliar(i, 'data_nascimento') }}
                    </p>
                </div>

                <div class="flex items-end md:col-span-2 xl:col-span-1">
                    <button type="button" :aria-label="`Remover familiar ${i + 1}`" @click="removerFamiliar(i)"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg text-red-600 hover:bg-red-50">
                        <Trash2 class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <Link :href="cancelarHref"
                class="inline-flex h-10 items-center rounded-lg px-4 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                Cancelar
            </Link>
            <button type="submit" :disabled="form.processing"
                class="inline-flex h-10 items-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-700 disabled:opacity-50">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                {{ rotuloSalvar }}
            </button>
        </div>
    </form>
</template>
