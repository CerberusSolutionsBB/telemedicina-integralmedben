import { computed, ref, watch } from 'vue'

const formatarData = (iso) => (iso ? iso.split('-').reverse().join('/') : '…')

const formatarMoeda = (valor) => Number(valor || 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })

/**
 * Regras dos campos da meta e o resumo em texto do que foi configurado.
 */
export function useCamposDesempenho(form, { planos, roles, usuariosIniciais = [] }) {
    // Usuários escolhidos no modal: [{ id, name, email, roles: [idPerfil] }].
    const usuarios = ref(
        usuariosIniciais
            .map((usuario) => ({
                ...usuario,
                roles: (usuario.roles ?? []).filter((roleId) => form.roles.includes(roleId)),
            }))
            .filter((usuario) => usuario.roles.length > 0),
    )
    const perfilModal = ref(null)
    const modalAberto = ref(false)

    // O formulário envia apenas os ids; os nomes ficam para a exibição.
    watch(usuarios, (lista) => {
        form.usuarios = lista.map((usuario) => usuario.id)
    }, { deep: true, immediate: true })

    // Escopo "todos os planos" não usa plano específico.
    watch(() => form.escopo_plano, (escopo) => {
        if (escopo === 'todos') form.cod_plano = ''
    })

    const abrirSelecaoUsuarios = (role) => {
        perfilModal.value = role
        modalAberto.value = true
    }

    const alternarRole = (id) => {
        const marcando = !form.roles.includes(id)
        form.roles = marcando ? [...form.roles, id] : form.roles.filter((r) => r !== id)
        usuarios.value = usuarios.value.filter((usuario) => usuario.roles.some((roleId) => form.roles.includes(roleId)))

        // Ao marcar o perfil, abre direto o modal para escolher os usuários.
        if (marcando) {
            const role = roles.find((r) => r.id === id)
            if (role) abrirSelecaoUsuarios(role)
        }
    }

    const usuariosDoPerfil = (roleId) => usuarios.value.filter((usuario) => usuario.roles.includes(roleId))

    const selecionadosModal = computed(() => (perfilModal.value ? usuariosDoPerfil(perfilModal.value.id) : []))

    // Substitui a seleção do perfil pelos usuários confirmados no modal,
    // preservando quem já participa por outro perfil.
    const confirmarUsuarios = (escolhidos) => {
        const role = perfilModal.value
        if (!role) return

        const porId = new Map(usuarios.value.map((usuario) => [usuario.id, usuario]))
        const outros = usuarios.value.filter((usuario) => !usuario.roles.includes(role.id))
        const novos = escolhidos.map((usuario) => {
            const existente = porId.get(usuario.id)
            return existente
                ? { ...existente, ...usuario, roles: [...new Set([...existente.roles, role.id])] }
                : { ...usuario, roles: [role.id] }
        })

        usuarios.value = [...outros, ...novos]
    }

    const removerUsuario = (usuario, roleId) => {
        const restantes = usuario.roles.filter((id) => id !== roleId)

        if (restantes.length) {
            usuarios.value = usuarios.value.map((item) => (item.id === usuario.id ? { ...item, roles: restantes } : item))
        } else {
            usuarios.value = usuarios.value.filter((item) => item.id !== usuario.id)
        }
    }

    const erroUsuarios = computed(() => {
        const chave = Object.keys(form.errors).find((campo) => campo === 'usuarios' || campo.startsWith('usuarios.'))
        return chave ? form.errors[chave] : ''
    })

    const resumo = computed(() => {
        const plano = form.escopo_plano === 'plano'
            ? `no plano ${planos.find((p) => p.value === form.cod_plano)?.label ?? '…'}`
            : 'em qualquer plano'
        const perfis = roles.filter((r) => form.roles.includes(r.id)).map((r) => r.name).join(', ') || '…'
        const total = usuarios.value.length

        if (!total) {
            return `Nenhum usuário selecionado nos perfis ${perfis}. Use "Selecionar usuários" para incluir participantes.`
        }

        const comissao = form.funcao === 'comissao_venda_plano' && form.meta_valor !== '' && form.meta_valor !== null
            ? ` e ${formatarMoeda(form.meta_valor)} em comissões`
            : ''
        const cadastro = form.funcao === 'comissao_venda_plano'
            ? `vender ${form.meta || '…'} plano(s) ${plano}${comissao} entre ${formatarData(form.data_inicio)} e ${formatarData(form.prazo)}`
            : `registrar ${form.meta || '…'} beneficiário(s) ${plano} entre ${formatarData(form.data_inicio)} e ${formatarData(form.prazo)}`

        if (total === 1) {
            return `O usuário selecionado no perfil ${perfis} deve registrar ${cadastro}.`
        }

        return form.tipo_meta === 'coletiva'
            ? `A soma dos ${total} usuários selecionados nos perfis ${perfis} deve registrar ${cadastro}.`
            : `Cada um dos ${total} usuários selecionados nos perfis ${perfis} deve registrar ${cadastro}.`
    })

    // Contador "n/limite": âmbar a partir de 90% e vermelho no limite.
    const contador = (campo, limite) => {
        const usados = String(form[campo] ?? '').length
        return {
            texto: `${usados}/${limite}`,
            classe: usados >= limite ? 'text-red-600' : usados >= limite * 0.9 ? 'text-amber-600' : 'text-gray-400',
        }
    }

    return {
        alternarRole,
        usuariosDoPerfil,
        abrirSelecaoUsuarios,
        confirmarUsuarios,
        removerUsuario,
        perfilModal,
        modalAberto,
        selecionadosModal,
        erroUsuarios,
        resumo,
        contador,
    }
}
