<script setup>
import { Link } from "@inertiajs/vue3";
import { useCamposDesempenho } from "@/Composables/Desempenho/useCamposDesempenho";
import UsuariosDialog from "@/Components/Desempenho/UsuariosDialog.vue";
import { AlertCircle, AlertTriangle, Info, Loader2, Users, X } from "lucide-vue-next";

/**
 * Formulário da meta de desempenho (Create/Edit). A lógica fica nos composables de Desempenho.
 * Seções seguem a descrição da tela: quem participa, o que conta e até quando.
 */
const props = defineProps({
    form: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    usuariosIniciais: { type: Array, default: () => [] },
    funcoes: { type: Array, default: () => [] },
    tipos: { type: Array, default: () => [] },
    planos: { type: Array, default: () => [] },
    limites: { type: Object, default: () => ({ titulo: 255, descricao: 2000 }) },
    cancelarHref: { type: String, required: true },
    rotuloSalvar: { type: String, default: "Salvar" },
});

const emit = defineEmits(["submit"]);

const form = props.form;
const {
    alternarRole,
    usuariosDoPerfil,
    abrirSelecaoUsuarios,
    confirmarUsuarios,
    removerUsuario,
    perfilModal,
    modalAberto,
    selecionadosModal,
    erroUsuarios,
    resumo,
    contador,
} = useCamposDesempenho(form, { planos: props.planos, roles: props.roles, usuariosIniciais: props.usuariosIniciais });

const card = "w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6";
const titulo = "text-base font-semibold text-gray-900";
const ajuda = "mt-1 text-sm text-gray-600";
const label = "mb-1 block text-sm font-medium text-gray-700";
const erro = "mt-1 flex items-start gap-1 text-sm text-red-600";
const foco = "focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 focus-visible:ring-offset-2";
const input = (temErro) => [
    "w-full min-h-11 rounded-lg border bg-white px-3 text-base text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 disabled:bg-gray-50 sm:text-sm",
    temErro ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-cyan-500",
];
const opcao = (ativa) => [
    "flex min-h-11 cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors duration-150 motion-reduce:transition-none",
    ativa ? "border-cyan-500 bg-cyan-50 font-medium text-cyan-800" : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
];
</script>

