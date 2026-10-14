<script setup>
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { fmtMoeda } from "@/Composables/Cupom/situacao";

/**
 * Histórico de usos do cupom (snapshot dos valores de cada venda).
 */
defineProps({
    usos: { type: Array, default: () => [] },
});

const cabecalho = "text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider";
</script>

<template>
    <section class="w-full rounded-xl border border-gray-200 bg-white shadow-sm">
        <div class="p-4 sm:p-6">
            <h2 class="text-base font-semibold text-gray-900">Usos do cupom</h2>
            <p class="mt-1 text-sm text-gray-600">Vendas em que o cupom foi aplicado. Usos estornados não contam no limite.</p>
        </div>

        <p v-if="!usos.length" class="px-4 pb-6 text-sm text-gray-500 sm:px-6">Nenhum uso ainda.</p>

        <div v-else class="overflow-x-auto border-t border-gray-100">
            <Table>
                <TableHeader>
                    <TableRow class="bg-gray-50/80">
                        <TableHead :class="cabecalho">Data</TableHead>
                        <TableHead :class="cabecalho">Beneficiário</TableHead>
                        <TableHead :class="cabecalho">Plano</TableHead>
                        <TableHead :class="cabecalho">Valor</TableHead>
                        <TableHead :class="cabecalho">Desconto</TableHead>
                        <TableHead :class="cabecalho">Final</TableHead>
                        <TableHead :class="cabecalho">Por</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="uso in usos" :key="uso.id" :class="uso.estornado_em ? 'opacity-60' : ''">
                        <TableCell class="text-center text-xs text-gray-500 whitespace-nowrap">
                            {{ uso.data }}
                            <span v-if="uso.estornado_em" class="block text-red-600">Estornado em {{ uso.estornado_em }}</span>
                        </TableCell>
                        <TableCell class="text-center text-sm text-gray-800">
                            {{ uso.paciente }}<span class="block text-xs text-gray-500">{{ uso.cpf }}</span>
                        </TableCell>
                        <TableCell class="text-center text-sm text-gray-600">{{ uso.plano }}</TableCell>
                        <TableCell class="text-center text-sm text-gray-600 whitespace-nowrap">{{ fmtMoeda(uso.valor_original) }}</TableCell>
                        <TableCell class="text-center text-sm text-red-600 whitespace-nowrap">− {{ fmtMoeda(uso.desconto) }}</TableCell>
                        <TableCell class="text-center text-sm font-semibold text-gray-900 whitespace-nowrap">{{ fmtMoeda(uso.valor_final) }}</TableCell>
                        <TableCell class="text-center text-sm text-gray-600">{{ uso.usuario ?? '—' }}</TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>
    </section>
</template>
