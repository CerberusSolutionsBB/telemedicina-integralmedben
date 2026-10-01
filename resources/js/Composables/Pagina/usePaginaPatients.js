import { computed, ref } from 'vue'

const perPage = 25

const registroLabels = {
    formulario: 'Formulário',
    'form-dinamico': 'Form. Dinâmico',
    importacao: 'Importação',
    'form-publico': 'Form. Público',
    vinculo: 'Vínculo',
}

const registroColors = {
    formulario: 'bg-blue-50 text-blue-700 border border-blue-200',
    'form-dinamico': 'bg-purple-50 text-purple-700 border border-purple-200',
    importacao: 'bg-orange-50 text-orange-700 border border-orange-200',
    'form-publico': 'bg-teal-50 text-teal-700 border border-teal-200',
    vinculo: 'bg-cyan-50 text-cyan-700 border border-cyan-200',
}

const registroIcons = {
    formulario: '📝',
    'form-dinamico': '⚡',
    importacao: '📥',
    'form-publico': '🌐',
    vinculo: '🔗',
}

const isMasculino = (sexo) => {
    const s = String(sexo).toLowerCase()
    return s === 'm' || s === 'masculino'
}

export const formatCpf = (cpf) => {
    if (!cpf) return '—'
    const cleaned = cpf.replace(/\D/g, '')
    if (cleaned.length !== 11) return cpf
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4')
}

export const formatDateShort = (date) => {
    if (!date) return '—'
    const d = new Date(date)
    if (isNaN(d.getTime())) return date
    return d.toLocaleDateString('pt-BR')
}

export const patientSexoIcon = (sexo) => {
    if (!sexo) return null
    return isMasculino(sexo) ? 'M' : 'F'
}

export const patientSexoColor = (sexo) => {
    if (!sexo) return 'bg-gray-100 text-gray-500'
    return isMasculino(sexo) ? 'bg-blue-100 text-blue-700' : 'bg-pink-100 text-pink-700'
}

export const registroLabel = (registro) => registroLabels[registro] || registro || '—'

export const registroColor = (registro) =>
    registroColors[registro] || 'bg-gray-50 text-gray-600 border border-gray-200'

export const registroIcon = (registro) => registroIcons[registro] || '•'

// Cards por origem do registro: os dois primeiros sempre aparecem; os demais só com pacientes.
const ORIGENS_CARDS = [
    { key: 'formulario', label: 'Formulário', sempre: true },
    { key: 'form-dinamico', label: 'Formulário dinâmico', sempre: true },
    { key: 'form-publico', label: 'Formulário público' },
    { key: 'importacao', label: 'Importação' },
    { key: 'vinculo', label: 'Vínculo SIPROV' },
]

/**
 * Aba de pacientes: busca local, filtro por origem do registro e paginação client-side.
 */
export function usePaginaPatients(props) {
    const patientSearch = ref('')
    const patientPage = ref(1)
    const registroFiltro = ref('')

    const totaisPorRegistro = computed(() => {
        const contagem = props.patients.reduce((acc, p) => {
            acc[p.status_registro] = (acc[p.status_registro] ?? 0) + 1
            return acc
        }, {})

        return ORIGENS_CARDS
            .map((origem) => ({ ...origem, total: contagem[origem.key] ?? 0 }))
            .filter((origem) => origem.sempre || origem.total > 0)
    })

    // Card de origem: filtra a lista por ela; clicar de novo remove o filtro.
    const alternarRegistro = (key) => {
        registroFiltro.value = registroFiltro.value === key ? '' : key
        patientPage.value = 1
    }

    const filteredPatients = computed(() => {
        const q = patientSearch.value.toLowerCase().trim()
        const porOrigem = registroFiltro.value
            ? props.patients.filter(p => p.status_registro === registroFiltro.value)
            : props.patients
        if (!q) return porOrigem
        return porOrigem.filter(p =>
            (p.nome || '').toLowerCase().includes(q) ||
            (p.cpf || '').includes(q) ||
            (p.email || '').toLowerCase().includes(q) ||
            (p.telefone || '').includes(q),
        )
    })

    const paginatedPatients = computed(() => {
        const start = (patientPage.value - 1) * perPage
        return filteredPatients.value.slice(start, start + perPage)
    })

    const totalPatientPages = computed(() => Math.ceil(filteredPatients.value.length / perPage))

    const showingFrom = computed(() =>
        filteredPatients.value.length === 0 ? 0 : (patientPage.value - 1) * perPage + 1,
    )
    const showingTo = computed(() => Math.min(patientPage.value * perPage, filteredPatients.value.length))

    const patientPageLinks = computed(() => {
        const total = totalPatientPages.value
        const current = patientPage.value
        const pages = []

        if (total <= 7) {
            for (let i = 1; i <= total; i++) {
                pages.push({ label: String(i), active: i === current, page: i })
            }
            return pages
        }

        pages.push({ label: '1', active: current === 1, page: 1 })

        if (current > 3) {
            pages.push({ label: '...', active: false, page: null })
        }

        const start = Math.max(2, current - 1)
        const end = Math.min(total - 1, current + 1)

        for (let i = start; i <= end; i++) {
            pages.push({ label: String(i), active: i === current, page: i })
        }

        if (current < total - 2) {
            pages.push({ label: '...', active: false, page: null })
        }

        pages.push({ label: String(total), active: current === total, page: total })

        return pages
    })

    const goToPatientPage = (page) => {
        if (page < 1 || page > totalPatientPages.value) return
        patientPage.value = page
    }

    return {
        perPage,
        patientSearch,
        patientPage,
        registroFiltro,
        totaisPorRegistro,
        alternarRegistro,
        filteredPatients,
        paginatedPatients,
        totalPatientPages,
        showingFrom,
        showingTo,
        patientPageLinks,
        goToPatientPage,
    }
}
