<script setup>
import { Head, Link } from "@inertiajs/vue3";
import CentralAdminLayout from "@/Layouts/CentralAdminLayout.vue";
import TableCupons from "@/Components/Cupom/TableCupons.vue";
import { Plus } from "lucide-vue-next";

defineProps({
    cupons: { type: Array, default: () => [] },
    parceiros: { type: Array, default: () => [] },
    opcoes: { type: Object, required: true },
});
</script>

<template>
    <Head title="Cupons" />

    <CentralAdminLayout>
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Cupons</h1>
                    <p class="mt-1 text-sm text-gray-500">Cupons globais (todos os parceiros) e de cada parceiro.</p>
                </div>
                <Link :href="route('financeiro.cupons.create')"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700">
                    <Plus class="h-4 w-4" aria-hidden="true" /> Novo cupom
                </Link>
            </div>

            <TableCupons :cupons="cupons" :rotas="{ show: 'financeiro.cupons.show', edit: 'financeiro.cupons.edit', status: 'financeiro.cupons.status', pdf: 'financeiro.cupons.pdf', destroy: 'financeiro.cupons.destroy' }" mostrar-parceiro />
        </div>
    </CentralAdminLayout>
</template>
