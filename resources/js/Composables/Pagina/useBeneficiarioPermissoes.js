import { useForm } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'

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
    const form = useForm({ create: true, edit: false, delete: false, status: false, ...props.permissoes })

    const salvar = () => {
        form.put(route('pagina.configuracao.beneficiario', props.tenantId), {
            preserveScroll: true,
            onSuccess: () => {
                form.defaults()
                showToast('Permissões de beneficiário atualizadas!', 'success')
            },
            onError: () => showToast('Erro ao salvar as permissões.', 'error'),
        })
    }

    return { form, salvar }
}
