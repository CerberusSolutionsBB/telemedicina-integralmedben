import { computed, watch } from 'vue'

const formatarData = (iso) => (iso ? iso.split('-').reverse().join('/') : '…')

/**
 * Regras dos campos da meta e o resumo em texto do que foi configurado.
 */
export function useCamposDesempenho(form, { planos, roles }) {
    // Escopo "todos os planos" não usa plano específico.
    watch(() => form.escopo_plano, (escopo) => {
        if (escopo === 'todos') form.cod_plano = ''
    })

    const alternarRole = (id) => {
        form.roles = form.roles.includes(id) ? form.roles.filter((r) => r !== id) : [...form.roles, id]
    }

    const resumo = computed(() => {
        const plano = form.escopo_plano === 'plano'
            ? `no plano ${planos.find((p) => p.value === form.cod_plano)?.label ?? '…'}`
            : 'em qualquer plano'
        const perfis = roles.filter((r) => form.roles.includes(r.id)).map((r) => r.name).join(', ') || '…'
        const quem = form.tipo_meta === 'coletiva'
            ? `Os usuários dos perfis ${perfis}, somados, devem`
            : `Cada usuário dos perfis ${perfis} deve`

        return `${quem} registrar ${form.meta || '…'} beneficiário(s) ${plano} entre ${formatarData(form.data_inicio)} e ${formatarData(form.prazo)}.`
    })

    // Contador "n/limite": âmbar a partir de 90% e vermelho no limite.
    const contador = (campo, limite) => {
        const usados = String(form[campo] ?? '').length
        return {
            texto: `${usados}/${limite}`,
            classe: usados >= limite ? 'text-red-600' : usados >= limite * 0.9 ? 'text-amber-600' : 'text-gray-400',
        }
    }

    return { alternarRole, resumo, contador }
}
