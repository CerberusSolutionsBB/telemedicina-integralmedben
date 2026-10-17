import { watch } from 'vue'
import { useForm } from '@inertiajs/vue3'
import { rotaDe } from './modulos'

/**
 * Dados e persistência do cadastro (Create/Edit) de um módulo. Sem `item`, cria.
 */
export function useCadastroIconeForm(modulo, { item = null } = {}) {
    const form = useForm({
        titulo: item?.titulo ?? '',
        descricao: item?.descricao ?? '',
        icone: item?.icone ?? '',
        status: item?.status ?? true,
        // Campos próprios do módulo (ex.: desconto e vínculos do cupom de benefício).
        ...(modulo.valoresIniciais?.(item) ?? {}),
    })

    // Corrigiu o campo: o aviso dele (e a contagem do resumo) some na hora.
    Object.keys(form.data()).forEach((campo) => {
        watch(() => form[campo], () => form.errors[campo] && form.clearErrors(campo))
    })

    // Selects/datas vazios viram null (ex.: "qualquer plano", "sem prazo").
    const transformado = () => form.transform((dados) => Object.fromEntries(Object.entries(dados).map(([k, v]) => [k, v === '' ? null : v])))

    // Erro no envio: leva o usuário ao primeiro campo inválido (na ordem da tela).
    const focarPrimeiroErro = () => {
        requestAnimationFrame(() => {
            const campo = Object.keys(form.errors)
                .map((nome) => document.getElementById(nome))
                .filter(Boolean)
                .sort((a, b) => (a.compareDocumentPosition(b) & Node.DOCUMENT_POSITION_FOLLOWING ? -1 : 1))[0]

            campo?.scrollIntoView({ block: 'center', behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' })
            campo?.focus({ preventScroll: true })
        })
    }

    const opcoes = { preserveScroll: true, onError: focarPrimeiroErro }

    const salvar = () => {
        if (item) {
            transformado().put(route(rotaDe(modulo, 'update'), item.id), opcoes)
        } else {
            transformado().post(route(rotaDe(modulo, 'store')), opcoes)
        }
    }

    return { form, salvar }
}
