import { ref, watch } from 'vue'

const lerStorage = (chave) => {
    try {
        return window.sessionStorage.getItem(chave)
    } catch {
        return null
    }
}

const gravarStorage = (chave, valor) => {
    try {
        window.sessionStorage.setItem(chave, valor)
    } catch {
        // Storage indisponível (modo privado/bloqueado): a URL ainda guarda a aba.
    }
}

// Atualiza ?{param}= sem nova visita Inertia nem entrada no histórico.
const sincronizarUrl = (param, aba, padrao) => {
    const url = new URL(window.location.href)
    if (aba === padrao) url.searchParams.delete(param)
    else url.searchParams.set(param, aba)

    if (url.href !== window.location.href) {
        window.history.replaceState(window.history.state, '', url.href)
    }
}

/**
 * Aba ativa que sobrevive ao recarregar a página e aos redirects após ações.
 * Ordem ao abrir: ?{param}= da URL → última aba usada (sessionStorage) → padrão.
 *
 * @param {string} chave chave do sessionStorage (ex.: por tenant)
 * @param {string[]} abasValidas chaves de aba aceitas
 * @param {string} padrao aba inicial quando nada foi guardado
 * @param {string} param nome do parâmetro na URL (sub-abas usam outro nome)
 */
export function useAbaAtiva(chave, abasValidas, padrao, param = 'aba') {
    const valida = (aba) => (abasValidas.includes(aba) ? aba : null)

    const inicial =
        valida(new URLSearchParams(window.location.search).get(param)) ??
        valida(lerStorage(chave)) ??
        padrao

    const activeTab = ref(inicial)

    sincronizarUrl(param, inicial, padrao)

    watch(activeTab, (aba) => {
        gravarStorage(chave, aba)
        sincronizarUrl(param, aba, padrao)
    })

    return { activeTab }
}
