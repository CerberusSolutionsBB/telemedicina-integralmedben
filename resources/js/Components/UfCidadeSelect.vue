<script setup>
import { computed, ref, watch } from "vue";
import { Label } from "@/Components/ui/label";
import SearchListModal from "@/Components/SearchListModal.vue";
import { ChevronDown } from "lucide-vue-next";
import { buscarEstado, carregarMunicipios, estados } from "@/Composables/useLocalidades";

const props = defineProps({
    disabled: { type: Boolean, default: false },
    errorUf: { type: String, default: null },
    errorCidade: { type: String, default: null },
});

// UF guardada como sigla (ex.: "SP") e cidade pelo nome, como em enderecos.*
const uf = defineModel("uf", { type: String, default: "" });
const cidade = defineModel("cidade", { type: String, default: "" });

const ufModalOpen = ref(false);
const cidadeModalOpen = ref(false);
const municipios = ref([]);
const carregandoMunicipios = ref(false);

const estadoAtual = computed(() => buscarEstado(uf.value));

const opcoesUf = estados.map((e) => ({ value: e.sigla, label: e.nome, hint: e.sigla }));
const opcoesCidade = computed(() => municipios.value.map((m) => ({ value: m.nome, label: m.nome })));

const buttonClass =
    "w-full h-10 flex items-center justify-between gap-2 px-3 text-sm border rounded-lg text-left bg-white focus:outline-none focus:ring-2 focus:ring-cyan-500 disabled:bg-gray-50 disabled:text-gray-400 disabled:cursor-not-allowed";

// Recarrega os municípios sempre que a UF muda (inclusive quando vem do ViaCEP).
watch(
    uf,
    async (sigla) => {
        municipios.value = [];
        if (!buscarEstado(sigla)) return;

        carregandoMunicipios.value = true;
        try {
            municipios.value = await carregarMunicipios(sigla);
        } finally {
            carregandoMunicipios.value = false;
        }
    },
    { immediate: true },
);

const selecionarUf = (item) => {
    if (item.value !== uf.value) cidade.value = "";
    uf.value = item.value;
};

const selecionarCidade = (item) => {
    cidade.value = item.value;
};
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <Label for="uf" class="mb-1 block text-sm font-medium text-gray-700">UF</Label>
            <button id="uf" type="button" :disabled="disabled"
                :class="[buttonClass, errorUf ? 'border-red-500 focus:ring-red-500' : 'border-gray-300']"
                @click="ufModalOpen = true">
                <span :class="estadoAtual ? 'text-gray-900' : 'text-gray-400'" class="truncate">
                    {{ estadoAtual ? `${estadoAtual.nome} (${estadoAtual.sigla})` : "Selecione o estado" }}
                </span>
                <ChevronDown class="w-4 h-4 text-gray-400 shrink-0" />
            </button>
            <p v-if="errorUf" class="mt-1 text-sm text-red-600">{{ errorUf }}</p>
        </div>

        <div>
            <Label for="cidade" class="mb-1 block text-sm font-medium text-gray-700">Cidade</Label>
            <button id="cidade" type="button" :disabled="disabled || !estadoAtual"
                :title="!estadoAtual ? 'Selecione a UF primeiro' : ''"
                :class="[buttonClass, errorCidade ? 'border-red-500 focus:ring-red-500' : 'border-gray-300']"
                @click="cidadeModalOpen = true">
                <span :class="cidade ? 'text-gray-900' : 'text-gray-400'" class="truncate">
                    {{ cidade || (estadoAtual ? "Selecione a cidade" : "Selecione a UF primeiro") }}
                </span>
                <ChevronDown class="w-4 h-4 text-gray-400 shrink-0" />
            </button>
            <p v-if="errorCidade" class="mt-1 text-sm text-red-600">{{ errorCidade }}</p>
        </div>

        <SearchListModal v-model:open="ufModalOpen" title="Selecionar estado" placeholder="Buscar estado ou sigla..."
            :items="opcoesUf" :selected="uf" @select="selecionarUf" />

        <SearchListModal v-model:open="cidadeModalOpen" title="Selecionar cidade"
            :description="estadoAtual ? `Municípios de ${estadoAtual.nome}` : ''" placeholder="Buscar cidade..."
            :items="opcoesCidade" :selected="cidade" :loading="carregandoMunicipios" @select="selecionarCidade" />
    </div>
</template>
