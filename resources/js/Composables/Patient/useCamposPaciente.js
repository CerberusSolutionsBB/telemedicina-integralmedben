import { computed, watch } from 'vue'

export const soDigitos = (valor) => String(valor ?? '').replace(/\D/g, '')

export const EMAIL_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

const formatarCpf = (valor) =>
    soDigitos(valor).slice(0, 11)
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d)/, '$1.$2')
        .replace(/(\d{3})(\d{1,2})$/, '$1-$2')

const formatarCelular = (valor) =>
    soDigitos(valor).slice(0, 11)
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2')

// Aplica a máscara quando o valor muda (permite v-model direto no input).
// Não roda na carga inicial para não marcar o formulário como alterado.
const mascarar = (form, campo, formatar) =>
    watch(() => form[campo], (valor) => {
        const formatado = formatar(valor)
        if (formatado !== valor) form[campo] = formatado
    })

/**
 * Máscaras de CPF/celular e avisos imediatos de CPF e e-mail.
 */
export function useCamposPaciente(form) {
    mascarar(form, 'cpf', formatarCpf)
    mascarar(form, 'numero', formatarCelular)

    const cpfValido = computed(() => soDigitos(form.cpf).length === 11)
    const emailValido = computed(() => EMAIL_REGEX.test(form.email.trim()))

    const avisoCpf = computed(() =>
        form.cpf && !cpfValido.value ? 'CPF incompleto: são 11 dígitos.' : null,
    )
    const avisoEmail = computed(() =>
        form.email.trim() && !emailValido.value ? 'Confira o e-mail (ex.: nome@dominio.com).' : null,
    )

    return { cpfValido, emailValido, avisoCpf, avisoEmail }
}
