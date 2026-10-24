<script setup>
import { fmtMoeda } from "@/Composables/Cupom/situacao";
import { Ticket, UserRound } from "lucide-vue-next";

/**
 * Histórico de usos de cupom de benefício no processo: quem usou, quando, o cupom
 * e os valores (mais recente primeiro). Registrado pela aba Processos do Relatório.
 */
defineProps({
    // [{ id, codigo, cupom_titulo, desconto_texto, valor_original, desconto, valor_final, usuario, data }]
    usos: { type: Array, default: () => [] },
});
</script>

<template>
    <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-gray-900">Usos de cupom</h2>
        <p class="mt-1 text-sm text-gray-600">Quem usou cupom de desconto neste processo, do mais recente para o mais antigo.</p>

        <p v-if="!usos.length" class="mt-4 rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-600">
            Nenhum cupom usado neste processo ainda.
        </p>

        <ul v-else class="mt-4 divide-y divide-gray-100">
            <li v-for="uso in usos" :key="uso.id" class="flex flex-col gap-2 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                        <span class="inline-flex items-center gap-1 rounded-full border border-gray-300 px-2.5 py-0.5 font-mono text-xs font-bold tracking-wider text-gray-900">
                            <Ticket class="h-3 w-3" aria-hidden="true" /> {{ uso.codigo }}
                        </span>
                        <span v-if="uso.cupom_titulo" class="text-gray-700">{{ uso.cupom_titulo }}</span>
                        <span class="text-gray-500">· {{ uso.desconto_texto }}</span>
                    </p>
                    <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-600">
                        <UserRound class="h-4 w-4 shrink-0" aria-hidden="true" />
                        <span><strong class="font-medium text-gray-800">{{ uso.usuario ?? 'Usuário removido' }}</strong> em {{ uso.data }}</span>
                    </p>
                </div>
                <p class="shrink-0 text-sm tabular-nums text-gray-700 sm:text-right">
                    <span class="line-through">{{ fmtMoeda(uso.valor_original) }}</span>
                    → <strong class="text-base text-gray-900">{{ fmtMoeda(uso.valor_final) }}</strong>
                    <span class="block text-xs text-green-700 dark:text-green-400">economia de {{ fmtMoeda(uso.desconto) }}</span>
                </p>
            </li>
        </ul>
    </section>
</template>
