<script setup>
import { Head, Link } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import TableCupons from "@/Components/Cupom/TableCupons.vue";
import { Plus } from "lucide-vue-next";

defineProps({
    cupons: { type: Array, default: () => [] },
    teto: { type: Number, default: null },
    opcoes: { type: Object, required: true },
});
</script>

<template>
    <Head title="Cupons" />

    <TenantAdminLayout>
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Cupons</h1>
                    <p class="mt-1 text-sm text-gray-500">Cupons de desconto deste parceiro. Desconto máximo permitido: {{ teto }}%.</p>
                </div>
                <Link :href="route('cupons.create')"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700">
                    <Plus class="h-4 w-4" aria-hidden="true" /> Novo cupom
                </Link>
            </div>

            <TableCupons :cupons="cupons" :rotas="{ show: 'cupons.show', edit: 'cupons.edit', status: 'cupons.status', pdf: 'cupons.pdf', destroy: 'cupons.destroy' }"  />
        </div>
    </TenantAdminLayout>
</template>
