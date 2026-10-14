import { onBeforeUnmount } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { showToast } from '@/Utils/toast';

// O layout é montado de novo a cada página: a mesma mensagem pode chegar pela
// montagem e pelo evento da visita. Repetição em menos de 1,5 s é ignorada.
const JANELA_DUPLICADA = 1500;
let ultimo = { chave: null, em: 0 };

const exibir = (flash) => {
    [['success', flash?.success], ['error', flash?.error]]
        .filter(([, mensagem]) => mensagem)
        .forEach(([tipo, mensagem]) => {
            const chave = `${tipo}:${mensagem}`;
            const agora = Date.now();

            if (chave === ultimo.chave && agora - ultimo.em < JANELA_DUPLICADA) {
                return;
            }

            ultimo = { chave, em: agora };
            showToast(mensagem, tipo);
        });
};

/**
 * Mensagens flash (with('success') / with('error')) exibidas como toast pelo layout.
 * Lidas a cada visita concluída, então a mesma mensagem repetida depois (ex.: dois
 * reenvios de SMS seguidos) aparece de novo.
 */
export function useFlashToast() {
    exibir(usePage().props.flash);

    const remover = router.on('success', (evento) => exibir(evento.detail.page.props.flash));
    onBeforeUnmount(remover);
}
