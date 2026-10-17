<script setup>
import { computed, ref } from "vue";
import { History } from "lucide-vue-next";

/**
 * Histórico de alterações de um cadastro (laravel-auditing): quem, quando e o que mudou.
 */
const props = defineProps({
    historico: { type: Array, default: () => [] },
});

// Os mais recentes primeiro; o restante só quando a pessoa pede (menos texto de uma vez).
const INICIAL = 5;
const verTodos = ref(false);
const visiveis = computed(() => (verTodos.value ? props.historico : props.historico.slice(0, INICIAL)));

const COR = {
    created: "bg-green-100 text-green-800 dark:bg-green-950/50 dark:text-green-300",
    updated: "bg-cyan-100 text-cyan-800 dark:bg-cyan-950/50 dark:text-cyan-300",
    deleted: "bg-red-100 text-red-800 dark:bg-red-950/50 dark:text-red-300",
};
</script>

<template>
    <section class="w-full rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <h2 class="flex items-center gap-2 text-base font-semibold text-gray-900">
            <History class="h-5 w-5 text-cyan-600" aria-hidden="true" /> Histórico de alterações
        </h2>
        <p class="mt-1 text-sm text-gray-600">Quem mudou o quê e quando. Registrado automaticamente.</p>

        <p v-if="!historico.length" class="mt-4 text-sm text-gray-500">Nenhum registro ainda.</p>

        <ol v-else class="mt-4 space-y-4">
            <li v-for="item in visiveis" :key="item.id" class="rounded-lg border border-gray-200 p-3 sm:p-4">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="COR[item.evento_key] ?? 'bg-gray-100 text-gray-700'">
                        {{ item.evento }}
                    </span>
                    <span class="font-medium text-gray-900">{{ item.usuario ?? 'Sistema' }}</span>
                    <span class="text-gray-600">· {{ item.data }}</span>
                </div>

                <dl v-if="item.alteracoes.length" class="mt-2 space-y-1 text-sm">
                    <div v-for="alteracao in item.alteracoes" :key="alteracao.campo" class="grid grid-cols-1 gap-1 sm:grid-cols-[8rem_1fr]">
                        <dt class="text-gray-500">{{ alteracao.campo }}</dt>
                        <dd class="break-words text-gray-800">
                            <template v-if="item.evento_key === 'updated'">
                                <span class="text-gray-600 line-through">{{ alteracao.antes ?? '—' }}</span>
                                <span class="mx-1 text-gray-600" aria-label="mudou para">→</span>
                            </template>
                            <span>{{ (item.evento_key === 'deleted' ? alteracao.antes : alteracao.depois) ?? '—' }}</span>
                        </dd>
                    </div>
                </dl>
            </li>
        </ol>

        <button v-if="historico.length > INICIAL" type="button" @click="verTodos = !verTodos"
            class="mt-4 inline-flex min-h-11 items-center rounded-lg border border-gray-300 bg-white px-4 text-sm font-medium text-gray-700 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500">
            {{ verTodos ? 'Mostrar só os mais recentes' : `Ver todo o histórico (${historico.length} registros)` }}
        </button>
    </section>
</template>
