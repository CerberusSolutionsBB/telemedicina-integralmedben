import { computed, watch } from 'vue'
import { soDigitos } from './useCamposPaciente'

// Mesmo limite do StorePatientRequest::MAX_FAMILIARES.
export const MAX_FAMILIARES = 3

const FAMILIAR_VAZIO = { id: null, nome: '', cpf: '', data_nascimento: '', tipo: '' }

const formatarCpf = (valor) =>
    soDigitos(valor).slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2')

/**
 * Membros da família do plano familiar: aparece quando o plano escolhido
 * (ou o vínculo atual, no Edit) é o familiar.
 */
export function useFamiliaresPaciente(form, { planos, planoAtual }) {
    const planoFamiliar = computed(() =>
        Boolean(planos.find((p) => p.value === form.cod_plano)?.familiar || (!form.cod_plano && planoAtual?.familiar)),
    )

    const podeAdicionar = computed(() => form.familiares.length < MAX_FAMILIARES)

    const adicionarFamiliar = () => {
        if (podeAdicionar.value) form.familiares.push({ ...FAMILIAR_VAZIO })
    }
    const removerFamiliar = (indice) => form.familiares.splice(indice, 1)

    // Máscara do CPF de cada familiar.
    watch(() => form.familiares.map((f) => f.cpf), (cpfs) => {
        cpfs.forEach((cpf, i) => {
            const formatado = formatarCpf(cpf)
            if (formatado !== cpf) form.familiares[i].cpf = formatado
        })
    })

    // Ao escolher o plano familiar, já abre a primeira linha.
    watch(planoFamiliar, (familiar) => {
        if (familiar && !form.familiares.length) adicionarFamiliar()
    })

    const erroFamiliar = (indice, campo) => form.errors[`familiares.${indice}.${campo}`]

    // Aviso imediato: CPF igual ao do beneficiário ou repetido entre os familiares.
    const avisoCpfFamiliar = (indice) => {
        const cpf = soDigitos(form.familiares[indice]?.cpf)
        if (cpf.length !== 11) return null
        if (cpf === soDigitos(form.cpf)) return 'O CPF do familiar deve ser diferente do CPF do beneficiário.'
        const repetido = form.familiares.some((f, i) => i !== indice && soDigitos(f.cpf) === cpf)
        return repetido ? 'Este CPF já foi informado para outro familiar.' : null
    }

    return { planoFamiliar, podeAdicionar, adicionarFamiliar, removerFamiliar, erroFamiliar, avisoCpfFamiliar }
}
