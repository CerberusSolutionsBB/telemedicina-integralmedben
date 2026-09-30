// Helpers compartilhados pelos composables de Pagina.

export const firstError = (errors, fallback) =>
    Object.values(errors || {}).flat()[0] || fallback

export const formatDate = (date) => {
    if (!date) return '-'

    const parsedDate = new Date(date)

    if (Number.isNaN(parsedDate.getTime())) return '-'

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
    }).format(parsedDate)
}

export const formatDateTime = (date) => {
    if (!date) return '-'

    const parsedDate = new Date(date)

    if (Number.isNaN(parsedDate.getTime())) return '-'

    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(parsedDate)
}

export const csrfToken = () =>
    document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''

export const xsrfToken = () =>
    decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || '')
