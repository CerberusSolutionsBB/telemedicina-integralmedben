<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import { AlertTriangle, Briefcase, CheckCircle2, CircleOff, Handshake, LayoutGrid, Loader2, TicketPercent } from "lucide-vue-next";

/**
 * Módulos opcionais do painel do parceiro (aba Configuração da página do parceiro).
 * Cada chave salva na hora; desligado, o módulo some do menu do parceiro.
 */
const props = defineProps({
    tenantId: { type: [String, Number], required: true },
    // [{ key, label, descricao, habilitado }]
    modulos: { type: Array, default: () => [] },
});

const ICONES = { servicos: Briefcase, convenios: Handshake, cupons_beneficio: TicketPercent };

const salvando = ref(null);

const alternar = (modulo, habilitado) => {
    salvando.value = modulo.key;
    router.put(route("pagina.configuracao.modulos", [props.tenantId, modulo.key]), { habilitado }, {
        preserveScroll: true,
        preserveState: true,
        onFinish: () => (salvando.value = null),
    });
};

const estado = (key) => props.modulos.find((m) => m.key === key)?.habilitado ?? false;

// Cupom de benefício precisa de um convênio ou serviço para apontar.
const cuponsSemBase = computed(() => estado("cupons_beneficio") && !estado("convenios") && !estado("servicos"));
</script>

<template>
    <section class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm sm:p-6">
        <div class="flex items-start gap-3">
            <div class="rounded-lg bg-cyan-50 p-3">
                <LayoutGrid class="h-6 w-6 text-cyan-700" aria-hidden="true" />
            </div>
            <div>
                <h3 class="text-base font-semibold text-gray-900">Módulos do parceiro</h3>
                <p class="mt-0.5 text-sm text-gray-600">Escolha o que aparece no painel deste parceiro. Desligado, o módulo some do menu dele.</p>
            </div>
        </div>

        <ul class="mt-5 divide-y divide-gray-100 rounded-lg border border-gray-200">
            <li v-for="modulo in modulos" :key="modulo.key" class="flex items-start gap-4 p-4">
                <component :is="ICONES[modulo.key] ?? LayoutGrid" class="mt-0.5 h-5 w-5 shrink-0 text-gray-600" aria-hidden="true" />
                <div class="min-w-0 flex-1">
                    <p :id="`modulo-${modulo.key}`" class="text-sm font-semibold text-gray-900">{{ modulo.label }}</p>
                    <p class="mt-0.5 text-sm text-gray-600">{{ modulo.descricao }}</p>
                    <p class="mt-2 inline-flex items-center gap-1 text-xs font-semibold"
                        :class="modulo.habilitado ? 'text-green-700 dark:text-green-400' : 'text-gray-600'">
                        <component :is="modulo.habilitado ? CheckCircle2 : CircleOff" class="h-3.5 w-3.5" aria-hidden="true" />
                        {{ modulo.habilitado ? 'Habilitado: aparece no painel do parceiro' : 'Desabilitado: não aparece para o parceiro' }}
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2 pt-0.5">
                    <Loader2 v-if="salvando === modulo.key" class="h-4 w-4 animate-spin text-gray-500 motion-reduce:animate-none" aria-hidden="true" />
                    <AppSwitch :model-value="modulo.habilitado" :sr-text="`${modulo.habilitado ? 'Desabilitar' : 'Habilitar'} ${modulo.label}`"
                        :class="salvando ? 'pointer-events-none opacity-60' : ''" @update:model-value="(valor) => alternar(modulo, valor)" />
                </div>
            </li>
        </ul>

        <p v-if="cuponsSemBase" role="status"
            class="mt-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-300">
            <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
            Cupons de benefício está ligado, mas Convênios e Serviços estão desligados. O parceiro não vai conseguir criar cupons novos.
        </p>
    </section>
</template>