<template>
    <form class="w-full space-y-6" @submit.prevent="emit('submit')">
        <!-- Dados da meta -->
        <section :class="card">
            <h2 :class="titulo">Dados da meta</h2>
            <p :class="ajuda">Um nome curto ajuda a encontrar a meta depois.</p>

            <div class="mt-4 grid grid-cols-1 gap-4">
                <div>
                    <label :class="label" for="titulo">Título <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="titulo" v-model="form.titulo" type="text" :maxlength="limites.titulo" placeholder="Ex.: Meta de cadastros de outubro"
                        :class="input(form.errors.titulo)" :aria-invalid="!!form.errors.titulo" aria-describedby="titulo-erro titulo-contador" required />
                    <div class="mt-1 flex items-start justify-between gap-2">
                        <p id="titulo-erro" class="flex items-start gap-1 text-sm text-red-600">
                            <template v-if="form.errors.titulo">
                                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.titulo }}
                            </template>
                        </p>
                        <span id="titulo-contador" class="shrink-0 text-xs tabular-nums" :class="contador('titulo', limites.titulo).classe"
                            aria-live="polite">{{ contador('titulo', limites.titulo).texto }}</span>
                    </div>
                </div>
                <div>
                    <label :class="label" for="descricao">Descrição <span class="font-normal text-gray-500">(opcional)</span></label>
                    <textarea id="descricao" v-model="form.descricao" rows="3" :maxlength="limites.descricao"
                        :aria-invalid="!!form.errors.descricao" aria-describedby="descricao-erro descricao-contador"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-base leading-relaxed focus:outline-none focus:ring-2 sm:text-sm"
                        :class="form.errors.descricao ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-cyan-500'" />
                    <div class="mt-1 flex items-start justify-between gap-2">
                        <p id="descricao-erro" class="flex items-start gap-1 text-sm text-red-600">
                            <template v-if="form.errors.descricao">
                                <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.descricao }}
                            </template>
                        </p>
                        <span id="descricao-contador" class="shrink-0 text-xs tabular-nums" :class="contador('descricao', limites.descricao).classe"
                            aria-live="polite">{{ contador('descricao', limites.descricao).texto }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Quem participa -->
        <section :class="card">
            <h2 id="roles-titulo" :class="titulo">Quem participa <span class="text-red-600" aria-hidden="true">*</span></h2>
            <p id="roles-ajuda" :class="ajuda">Marque um perfil e escolha os usuários dele que entram na meta.</p>

            <p v-if="!roles.length" class="mt-4 text-sm text-gray-600">Nenhum perfil cadastrado no Controle de Acesso.</p>
            <div v-else class="mt-4 grid grid-cols-1 gap-3 lg:grid-cols-2" role="group"
                aria-labelledby="roles-titulo" aria-describedby="roles-ajuda roles-erro">
                <div v-for="role in roles" :key="role.id" class="rounded-xl border p-3 transition-colors duration-150 motion-reduce:transition-none"
                    :class="form.roles.includes(role.id) ? 'border-cyan-500 bg-cyan-50/40' : 'border-gray-300 bg-white'">
                    <label class="flex min-h-11 cursor-pointer items-center gap-2 text-sm font-medium text-gray-800">
                        <input type="checkbox" :checked="form.roles.includes(role.id)"
                            class="h-4 w-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" @change="alternarRole(role.id)" />
                        {{ role.name }}
                    </label>

                    <template v-if="form.roles.includes(role.id)">
                        <button type="button" @click="abrirSelecaoUsuarios(role)"
                            :class="['inline-flex min-h-11 items-center gap-1.5 rounded-lg px-2 text-sm font-medium text-cyan-700 hover:bg-cyan-50 hover:text-cyan-800', foco]">
                            <Users class="h-4 w-4" aria-hidden="true" />
                            Selecionar usuários
                            <span class="rounded-full bg-cyan-100 px-1.5 text-xs font-semibold text-cyan-800">
                                {{ usuariosDoPerfil(role.id).length }}
                                <span class="sr-only">selecionados</span>
                            </span>
                        </button>

                        <p v-if="!usuariosDoPerfil(role.id).length" class="mt-1 flex items-start gap-1 text-sm text-amber-700">
                            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                            Nenhum usuário selecionado. Este perfil não terá participantes.
                        </p>
                        <ul v-else class="mt-2 flex flex-wrap gap-1.5">
                            <li v-for="usuario in usuariosDoPerfil(role.id)" :key="usuario.id"
                                class="inline-flex max-w-full items-center gap-1 rounded-full border border-gray-200 bg-white py-0.5 pl-2.5 pr-0.5 text-sm text-gray-700">
                                <span class="truncate" :title="usuario.email">{{ usuario.name }}</span>
                                <button type="button" :aria-label="`Remover ${usuario.name}`" :title="`Remover ${usuario.name}`"
                                    :class="['inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-gray-500 hover:bg-red-50 hover:text-red-600', foco]"
                                    @click="removerUsuario(usuario, role.id)">
                                    <X class="h-4 w-4" aria-hidden="true" />
                                </button>
                            </li>
                        </ul>
                    </template>
                </div>
            </div>
            <div id="roles-erro">
                <p v-if="form.errors.roles" :class="[erro, 'mt-2']">
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.roles }}
                </p>
                <p v-if="erroUsuarios" :class="[erro, 'mt-2']">
                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ erroUsuarios }}
                </p>
            </div>

            <UsuariosDialog v-model:open="modalAberto" :perfil="perfilModal" :selecionados="selecionadosModal"
                @confirm="confirmarUsuarios" />
        </section>

        <!-- O que conta -->
        <section :class="card">
            <h2 :class="titulo">O que conta</h2>
            <p :class="ajuda">Escolha o que soma pontos e quanto é preciso para bater a meta.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-6 xl:col-span-6">
                    <label :class="label" for="funcao">Função <span class="text-red-600" aria-hidden="true">*</span></label>
                    <select id="funcao" v-model="form.funcao" :class="input(form.errors.funcao)"
                        :aria-invalid="!!form.errors.funcao" aria-describedby="funcao-erro" required>
                        <option v-for="funcao in funcoes" :key="funcao.value" :value="funcao.value">{{ funcao.label }}</option>
                    </select>
                    <p v-if="form.errors.funcao" id="funcao-erro" :class="erro">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.funcao }}
                    </p>
                </div>

                <!-- Plano escolhido fica logo abaixo das opções, sem empurrar os outros campos -->
                <fieldset class="min-w-0 md:col-span-6 xl:col-span-6">
                    <legend :class="label">Planos que contam <span class="text-red-600" aria-hidden="true">*</span></legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label :class="opcao(form.escopo_plano === 'todos')">
                            <input v-model="form.escopo_plano" type="radio" value="todos" class="text-cyan-600 focus:ring-cyan-500" />
                            Todos os planos
                        </label>
                        <label :class="opcao(form.escopo_plano === 'plano')">
                            <input v-model="form.escopo_plano" type="radio" value="plano" class="text-cyan-600 focus:ring-cyan-500" />
                            Apenas um plano
                        </label>
                    </div>

                    <div v-if="form.escopo_plano === 'plano'" class="mt-3">
                        <label :class="label" for="cod_plano">Plano <span class="text-red-600" aria-hidden="true">*</span></label>
                        <select id="cod_plano" v-model="form.cod_plano" :class="input(form.errors.cod_plano)"
                            :aria-invalid="!!form.errors.cod_plano" aria-describedby="cod_plano-erro" required>
                            <option value="" disabled>Selecione um plano</option>
                            <option v-for="plano in planos" :key="plano.value" :value="plano.value">{{ plano.label }}</option>
                        </select>
                        <p v-if="form.errors.cod_plano" id="cod_plano-erro" :class="erro">
                            <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.cod_plano }}
                        </p>
                    </div>
                </fieldset>

                <fieldset class="min-w-0 md:col-span-6 xl:col-span-6" aria-describedby="tipo-ajuda">
                    <legend :class="label">Tipo de meta <span class="text-red-600" aria-hidden="true">*</span></legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="tipo in tipos" :key="tipo.value" :class="opcao(form.tipo_meta === tipo.value)">
                            <input v-model="form.tipo_meta" type="radio" :value="tipo.value" class="text-cyan-600 focus:ring-cyan-500" />
                            {{ tipo.label }}
                        </label>
                    </div>
                    <p id="tipo-ajuda" :class="ajuda">
                        {{ form.tipo_meta === 'coletiva' ? 'A soma dos registros do grupo precisa bater a meta.' : 'Cada usuário precisa bater a meta sozinho.' }}
                    </p>
                </fieldset>

                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="meta">
                        {{ form.funcao === 'comissao_venda_plano' ? 'Quantidade de vendas' : 'Quantidade de registros' }}
                        <span class="text-red-600" aria-hidden="true">*</span>
                    </label>
                    <input id="meta" v-model.number="form.meta" type="number" inputmode="numeric" min="1" step="1" :class="input(form.errors.meta)"
                        :aria-invalid="!!form.errors.meta" aria-describedby="meta-erro" required />
                    <p v-if="form.errors.meta" id="meta-erro" :class="erro">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.meta }}
                    </p>
                </div>

                <div v-if="form.funcao === 'comissao_venda_plano'" class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="meta_valor">Comissão em R$ <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="meta_valor" v-model.number="form.meta_valor" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0,00"
                        :class="input(form.errors.meta_valor)" :aria-invalid="!!form.errors.meta_valor" aria-describedby="meta_valor-erro" required />
                    <p v-if="form.errors.meta_valor" id="meta_valor-erro" :class="erro">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.meta_valor }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Até quando -->
        <section :class="card">
            <h2 :class="titulo">Até quando</h2>
            <p :class="ajuda">Só contam os registros feitos dentro deste período.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="data_inicio">Início <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="data_inicio" v-model="form.data_inicio" type="date" :class="input(form.errors.data_inicio)"
                        :aria-invalid="!!form.errors.data_inicio" aria-describedby="data_inicio-erro" required />
                    <p v-if="form.errors.data_inicio" id="data_inicio-erro" :class="erro">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.data_inicio }}
                    </p>
                </div>

                <div class="md:col-span-3 xl:col-span-3">
                    <label :class="label" for="prazo">Prazo <span class="text-red-600" aria-hidden="true">*</span></label>
                    <input id="prazo" v-model="form.prazo" type="date" :min="form.data_inicio || undefined" :class="input(form.errors.prazo)"
                        :aria-invalid="!!form.errors.prazo" aria-describedby="prazo-erro" required />
                    <p v-if="form.errors.prazo" id="prazo-erro" :class="erro">
                        <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />{{ form.errors.prazo }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Resumo do que foi configurado, logo antes de salvar -->
        <div class="flex items-start gap-2 rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm leading-relaxed text-cyan-800 dark:border-cyan-800"
            aria-live="polite">
            <Info class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            <p><span class="font-semibold">Resumo:</span> {{ resumo }}</p>
        </div>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <Link :href="cancelarHref"
                :class="['inline-flex min-h-11 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50', foco]">
                Cancelar
            </Link>
            <button type="submit" :disabled="form.processing"
                :class="['inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-700 disabled:opacity-60', foco]">
                <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin motion-reduce:animate-none" aria-hidden="true" />
                {{ form.processing ? 'Salvando…' : rotuloSalvar }}
            </button>
        </div>
    </form>
</template>
