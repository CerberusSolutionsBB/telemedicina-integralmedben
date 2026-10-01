import { useForm } from '@inertiajs/vue3'

const hoje = () => new Date().toISOString().slice(0, 10)

/**
 * Dados e persistência da meta de desempenho (Create/Edit). Sem `desempenho`, cria.
 */
export function useDesempenhoForm({ desempenho = null } = {}) {
    const form = useForm({
        titulo: desempenho?.titulo ?? '',
        descricao: desempenho?.descricao ?? '',
        roles: desempenho?.roles ?? [],
        funcao: desempenho?.funcao ?? 'registro_beneficiario_plano',
        escopo_plano: desempenho?.escopo_plano ?? 'todos',
        cod_plano: desempenho?.cod_plano ?? '',
        tipo_meta: desempenho?.tipo_meta ?? 'individual',
        meta: desempenho?.meta ?? 10,
        data_inicio: desempenho?.data_inicio ?? hoje(),
        prazo: desempenho?.prazo ?? '',
    })

    const salvar = () => {
        if (desempenho) {
            form.put(route('desempenho.update', desempenho.id), { preserveScroll: true })
        } else {
            form.post(route('desempenho.store'), { preserveScroll: true })
        }
    }

    return { form, salvar }
}
