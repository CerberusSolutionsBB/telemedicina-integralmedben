import { onBeforeUnmount, onMounted, ref } from 'vue'
import { router } from '@inertiajs/vue3'

/**
 * Protege um formulário com alterações não salvas.
 * - Navegação dentro do sistema (links, menu): segura a visita e abre o modal
 *   SairSemSalvarDialog; "Sair sem salvar" refaz a mesma visita.
 * - Atalhos de recarregar (F5, Ctrl/⌘+R): também abrem o modal.
 * - Botão de recarregar, fechar a aba ou trocar a URL: o navegador só permite
 *   o aviso nativo (beforeunload), que fica como última proteção.
 *
 * @param {object} form useForm do Inertia
 * @param {{ ignorar?: import('vue').Ref<boolean> }} opcoes ignorar = true enquanto o formulário é enviado
 */
export function useSairSemSalvar(form, { ignorar = ref(false) } = {}) {
    const aberto = ref(false)
    // Visita do Inertia a refazer, ou 'recarregar' quando veio do atalho de recarga.
    let visitaPendente = null
    let liberado = false

    const protegido = () => form.isDirty && !ignorar.value && !liberado

    const onBeforeUnload = (event) => {
        if (protegido()) {
            event.preventDefault()
            event.returnValue = ''
        }
    }

    const ehRecarregar = (event) =>
        event.key === 'F5' || ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'r')

    const onKeydown = (event) => {
        if (!ehRecarregar(event) || !protegido()) return

        event.preventDefault()
        visitaPendente = 'recarregar'
        aberto.value = true
    }

    let removerGuarda = null

    onMounted(() => {
        window.addEventListener('beforeunload', onBeforeUnload)
        window.addEventListener('keydown', onKeydown)
        removerGuarda = router.on('before', (event) => {
            if (!protegido()) return

            event.preventDefault()
            visitaPendente = event.detail.visit
            aberto.value = true
        })
    })

    onBeforeUnmount(() => {
        window.removeEventListener('beforeunload', onBeforeUnload)
        window.removeEventListener('keydown', onKeydown)
        removerGuarda?.()
    })

    const ficar = () => {
        aberto.value = false
        visitaPendente = null
    }

    const sair = () => {
        const visita = visitaPendente
        aberto.value = false
        visitaPendente = null
        if (!visita) return

        liberado = true

        if (visita === 'recarregar') {
            window.location.reload()
            return
        }

        router.visit(visita.url, {
            method: visita.method,
            data: visita.data,
            replace: visita.replace,
            preserveScroll: visita.preserveScroll,
            preserveState: visita.preserveState,
            only: visita.only,
            except: visita.except,
            headers: visita.headers,
            onFinish: () => {
                liberado = false
            },
        })
    }

    return { aberto, ficar, sair }
}
