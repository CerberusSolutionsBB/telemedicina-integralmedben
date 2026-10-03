import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { onKeyStroke } from '@vueuse/core';

/**
 * Busca da topbar: envia `search` para a listagem da rota informada e foca o
 * campo com Ctrl+K (⌘K no Mac). Na própria listagem, o campo já vem com o termo.
 *
 * @param {string} rota  nome da rota da listagem (ex.: 'patients.index')
 */
export function useBuscaGlobal(rota) {
    const campo = ref(null);
    const termo = ref(
        route().current(rota) ? new URLSearchParams(window.location.search).get('search') ?? '' : '',
    );
    const atalho = /Mac|iPhone|iPad/.test(navigator.userAgent) ? '⌘K' : 'Ctrl K';

    onKeyStroke(
        (e) => (e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k',
        (e) => {
            e.preventDefault();
            campo.value?.focus();
            campo.value?.select();
        },
    );

    const buscar = () => {
        const valor = termo.value.trim();
        router.get(route(rota), valor ? { search: valor } : {});
    };

    return { campo, termo, atalho, buscar };
}
