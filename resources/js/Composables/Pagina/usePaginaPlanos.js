import { computed, ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { showToast } from '@/Utils/toast'
import { firstError } from './helpers'

// Estado editável: uma linha por plano disponível, marcada ou não, com a quantidade
// e as vagas já ocupadas por associados vinculados.
const buildRows = (planos, tenantPlanos, planoUso) => {
    const salvos = new Map(tenantPlanos.map(p => [String(p.cod_plano), p.quantidade]))

    return planos.map(plano => {
        const emUso = Number(planoUso?.[plano.value] ?? 0)

        return {
            cod_plano: plano.value,
            label: plano.label,
            emUso,
            selecionado: salvos.has(plano.value),
            quantidade: salvos.get(plano.value) ?? Math.max(1, emUso),
        }
    })
}

const serialize = (rows) =>
    JSON.stringify(rows.filter(r => r.selecionado).map(r => [r.cod_plano, Number(r.quantidade)]))

// Um plano em uso não pode ser desmarcado nem ficar abaixo das vagas ocupadas.
export const quantidadeMinima = (row) => Math.max(1, row.emUso)

// Mesma regra de TenantPlanoCotaService::codigosDoItemSiprov.
const codigosDoItemSiprov = (item) =>
    [...new Set((item.planos || []).map(p => p?.codPlano).filter(c => c !== null && c !== undefined && c !== '').map(String))]

/**
 * Aba de planos: quais planos SIPROV o tenant oferece e quantos associados cada um comporta.
 */
export function usePaginaPlanos(props) {
    const planoRows = ref(buildRows(props.planos, props.tenantPlanos, props.planoUso))
    const planosSalvos = ref(serialize(planoRows.value))
    const isSavingPlanos = ref(false)

    watch(() => [props.tenantPlanos, props.planoUso], () => {
        planoRows.value = buildRows(props.planos, props.tenantPlanos, props.planoUso)
        planosSalvos.value = serialize(planoRows.value)
    })

    const planosSelecionados = computed(() => planoRows.value.filter(r => r.selecionado))

    const totalQuantidadePlanos = computed(() =>
        planosSelecionados.value.reduce((total, r) => total + (Number(r.quantidade) || 0), 0),
    )

    const totalEmUsoPlanos = computed(() =>
        planoRows.value.reduce((total, r) => total + r.emUso, 0),
    )

    const planosHasUnsavedChanges = computed(() => serialize(planoRows.value) !== planosSalvos.value)

    const isQuantidadeInvalida = (row) => {
        if (!row.selecionado) return false
        const quantidade = Number(row.quantidade)
        return !Number.isInteger(quantidade) || quantidade < quantidadeMinima(row)
    }

    const hasQuantidadeInvalida = computed(() => planoRows.value.some(isQuantidadeInvalida))

    // Vagas conforme o que está salvo (não o rascunho da aba), usadas no vínculo SIPROV.
    const planoVagas = computed(() => {
        const labels = new Map(props.planos.map(p => [p.value, p.label]))

        return props.tenantPlanos.map(p => {
            const codigo = String(p.cod_plano)
            const emUso = Number(props.planoUso?.[codigo] ?? 0)

            return {
                cod_plano: codigo,
                label: labels.get(codigo) ?? `Plano ${codigo}`,
                quantidade: p.quantidade,
                emUso,
                disponivel: Math.max(0, p.quantidade - emUso),
            }
        })
    })

    /**
     * Prévia da validação feita no servidor ao vincular associados SIPROV.
     * Retorna as mensagens de bloqueio (vazio quando a seleção cabe nas cotas).
     */
    const errosCotaSiprov = (itens) => {
        const vagas = new Map(planoVagas.value.map(v => [v.cod_plano, v]))
        const novos = new Map()
        const erros = []

        for (const item of itens) {
            const nome = item.nomePessoa || 'Associado'
            const codigos = codigosDoItemSiprov(item)

            if (!codigos.length) {
                erros.push(`${nome} não possui plano.`)
                continue
            }

            for (const codigo of codigos) {
                if (!vagas.has(codigo)) {
                    const planoNome = item.planos.find(p => String(p?.codPlano) === codigo)?.nome || `Plano ${codigo}`
                    erros.push(`${nome}: o plano ${planoNome} não está habilitado para este tenant.`)
                    continue
                }
                novos.set(codigo, (novos.get(codigo) ?? 0) + 1)
            }
        }

        for (const [codigo, quantidade] of novos) {
            const vaga = vagas.get(codigo)
            if (quantidade > vaga.disponivel) {
                erros.push(`Limite do plano ${vaga.label} excedido: ${quantidade} selecionado(s), ${vaga.disponivel} vaga(s) disponível(is) de ${vaga.quantidade}.`)
            }
        }

        return erros
    }

    const descartarPlanos = () => {
        planoRows.value = buildRows(props.planos, props.tenantPlanos, props.planoUso)
    }

    const salvarPlanos = () => {
        if (!props.tenant?.id) {
            showToast('Tenant não encontrado.', 'error')
            return
        }

        if (hasQuantidadeInvalida.value) {
            showToast('A quantidade de cada plano deve ser no mínimo 1 e não pode ser menor que os associados já vinculados.', 'warning')
            return
        }

        isSavingPlanos.value = true

        router.put(
            route('pagina.configuracao.planos', props.tenant.id),
            {
                planos: planosSelecionados.value.map(r => ({
                    cod_plano: r.cod_plano,
                    quantidade: Number(r.quantidade),
                })),
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    showToast('Planos atualizados com sucesso!', 'success')
                    router.reload({ only: ['tenantPlanos', 'planoUso'], preserveScroll: true })
                },
                onError: (errors) => {
                    showToast(firstError(errors, 'Erro ao salvar os planos.'), 'error')
                },
                onFinish: () => {
                    isSavingPlanos.value = false
                },
            },
        )
    }

    return {
        planoRows,
        isSavingPlanos,
        planosSelecionados,
        totalQuantidadePlanos,
        totalEmUsoPlanos,
        planosHasUnsavedChanges,
        isQuantidadeInvalida,
        hasQuantidadeInvalida,
        planoVagas,
        errosCotaSiprov,
        descartarPlanos,
        salvarPlanos,
    }
}
