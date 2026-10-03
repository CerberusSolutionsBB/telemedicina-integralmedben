import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const CHAVE = 'tema';

const lerPreferencia = () => {
    try {
        return localStorage.getItem(CHAVE) === 'escuro' ? 'escuro' : 'claro';
    } catch {
        return 'claro';
    }
};

// Compartilhado entre os layouts: o tema vale para todo o painel.
const tema = ref(lerPreferencia());

const aplicar = (ativo) => {
    document.documentElement.classList.toggle('dark', ativo && tema.value === 'escuro');
};

/**
 * Tema claro/escuro do painel administrativo. A classe `dark` vai no <html>
 * só enquanto um layout administrativo está montado, assim as páginas públicas
 * (formulários, cartão) continuam sempre claras.
 */
export function useTema() {
    const escuro = computed(() => tema.value === 'escuro');

    const alternar = () => {
        tema.value = escuro.value ? 'claro' : 'escuro';
        aplicar(true);

        try {
            localStorage.setItem(CHAVE, tema.value);
        } catch {
            // Sem storage (aba privada): o tema vale só até recarregar.
        }
    };

    onMounted(() => aplicar(true));
    onBeforeUnmount(() => aplicar(false));

    return { escuro, alternar };
}
