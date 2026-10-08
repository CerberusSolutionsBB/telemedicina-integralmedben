<script setup>
import { computed, ref, watch } from "vue";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import { Check, Loader2, Search, Square, SquareCheck, Trash2, X } from "lucide-vue-next";

/**
 * Modal de seleção múltipla dos usuários de um perfil, com busca no servidor.
 */
const props = defineProps({
    perfil: { type: Object, default: null },
    selecionados: { type: Array, default: () => [] },
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["confirm"]);

const termo = ref("");
const carregando = ref(false);
const erro = ref("");
const usuarios = ref([]);
const total = ref(0);
const escolhidos = ref([]);
let debounce = null;
let requisicao = 0;

const estaEscolhido = (id) => escolhidos.value.some((usuario) => usuario.id === id);

const alternar = (usuario) => {
    escolhidos.value = estaEscolhido(usuario.id)
        ? escolhidos.value.filter((item) => item.id !== usuario.id)
        : [...escolhidos.value, { id: usuario.id, name: usuario.name, email: usuario.email }];
};

const todosVisiveisEscolhidos = computed(
    () => usuarios.value.length > 0 && usuarios.value.every((usuario) => estaEscolhido(usuario.id)),
);

const alternarTodosVisiveis = () => {
    if (todosVisiveisEscolhidos.value) {
        const visiveis = new Set(usuarios.value.map((usuario) => usuario.id));
        escolhidos.value = escolhidos.value.filter((usuario) => !visiveis.has(usuario.id));
        return;
    }

    const porId = new Map(escolhidos.value.map((usuario) => [usuario.id, usuario]));
    usuarios.value.forEach((usuario) => porId.set(usuario.id, { id: usuario.id, name: usuario.name, email: usuario.email }));
    escolhidos.value = [...porId.values()];
};

const buscar = async () => {
    if (!props.perfil) return;

    const atual = ++requisicao;
    carregando.value = true;
    erro.value = "";

    try {
        const { data } = await window.axios.get(route("desempenho.usuarios"), {
            params: { role_id: props.perfil.id, q: termo.value },
        });

        if (atual !== requisicao) return;

        usuarios.value = data.usuarios;
        total.value = data.total;
    } catch {
        if (atual === requisicao) {
            usuarios.value = [];
            total.value = 0;
            erro.value = "Não foi possível carregar os usuários deste perfil.";
        }
    } finally {
        if (atual === requisicao) carregando.value = false;
    }
};

watch(open, (aberto) => {
    if (!aberto) return;

    escolhidos.value = [...props.selecionados];
    erro.value = "";

    if (termo.value === "") buscar();
    else termo.value = "";
});

watch(termo, () => {
    if (!open.value) return;

    clearTimeout(debounce);
    debounce = setTimeout(buscar, 300);
});

const confirmar = () => {
    emit("confirm", escolhidos.value);
    open.value = false;
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-[560px] p-0 gap-0 overflow-hidden">
            <DialogHeader class="p-5 pb-3">
                <DialogTitle>Selecionar usuários</DialogTitle>
                <DialogDescription>
                    Escolha os usuários{{ perfil ? ` do perfil ${perfil.name}` : "" }} que participam da meta.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 px-5 pb-3">
                <div class="relative">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <input v-model="termo" type="text" placeholder="Buscar por nome ou e-mail..." autocomplete="off"
                        class="w-full rounded-lg border border-gray-300 py-2 pl-9 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500" />
                    <button v-if="termo" type="button" title="Limpar busca"
                        class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-gray-400 hover:text-gray-600"
                        @click="termo = ''">
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <button type="button" :disabled="!usuarios.length" @click="alternarTodosVisiveis"
                            class="inline-flex items-center gap-1.5 text-sm font-medium text-cyan-700 hover:text-cyan-800 disabled:cursor-not-allowed disabled:opacity-40">
                            <SquareCheck v-if="todosVisiveisEscolhidos" class="h-4 w-4" />
                            <Square v-else class="h-4 w-4" />
                            {{ todosVisiveisEscolhidos ? "Desmarcar todos" : "Selecionar todos" }}
                        </button>
                        <template v-if="escolhidos.length">
                            <span class="text-gray-300">|</span>
                            <button type="button" @click="escolhidos = []"
                                class="inline-flex items-center gap-1.5 text-sm font-medium text-red-500 hover:text-red-600">
                                <Trash2 class="h-4 w-4" />
                                Limpar
                            </button>
                        </template>
                    </div>
                    <span class="text-sm text-gray-500">{{ escolhidos.length }} selecionado{{ escolhidos.length === 1 ? "" : "s" }}</span>
                </div>
            </div>

            <div class="max-h-80 overflow-y-auto border-t border-gray-100">
                <div v-if="carregando" class="flex items-center justify-center gap-2 py-10 text-sm text-gray-500">
                    <Loader2 class="h-4 w-4 animate-spin" />
                    Carregando...
                </div>

                <p v-else-if="erro" class="py-10 text-center text-sm text-red-600">{{ erro }}</p>

                <p v-else-if="!usuarios.length" class="py-10 text-center text-sm text-gray-500">
                    {{ termo ? "Nenhum usuário encontrado para esta busca." : "Nenhum usuário neste perfil." }}
                </p>

                <div v-else class="py-1">
                    <button v-for="usuario in usuarios" :key="usuario.id" type="button" @click="alternar(usuario)"
                        class="flex w-full items-center gap-3 px-5 py-2.5 text-left text-sm transition-colors"
                        :class="estaEscolhido(usuario.id) ? 'bg-cyan-50/60' : 'hover:bg-gray-50'">
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded border-2 transition-colors"
                            :class="estaEscolhido(usuario.id) ? 'border-cyan-500 bg-cyan-500' : 'border-gray-300 bg-white'">
                            <Check v-if="estaEscolhido(usuario.id)" class="h-3.5 w-3.5 text-white" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium" :class="estaEscolhido(usuario.id) ? 'text-cyan-900' : 'text-gray-800'">
                                {{ usuario.name }}
                            </span>
                            <span v-if="usuario.email" class="block truncate text-xs text-gray-500">{{ usuario.email }}</span>
                        </span>
                    </button>
                </div>
            </div>

            <div class="flex items-center justify-between gap-3 border-t border-gray-100 bg-gray-50/50 p-5">
                <p class="text-xs text-gray-500">
                    <template v-if="!carregando && total > usuarios.length">
                        Mostrando {{ usuarios.length }} de {{ total }} — refine a busca para ver os demais.
                    </template>
                    <template v-else-if="!carregando && perfil">
                        {{ total }} usuário{{ total === 1 ? "" : "s" }} no perfil {{ perfil.name }}.
                    </template>
                </p>
                <div class="flex shrink-0 gap-2">
                    <button type="button" @click="open = false"
                        class="h-10 rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="button" @click="confirmar"
                        class="inline-flex h-10 items-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white shadow-sm hover:bg-cyan-700">
                        <Check class="h-4 w-4" />
                        Confirmar
                        <span v-if="escolhidos.length" class="rounded-full bg-cyan-500 px-1.5 text-xs">{{ escolhidos.length }}</span>
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
