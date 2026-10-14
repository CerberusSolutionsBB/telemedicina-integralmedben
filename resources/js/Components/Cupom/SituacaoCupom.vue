<script setup>
import { computed } from "vue";
import { DropdownMenuContent, DropdownMenuItem, DropdownMenuPortal, DropdownMenuRoot, DropdownMenuTrigger } from "reka-ui";
import { SITUACOES } from "@/Composables/Cupom/situacao";
import { Ban, ChevronDown, Pause, Play } from "lucide-vue-next";

/**
 * Situação do cupom com botão para trocá-la (menu). Cancelado é final: sem menu.
 * Emite `pedir(tipo)`; quem usa abre a confirmação (useAcoesCupom).
 */
const props = defineProps({
    cupom: { type: Object, required: true },
});

const emit = defineEmits(["pedir"]);

const OPCOES = {
    pausar: { rotulo: "Pausar", icone: Pause, classe: "text-amber-700" },
    reativar: { rotulo: "Reativar", icone: Play, classe: "text-cyan-700" },
    cancelar: { rotulo: "Cancelar", icone: Ban, classe: "text-red-600" },
};

// Trocas possíveis a partir do status gravado (expirado/esgotado/agendado são calculados sobre o "ativo").
const trocas = computed(() => ({
    ativo: ["pausar", "cancelar"],
    pausado: ["reativar", "cancelar"],
}[props.cupom.status] ?? []));

const situacao = computed(() => SITUACOES[props.cupom.situacao] ?? { label: props.cupom.situacao, classe: "" });
const etiqueta = "inline-flex items-center gap-1 rounded-full border px-2.5 py-1 text-xs font-medium whitespace-nowrap";
</script>

<template>
    <span v-if="!trocas.length" :class="[etiqueta, situacao.classe]">{{ situacao.label }}</span>

    <DropdownMenuRoot v-else>
        <DropdownMenuTrigger :class="[etiqueta, situacao.classe, 'cursor-pointer hover:brightness-95 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500']"
            :aria-label="`Situação: ${situacao.label}. Trocar situação`" title="Trocar situação">
            {{ situacao.label }}
            <ChevronDown class="h-3.5 w-3.5" aria-hidden="true" />
        </DropdownMenuTrigger>

        <DropdownMenuPortal>
            <DropdownMenuContent :side-offset="6" align="center"
                class="z-50 min-w-[10rem] rounded-lg border border-gray-200 bg-white p-1 shadow-lg">
                <p class="px-2 py-1.5 text-xs font-semibold uppercase tracking-wider text-gray-500">Trocar situação</p>
                <DropdownMenuItem v-for="tipo in trocas" :key="tipo" @select="emit('pedir', tipo)"
                    class="flex cursor-pointer items-center gap-2 rounded-md px-2 py-2 text-sm outline-none data-[highlighted]:bg-gray-100"
                    :class="OPCOES[tipo].classe">
                    <component :is="OPCOES[tipo].icone" class="h-4 w-4" aria-hidden="true" />
                    {{ OPCOES[tipo].rotulo }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenuPortal>
    </DropdownMenuRoot>
</template>
