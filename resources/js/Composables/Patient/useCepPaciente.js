import { nextTick, ref, watch } from 'vue'
import { soDigitos } from './useCamposPaciente'

const formatarCep = (valor) => soDigitos(valor).slice(0, 8).replace(/^(\d{5})(\d)/, '$1-$2')

/**
 * Máscara do CEP e preenchimento automático do endereço via ViaCEP.
 */
export function useCepPaciente(form) {
    const buscandoCep = ref(false)
    const cepNaoEncontrado = ref(false)

    const limparEndereco = () => {
        form.enderecos.logradouro = ''
        form.enderecos.bairro = ''
        form.enderecos.cidade = ''
        form.enderecos.estado = ''
    }

    const buscarCep = async (cep) => {
        buscandoCep.value = true
        try {
            const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`)
            const data = await response.json()

            if (data.erro) {
                cepNaoEncontrado.value = true
                limparEndereco()
                return
            }

            form.enderecos.logradouro = data.logradouro || ''
            form.enderecos.bairro = data.bairro || ''
            form.enderecos.cidade = data.localidade || ''
            form.enderecos.estado = data.uf || ''

            // Próximo passo natural depois do CEP: o número do endereço.
            nextTick(() => document.getElementById('numero_endereco')?.focus())
        } catch {
            cepNaoEncontrado.value = true
            limparEndereco()
        } finally {
            buscandoCep.value = false
        }
    }

    watch(() => form.enderecos.cep, (valor) => {
        const formatado = formatarCep(valor)
        if (formatado !== valor) {
            form.enderecos.cep = formatado
            return
        }

        cepNaoEncontrado.value = false
        if (soDigitos(formatado).length === 8) buscarCep(soDigitos(formatado))
    })

    return { buscandoCep, cepNaoEncontrado }
}
