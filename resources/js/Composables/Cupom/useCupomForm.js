import { useForm } from '@inertiajs/vue3'

const agora = () => {
    const d = new Date()
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
    return d.toISOString().slice(0, 16)
}

const daquiUmMes = () => {
    const d = new Date()
    d.setMonth(d.getMonth() + 1)
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset())
    return d.toISOString().slice(0, 16)
}

/**
 * Dados e persistência do cupom (Create/Edit), no painel central e no do parceiro.
 * `rotas` = { store, update } (nomes das rotas). Sem `cupom`, cria.
 */
export function useCupomForm({ cupom = null, rotas }) {
    const form = useForm({
        codigo: cupom?.codigo ?? '',
        tipo: cupom?.tipo ?? 'percentual',
        valor: cupom?.valor ?? '',
        // Texto abaixo do desconto no PDF; vazio = texto padrão.
        texto_desconto: cupom?.texto_desconto ?? '',
        tenant_id: cupom?.tenant_id ?? '',
        origem: cupom?.origem ?? 'manual',
        inicio_em: cupom?.inicio_em ?? agora(),
        expira_em: cupom?.expira_em ?? daquiUmMes(),
        limite_uso_total: cupom?.limite_uso_total ?? '',
        limite_uso_por_cpf: cupom?.limite_uso_por_cpf ?? 1,
        valor_minimo: cupom?.valor_minimo ?? '',
        aplicavel_a: cupom?.aplicavel_a ?? 'todos',
        planos: cupom?.planos ?? [],
        observacoes: cupom?.observacoes ?? '',
    })

    const salvar = () => {
        // Campos vazios viram null (parceiro global, sem limite total, sem mínimo).
        const transformado = form.transform((dados) => ({
            ...dados,
            tenant_id: dados.tenant_id || null,
            limite_uso_total: dados.limite_uso_total === '' ? null : dados.limite_uso_total,
            valor_minimo: dados.valor_minimo === '' ? null : dados.valor_minimo,
        }))

        if (cupom) {
            transformado.put(route(rotas.update, cupom.id), { preserveScroll: true })
        } else {
            transformado.post(route(rotas.store), { preserveScroll: true })
        }
    }

    return { form, salvar }
}
