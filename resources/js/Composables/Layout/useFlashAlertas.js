import { onBeforeUnmount, ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const DURACAO = 6000;

/**
 * Mensagens flash (with('success') / with('error')) exibidas pelo layout.
 * Lidas a cada visita concluída, então a mesma mensagem repetida (ex.: dois
 * reenvios de SMS seguidos) aparece de novo. Somem sozinhas após alguns segundos.
 */
export function useFlashAlertas() {
    const page = usePage();
    const alertas = ref([]);
    let sequencia = 0;

    const fechar = (id) => {
        alertas.value = alertas.value.filter((a) => a.id !== id);
    };

    const exibir = (flash) => {
        [['success', flash?.success], ['error', flash?.error]]
            .filter(([, mensagem]) => mensagem)
            .forEach(([tipo, mensagem]) => {
                const id = ++sequencia;
                alertas.value = [...alertas.value.filter((a) => a.mensagem !== mensagem), { id, tipo, mensagem }];
                setTimeout(() => fechar(id), DURACAO);
            });
    };

    exibir(page.props.flash);

    const remover = router.on('success', (evento) => exibir(evento.detail.page.props.flash));
    onBeforeUnmount(remover);

    return { alertas, fechar };
}
