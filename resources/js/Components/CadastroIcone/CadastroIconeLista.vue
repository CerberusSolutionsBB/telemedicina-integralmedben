<script setup>
import { Head, Link } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import TabelaCadastroIcone from "@/Components/CadastroIcone/TabelaCadastroIcone.vue";
import { rotaDe } from "@/Composables/CadastroIcone/modulos";
import { Plus } from "lucide-vue-next";

/** Tela de lista de um cadastro com ícone (Index). */
defineProps({
    modulo: { type: Object, required: true },
    itens: { type: Array, default: () => [] },
});
</script>

<template>
    <Head :title="modulo.titulo" />

    <TenantAdminLayout>
        <div class="space-y-4">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">{{ modulo.titulo }}</h1>
                    <p class="mt-1 text-sm text-gray-500">{{ modulo.descricaoLista }}</p>
                </div>
                <Link :href="route(rotaDe(modulo, 'create'))"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-cyan-600 px-4 text-sm font-semibold text-white hover:bg-cyan-700">
                    <Plus class="h-4 w-4" aria-hidden="true" /> {{ modulo.novo }}
                </Link>
            </div>

            <TabelaCadastroIcone :modulo="modulo" :itens="itens">
                <template v-if="$slots.resumo" #resumo="escopo"><slot name="resumo" v-bind="escopo" /></template>
            </TabelaCadastroIcone>
        </div>
    </TenantAdminLayout>
</template>
