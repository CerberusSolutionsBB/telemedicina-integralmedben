import { useForm } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

// Ações do CRUD de beneficiários (mesmas chaves de BeneficiarioPermissoes::ACOES).
export const ACOES_BENEFICIARIO = [
    { key: 'create', label: 'Cadastrar', descricao: 'Botão "Novo", cadastro e importação de beneficiários.' },
    { key: 'edit', label: 'Editar', descricao: 'Botão "Editar" e alteração dos dados do beneficiário.' },
    { key: 'delete', label: 'Excluir', descricao: 'Botão "Excluir" na listagem de beneficiários.' },
    { key: 'status', label: 'Alterar status', descricao: 'Ativar ou inativar o beneficiário.' },
]

/**
 * Aba Beneficiário: quais ações do CRUD o parceiro pode usar.
 */
export function useBeneficiarioPermissoes(props) {
    // Aninhado em "permissoes": no topo do useForm a chave "delete" é sobrescrita
    // pelo método form.delete() e some do envio. O transform manda o formato plano.
    const form = useForm({
        permissoes: { create: true, edit: false, delete: false, status: false, ...props.permissoes },
    }).transform((data) => data.permissoes)

    const salvar = () => {
        form.put(route('pagina.configuracao.beneficiario', props.tenantId), {
            preserveScroll: true,
            onSuccess: () => {
                form.defaults()
                showToast('Permissões de beneficiário atualizadas!', 'success')
            },
            onError: (errors) => {
                console.error('Erro ao salvar as permissões de beneficiário', errors)
                showToast(firstError(errors, 'Erro ao salvar as permissões.'), 'error')
            },
        })
    }

    return { form, salvar }
}
