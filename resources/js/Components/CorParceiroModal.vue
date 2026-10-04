<script setup>
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { Check, Loader2, Palette, RotateCcw } from 'lucide-vue-next';
import Modal from '@/Components/Modal.vue';
import { Button } from '@/Components/ui/button';
import { showToast } from '@/Utils/toast';

const props = defineProps({
    show: { type: Boolean, default: false },
    // { id, indicativo_cor, nome }
    tenant: { type: Object, default: null },
});

const emit = defineEmits(['close']);

// Cores sugeridas: contrastam bem entre si nos gráficos do dashboard.
const SUGESTOES = [
    '#22B8CF', '#E11D2E', '#1E293B', '#F59E0B', '#10B981', '#8B5CF6',
    '#EC4899', '#3B82F6', '#F97316', '#14B8A6', '#84CC16', '#6366F1',
];
const HEX = /^#[0-9A-Fa-f]{6}$/;

const cor = ref(null);
const hex = ref('');
const salvando = ref(false);

watch(() => props.show, (aberto) => {
    if (!aberto) return;
    cor.value = props.tenant?.indicativo_cor || null;
    hex.value = cor.value || '';
});

const escolher = (valor) => {
    cor.value = valor.toUpperCase();
    hex.value = cor.value;
};

const digitarHex = () => {
    if (hex.value.trim() === '') {
        cor.value = null;
        return;
    }
    const valor = hex.value.startsWith('#') ? hex.value : `#${hex.value}`;
    if (HEX.test(valor)) cor.value = valor.toUpperCase();
};

const hexInvalido = computed(() => hex.value !== '' && !HEX.test(hex.value.startsWith('#') ? hex.value : `#${hex.value}`));
const alterado = computed(() => (cor.value || null) !== (props.tenant?.indicativo_cor || null));

const salvar = (valor) => {
    if (!props.tenant?.id) return;
    salvando.value = true;

    router.put(route('pagina.cor', props.tenant.id), { indicativo_cor: valor }, {
        preserveScroll: true,
        // Sucesso é exibido pelo flash do layout.
        onSuccess: () => emit('close'),
        onError: (errors) => showToast(errors.indicativo_cor || 'Erro ao salvar a cor.', 'error'),
        onFinish: () => { salvando.value = false; },
    });
};
</script>

<template>
    <Modal :show="show" max-width="md" @close="emit('close')">
        <div class="p-6">
            <div class="flex items-center gap-3">
                <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-cyan-50 text-cyan-600">
                    <Palette class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <h2 class="text-lg font-semibold text-gray-900">Cor do parceiro</h2>
                    <p class="truncate text-sm text-gray-500">{{ tenant?.nome || tenant?.id }}</p>
                </div>
            </div>

            <p class="mt-4 text-sm text-gray-600">
                Selecione uma cor para identificar este parceiro nos gráficos do dashboard.
                Sem cor escolhida, ele usa a cor padrão do sistema.
            </p>

            <div class="mt-5 grid grid-cols-6 gap-3">
                <button v-for="sugestao in SUGESTOES" :key="sugestao" type="button"
                    :aria-label="`Usar a cor ${sugestao}`" :aria-pressed="cor === sugestao"
                    class="grid aspect-square place-items-center rounded-xl ring-offset-2 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500"
                    :class="cor === sugestao ? 'ring-2 ring-gray-900' : 'hover:scale-105'"
                    :style="{ background: sugestao }" @click="escolher(sugestao)">
                    <Check v-if="cor === sugestao" class="h-4 w-4 text-white drop-shadow" />
                </button>
            </div>

            <div class="mt-5 flex items-center gap-3">
                <label class="relative h-10 w-10 shrink-0 cursor-pointer overflow-hidden rounded-xl border border-gray-300"
                    title="Escolher outra cor" :style="{ background: cor || '#FFFFFF' }">
                    <input type="color" :value="cor || '#22B8CF'" class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                        aria-label="Escolher outra cor" @input="escolher($event.target.value)" />
                </label>
                <div class="flex-1">
                    <input v-model="hex" type="text" maxlength="7" placeholder="Cor padrão" aria-label="Código da cor"
                        class="w-full rounded-lg border px-3 py-2 font-mono text-sm uppercase focus:outline-none focus:ring-2 focus:ring-cyan-500"
                        :class="hexInvalido ? 'border-red-300 bg-red-50' : 'border-gray-300'" @input="digitarHex" />
                    <p v-if="hexInvalido" class="mt-1 text-xs text-red-600">Use o formato #RRGGBB.</p>
                </div>
            </div>

            <div class="mt-5 flex items-center gap-3 rounded-xl bg-gray-50 px-4 py-3 text-sm">
                <span class="h-3 w-3 shrink-0 rounded-sm" :class="cor ? '' : 'bg-gradient-to-br from-cyan-400 to-red-500'"
                    :style="cor ? { background: cor } : {}" />
                <span class="text-gray-700">
                    {{ cor ? `Cor escolhida: ${cor}` : 'Usando a cor padrão do sistema' }}
                </span>
            </div>

            <div class="mt-6 flex flex-col-reverse gap-2 sm:flex-row sm:items-center sm:justify-between">
                <Button v-if="tenant?.indicativo_cor" type="button" variant="ghost" :disabled="salvando"
                    class="gap-2 text-gray-600" @click="salvar(null)">
                    <RotateCcw class="h-4 w-4" />
                    Usar cor padrão
                </Button>
                <span v-else />
                <div class="flex flex-col-reverse gap-2 sm:flex-row">
                    <Button type="button" variant="outline" :disabled="salvando" @click="emit('close')">Cancelar</Button>
                    <Button type="button" class="gap-2 bg-cyan-500 hover:bg-cyan-600"
                        :disabled="salvando || !alterado || hexInvalido" @click="salvar(cor)">
                        <Loader2 v-if="salvando" class="h-4 w-4 animate-spin" />
                        Salvar cor
                    </Button>
                </div>
            </div>
        </div>
    </Modal>
</template>
