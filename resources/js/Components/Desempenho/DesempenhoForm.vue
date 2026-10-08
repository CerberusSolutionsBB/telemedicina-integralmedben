<script setup>
import { Link } from "@inertiajs/vue3";
import { useCamposDesempenho } from "@/Composables/Desempenho/useCamposDesempenho";
import UsuariosDialog from "@/Components/Desempenho/UsuariosDialog.vue";
import { Info, Loader2, Users, X } from "lucide-vue-next";

/**
 * Formulário da meta de desempenho (Create/Edit). A lógica fica nos composables de Desempenho.
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
const titulo = "mb-4 text-base font-semibold text-gray-900";
const label = "mb-1 block text-sm font-medium text-gray-700";
const input = (erro) => [
    "w-full h-10 rounded-lg border bg-white px-3 text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-2 disabled:bg-gray-50",
    erro ? "border-red-500 focus:ring-red-500" : "border-gray-300 focus:ring-cyan-500",
];
const opcao = (ativa) => [
    "flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm transition-colors",
    ativa ? "border-cyan-500 bg-cyan-50 text-cyan-800" : "border-gray-300 bg-white text-gray-700 hover:bg-gray-50",
];
</script>

<template>
    <form class="w-full space-y-6" @submit.prevent="emit('submit')">
        <!-- Dados da meta -->
        <section :class="card">
            <h2 :class="titulo">Dados da meta</h2>
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label :class="label" for="titulo">Título <span class="text-red-600">*</span></label>
                    <input id="titulo" v-model="form.titulo" type="text" :maxlength="limites.titulo" placeholder="Ex.: Meta de cadastros de outubro"
                        :class="input(form.errors.titulo)" aria-describedby="titulo-contador" required />
                    <div class="mt-1 flex items-start justify-between gap-2">
                        <p class="text-sm text-red-600">{{ form.errors.titulo }}</p>
                        <span id="titulo-contador" class="shrink-0 text-xs tabular-nums" :class="contador('titulo', limites.titulo).classe"
                            aria-live="polite">{{ contador('titulo', limites.titulo).texto }}</span>
                    </div>
                </div>
                <div>
                    <label :class="label" for="descricao">Descrição</label>
                    <textarea id="descricao" v-model="form.descricao" rows="3" :maxlength="limites.descricao" aria-describedby="descricao-contador"
                        class="w-full rounded-lg border bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2"
                        :class="form.errors.descricao ? 'border-red-500 focus:ring-red-500' : 'border-gray-300 focus:ring-cyan-500'" />
                    <div class="mt-1 flex items-start justify-between gap-2">
                        <p class="text-sm text-red-600">{{ form.errors.descricao }}</p>
                        <span id="descricao-contador" class="shrink-0 text-xs tabular-nums" :class="contador('descricao', limites.descricao).classe"
                            aria-live="polite">{{ contador('descricao', limites.descricao).texto }}</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Participantes -->
        <section :class="card">
            <h2 class="text-base font-semibold text-gray-900">Perfis de usuário <span class="text-red-600">*</span></h2>
            <p class="mb-4 text-sm text-gray-500">Escolha os perfis e selecione os usuários que participam da meta.</p>

            <p v-if="!roles.length" class="text-sm text-gray-500">Nenhum perfil cadastrado no Controle de Acesso.</p>
            <div v-else class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                <div v-for="role in roles" :key="role.id" class="rounded-xl border p-3 transition-colors"
                    :class="form.roles.includes(role.id) ? 'border-cyan-500 bg-cyan-50/40' : 'border-gray-300 bg-white'">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-gray-800">
                        <input type="checkbox" :checked="form.roles.includes(role.id)"
                            class="h-4 w-4 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" @change="alternarRole(role.id)" />
                        {{ role.name }}
                    </label>

                    <template v-if="form.roles.includes(role.id)">
                        <button type="button" @click="abrirSelecaoUsuarios(role)"
                            class="mt-2 inline-flex items-center gap-1.5 text-sm font-medium text-cyan-700 hover:text-cyan-800">
                            <Users class="h-4 w-4" />
                            Selecionar usuários
                            <span class="rounded-full bg-cyan-100 px-1.5 text-xs font-semibold text-cyan-800">
                                {{ usuariosDoPerfil(role.id).length }}
                            </span>
                        </button>

                        <p v-if="!usuariosDoPerfil(role.id).length" class="mt-1 text-xs text-amber-600">
                            Nenhum usuário selecionado — este perfil não terá participantes.
                        </p>
                        <ul v-else class="mt-2 flex flex-wrap gap-1.5">
                            <li v-for="usuario in usuariosDoPerfil(role.id)" :key="usuario.id"
                                class="inline-flex max-w-full items-center gap-1 rounded-full bg-white px-2 py-1 text-xs text-gray-700 shadow-sm">
                                <span class="truncate" :title="usuario.email">{{ usuario.name }}</span>
                                <button type="button" class="text-gray-400 hover:text-red-600" :title="`Remover ${usuario.name}`"
                                    @click="removerUsuario(usuario, role.id)">
                                    <X class="h-3.5 w-3.5" />
                                </button>
                            </li>
                        </ul>
                    </template>
                </div>
            </div>
            <p v-if="form.errors.roles" class="mt-2 text-sm text-red-600">{{ form.errors.roles }}</p>
            <p v-if="erroUsuarios" class="mt-2 text-sm text-red-600">{{ erroUsuarios }}</p>

            <UsuariosDialog v-model:open="modalAberto" :perfil="perfilModal" :selecionados="selecionadosModal"
                @confirm="confirmarUsuarios" />
        </section>

        <!-- Função e meta -->
        <section :class="card">
            <h2 :class="titulo">Função e meta</h2>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-6 xl:grid-cols-12">
                <div class="md:col-span-6 xl:col-span-6">
                    <label :class="label" for="funcao">Função <span class="text-red-600">*</span></label>
                    <select id="funcao" v-model="form.funcao" :class="input(form.errors.funcao)" required>
                        <option v-for="funcao in funcoes" :key="funcao.value" :value="funcao.value">{{ funcao.label }}</option>
                    </select>
                    <p v-if="form.errors.funcao" class="mt-1 text-sm text-red-600">{{ form.errors.funcao }}</p>
                </div>

                <template>
                    <div class="md:col-span-6 xl:col-span-6">
                        <span :class="label">Planos que contam <span class="text-red-600">*</span></span>
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
                    </div>

                    <div v-if="form.escopo_plano === 'plano'" class="md:col-span-6 xl:col-span-6">
                        <label :class="label" for="cod_plano">Plano <span class="text-red-600">*</span></label>
                        <select id="cod_plano" v-model="form.cod_plano" :class="input(form.errors.cod_plano)" required>
                            <option value="" disabled>Selecione um plano</option>
                            <option v-for="plano in planos" :key="plano.value" :value="plano.value">{{ plano.label }}</option>
                        </select>
                        <p v-if="form.errors.cod_plano" class="mt-1 text-sm text-red-600">{{ form.errors.cod_plano }}</p>
                    </div>
                </template>

                <div class="md:col-span-6 xl:col-span-6">
                    <span :class="label">Tipo de meta <span class="text-red-600">*</span></span>
                    <div class="grid grid-cols-2 gap-2">
                        <label v-for="tipo in tipos" :key="tipo.value" :class="opcao(form.tipo_meta === tipo.value)">
                            <input v-model="form.tipo_meta" type="radio" :value="tipo.value" class="text-cyan-600 focus:ring-cyan-500" />
                            {{ tipo.label }}
                        </label>
                    </div>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ form.tipo_meta === 'coletiva' ? 'A soma dos registros do grupo precisa bater a meta.' : 'Cada usuário precisa bater a meta sozinho.' }}
                    </p>
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="meta">
                        {{ form.funcao === 'comissao_venda_plano' ? 'Meta (vendas)' : 'Meta' }} <span class="text-red-600">*</span>
                    </label>
                    <input id="meta" v-model.number="form.meta" type="number" min="1" step="1" :class="input(form.errors.meta)" required />
                    <p v-if="form.errors.meta" class="mt-1 text-sm text-red-600">{{ form.errors.meta }}</p>
                </div>

                <div v-if="form.funcao === 'comissao_venda_plano'" class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="meta_valor">Meta em R$ (comissão) <span class="text-red-600">*</span></label>
                    <input id="meta_valor" v-model.number="form.meta_valor" type="number" min="0" step="0.01" placeholder="0,00"
                        :class="input(form.errors.meta_valor)" required />
                    <p v-if="form.errors.meta_valor" class="mt-1 text-sm text-red-600">{{ form.errors.meta_valor }}</p>
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="data_inicio">Início <span class="text-red-600">*</span></label>
                    <input id="data_inicio" v-model="form.data_inicio" type="date" :class="input(form.errors.data_inicio)" required />
                    <p v-if="form.errors.data_inicio" class="mt-1 text-sm text-red-600">{{ form.errors.data_inicio }}</p>
                </div>

                <div class="md:col-span-2 xl:col-span-2">
                    <label :class="label" for="prazo">Prazo <span class="text-red-600">*</span></label>
                    <input id="prazo" v-model="form.prazo" type="date" :min="form.data_inicio || undefined" :class="input(form.errors.prazo)" required />
                    <p v-if="form.errors.prazo" class="mt-1 text-sm text-red-600">{{ form.errors.prazo }}</p>
                </div>
            </div>

            <div class="mt-4 flex items-start gap-2 rounded-lg border border-cyan-200 bg-cyan-50 p-3 text-sm text-cyan-800">
                <Info class="mt-0.5 h-4 w-4 shrink-0" />
                <span>{{ resumo }}</span>
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
