<script setup>
import { computed, ref, watch } from "vue";
import IconeLucide from "@/Components/Icone/IconeLucide.vue";
import { carregarIconesLucide, iconesLucide } from "@/Composables/Icone/useIconesLucide";
import { Loader2, Search, X } from "lucide-vue-next";

/**
 * Seletor de ícone com busca entre todos os ícones do lucide (~1.700).
 * Para não sobrecarregar a tela, começa só com os sugeridos (saúde, rótulo em
 * português); digitar busca em todos, e "Ver todos" abre a lista completa em lotes.
 * A busca aceita o nome em inglês ("heart") ou o rótulo ("coração").
 */
const props = defineProps({
    modelValue: { type: String, default: "" },
    // [{ value, label }] sugeridos, com rótulo em português
    sugeridos: { type: Array, default: () => [] },
    error: { type: String, default: "" },
});

const emit = defineEmits(["update:modelValue"]);

const LOTE = 120;

const busca = ref("");
const visiveis = ref(LOTE);
const mostrarTodos = ref(false);

// O pacote completo só é baixado quando a pessoa busca ou pede "Ver todos".
watch(busca, (termo) => termo && carregarIconesLucide());

const normalizar = (texto) => String(texto ?? "").normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().replace(/[\s_-]+/g, "");

// "HeartPulse" -> "Heart Pulse"
const separar = (nome) => nome.replace(/([a-z0-9])([A-Z])/g, "$1 $2");

const rotulos = computed(() => Object.fromEntries(props.sugeridos.map((s) => [s.value, s.label])));

const todos = computed(() => {
    const nomes = Object.keys(iconesLucide.value ?? {});
    const sugeridos = props.sugeridos.map((s) => s.value);
    const demais = nomes.filter((nome) => !rotulos.value[nome]);

    return [...sugeridos, ...demais].map((nome) => ({
        value: nome,
        label: rotulos.value[nome] ?? separar(nome),
        chave: normalizar(nome) + " " + normalizar(rotulos.value[nome]),
    }));
});

const resultados = computed(() => {
    const termo = normalizar(busca.value);
    return termo ? todos.value.filter((icone) => icone.chave.includes(termo)) : todos.value;
});

// Sem busca e sem "ver todos": só os sugeridos (+ o escolhido, se não for sugerido).
const modoSugeridos = computed(() => !busca.value && !mostrarTodos.value);
const listaSugeridos = computed(() => {
    const extra = props.modelValue && !rotulos.value[props.modelValue]
        ? [{ value: props.modelValue, label: separar(props.modelValue) }]
        : [];
    return [...extra, ...props.sugeridos];
});

const exibidos = computed(() => (modoSugeridos.value || !iconesLucide.value ? listaSugeridos.value : resultados.value.slice(0, visiveis.value)));

// Nova busca recomeça do primeiro lote.
watch(busca, () => (visiveis.value = LOTE));

const selecionadoLabel = computed(() => (props.modelValue ? (rotulos.value[props.modelValue] ?? separar(props.modelValue)) : null));
</script>

<template>
    <div>
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" aria-hidden="true" />
                <input id="icone" v-model="busca" type="search" placeholder="Procurar ícone (ex.: coração, heart, calendar)..."
                    aria-label="Buscar ícone" aria-describedby="icone-erro"
                    class="w-full min-h-11 rounded-lg border bg-white pl-9 pr-9 text-base placeholder:text-gray-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 sm:text-sm" :class="error ? 'border-red-500' : 'border-gray-300'" />
                <button v-if="busca" type="button" aria-label="Limpar busca" @click="busca = ''"
                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:text-gray-600">
                    <X class="h-4 w-4" />
                </button>
            </div>

            <div v-if="modelValue" class="flex shrink-0 items-center gap-2 rounded-lg border border-cyan-200 bg-cyan-50 px-3 py-2 text-sm text-cyan-800">
                <IconeLucide :nome="modelValue" class="h-5 w-5" />
                <span>Selecionado: <strong>{{ selecionadoLabel }}</strong></span>
            </div>
        </div>

        <p class="mt-2 flex items-center gap-2 text-sm text-gray-600" aria-live="polite">
            <template v-if="busca && !iconesLucide">
                <Loader2 class="h-4 w-4 animate-spin motion-reduce:animate-none" aria-hidden="true" /> Carregando todos os ícones...
            </template>
            <template v-else-if="modoSugeridos">Sugestões para a área da saúde. Digite acima para procurar entre todos.</template>
            <template v-else>
                {{ resultados.length.toLocaleString('pt-BR') }} ícone(s){{ busca ? ' encontrado(s)' : '' }}
                <template v-if="resultados.length > exibidos.length"> · mostrando {{ exibidos.length }}</template>
            </template>
        </p>

        <p v-if="busca && iconesLucide && !resultados.length" class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-600">
            Nenhum ícone para "{{ busca }}". Tente em inglês (ex.: "heart", "user", "calendar").
        </p>

        <div v-else class="mt-3 grid max-h-[28rem] grid-cols-3 gap-2 overflow-y-auto p-0.5 sm:grid-cols-6 xl:grid-cols-8"
            role="radiogroup" aria-label="Ícones">
            <button v-for="icone in exibidos" :key="icone.value" type="button" role="radio"
                :aria-checked="modelValue === icone.value" :title="icone.value" @click="emit('update:modelValue', icone.value)"
                class="flex min-h-[4.5rem] flex-col items-center justify-center gap-1 rounded-lg border p-2 text-xs transition-colors duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500 motion-reduce:transition-none"
                :class="modelValue === icone.value ? 'border-cyan-500 bg-cyan-50 font-semibold text-cyan-800 ring-1 ring-cyan-500' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                <IconeLucide :nome="icone.value" class="h-6 w-6" />
                <span class="line-clamp-1 w-full text-center">{{ icone.label }}</span>
            </button>
        </div>

        <div class="mt-3 flex flex-wrap justify-center gap-2">
            <button v-if="modoSugeridos" type="button" @click="mostrarTodos = true; carregarIconesLucide()"
                class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                Ver todos os ícones{{ iconesLucide ? ` (${Object.keys(iconesLucide).length.toLocaleString('pt-BR')})` : '' }}
            </button>
            <template v-else-if="iconesLucide">
                <button v-if="resultados.length > exibidos.length" type="button" @click="visiveis += LOTE"
                    class="inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                    Mostrar mais {{ Math.min(LOTE, resultados.length - exibidos.length) }}
                </button>
                <button v-if="!busca" type="button" @click="mostrarTodos = false"
                    class="inline-flex min-h-11 items-center rounded-lg px-4 text-sm font-medium text-cyan-700 hover:bg-cyan-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
                    Voltar às sugestões
                </button>
            </template>
        </div>

        <p v-if="error" id="icone-erro" class="mt-2 text-sm text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>
