<template>
  <div v-if="activeTab === 'logo'" class="space-y-5">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h2 class="text-base font-semibold flex items-center gap-2 text-gray-800">
          <ImageIcon class="w-5 h-5 text-cyan-500" />
          Logos
        </h2>
        <p class="text-sm text-gray-500 mt-0.5">
          Faça upload da logo do seu parceiro
        </p>
      </div>

      <div class="flex items-center gap-3">
        <span
          class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-semibold"
          :class="list.length ? 'bg-cyan-50 text-cyan-700 border border-cyan-200' : 'bg-gray-50 text-gray-500 border border-gray-200'"
        >
          <ImageIcon class="w-4 h-4" />
          {{ list.length }}
        </span>

        <button
          type="button"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm"
          @click="openUploadModal"
        >
          <Upload class="w-4 h-4" />
          Nova Logo
        </button>
      </div>
    </div>

    <!-- Conteúdo com dados -->
    <div v-if="list.length">
      <!-- Busca -->
      <div class="relative w-full max-w-md">
        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          v-model="search"
          type="text"
          placeholder="Buscar logo por nome do parceiro..."
          class="w-full pl-10 pr-10 py-2.5 rounded-xl border border-gray-200 bg-white text-sm text-gray-700 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent transition-all"
          @input="page = 1"
        />
        <button
          v-if="search"
          type="button"
          class="absolute inset-y-0 right-2 flex items-center text-gray-400 hover:text-gray-600 transition-colors"
          @click="search = ''"
        >
          <X class="w-4 h-4" />
        </button>
      </div>

      <div v-if="search" class="text-xs text-gray-500">
        {{ filteredList.length }} de {{ list.length }} resultado(s)
      </div>

      <!-- Tabela -->
      <div class="border rounded-xl border-gray-200 bg-white shadow-sm overflow-hidden mt-3">
        <div class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Preview
                </th>
                <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Parceiro
                </th>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Formato
                </th>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Tamanho
                </th>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Dimensões
                </th>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Status
                </th>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Cadastro
                </th>
                <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 tracking-wider">
                  Ações
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
              <tr
                v-for="logo in paginatedList"
                :key="logo.id"
                class="even:bg-gray-50/50 hover:bg-cyan-50/30 transition-colors"
              >
                <!-- Preview -->
                <td class="px-3 py-3 text-center">
                  <div class="w-12 h-12 mx-auto rounded-lg border border-gray-200 bg-gray-50 flex items-center justify-center overflow-hidden">
                    <img
                      v-if="logo.url"
                      :src="logo.url"
                      :alt="logo.nome"
                      class="w-full h-full object-contain"
                      @error="$event.target.style.display = 'none'"
                    />
                    <ImageIcon v-else class="w-5 h-5 text-gray-300" />
                  </div>
                </td>

                <!-- Parceiro -->
                <td class="px-3 py-3 text-sm">
                  <span class="font-semibold text-gray-900">{{ logo.nome || '—' }}</span>
                </td>

                <!-- Formato -->
                <td class="px-3 py-3 text-center">
                  <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded uppercase">
                    {{ logo.formato || '—' }}
                  </span>
                </td>

                <!-- Tamanho -->
                <td class="px-3 py-3 text-center text-sm text-gray-600">
                  {{ formatFileSize(logo.tamanho) || '—' }}
                </td>

                <!-- Dimensões -->
                <td class="px-3 py-3 text-center text-sm text-gray-600">
                  {{ logo.largura && logo.altura ? `${logo.largura}x${logo.altura}px` : '—' }}
                </td>

                <!-- Status -->
                <td class="px-3 py-3 text-center">
                  <span
                    v-if="logo.ativo"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-green-500" />
                    Ativo
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"
                  >
                    <span class="w-1.5 h-1.5 rounded-full bg-red-500" />
                    Inativo
                  </span>
                </td>

                <!-- Cadastro -->
                <td class="px-3 py-3 text-center text-sm text-gray-500 whitespace-nowrap">
                  {{ formatDateShort(logo.created_at) || '—' }}
                </td>

                <!-- Ações -->
                <td class="px-3 py-3 text-center">
                  <div class="flex items-center justify-center gap-1">
                    <button
                      type="button"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-colors"
                      title="Visualizar"
                      @click="previewLogo(logo)"
                    >
                      <Eye class="w-4 h-4" />
                    </button>
                    <button
                      type="button"
                      class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors"
                      title="Excluir"
                      @click="deleteLogo(logo)"
                    >
                      <Trash2 class="w-4 h-4" />
                    </button>
                  </div>
                </td>
              </tr>

              <tr v-if="!paginatedList.length">
                <td colspan="8" class="text-center py-12 text-gray-400 text-sm">
                  Nenhuma logo encontrada para "{{ search }}"
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Paginação -->
        <div class="flex items-center justify-between px-4 py-3 border-t border-gray-100">
          <div class="text-sm text-gray-500">
            Mostrando {{ showingFrom }} a {{ showingTo }} de {{ filteredList.length }} resultados
          </div>
          <div v-if="totalPages > 1" class="flex gap-1">
            <button
              variant="outline"
              size="sm"
              class="px-2 py-1 text-sm h-8 rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="page <= 1"
              @click="goToPage(page - 1)"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <button
              v-for="link in pageLinks"
              :key="link.page ?? 'dots-' + link.label"
              :disabled="link.page === null"
              size="sm"
              class="px-3 py-1 text-sm h-8 rounded-md border transition-colors disabled:cursor-default"
              :class="link.active
                ? 'bg-cyan-600 text-white border-cyan-600'
                : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50'"
              @click="link.page && goToPage(link.page)"
            >
              {{ link.label }}
            </button>

            <button
              variant="outline"
              size="sm"
              class="px-2 py-1 text-sm h-8 rounded-md border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 disabled:opacity-40 disabled:cursor-not-allowed"
              :disabled="page >= totalPages"
              @click="goToPage(page + 1)"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Estado vazio -->
    <div v-else class="text-center py-16 text-gray-500">
      <div class="w-16 h-16 mx-auto bg-gray-100 rounded-full flex items-center justify-center mb-4">
        <ImageIcon class="w-8 h-8 text-gray-400" />
      </div>
      <p class="text-lg font-medium text-gray-900">Nenhuma logo</p>
      <p class="text-sm text-gray-500 mt-1">Nenhuma logo cadastrada para este parceiro.</p>
      <button
        type="button"
        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm"
        @click="openUploadModal"
      >
        <Upload class="w-4 h-4" />
        Fazer upload
      </button>
    </div>

    <!-- Modal de Preview (simples) -->
    <div
      v-if="previewUrl"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm"
      @click.self="previewUrl = null"
    >
      <div class="bg-white rounded-2xl shadow-2xl p-4 max-w-lg w-full mx-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-sm font-semibold text-gray-800">Preview da Logo</h3>
          <button
            type="button"
            class="p-1 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100"
            @click="previewUrl = null"
          >
            <X class="w-4 h-4" />
          </button>
        </div>
        <div class="flex items-center justify-center bg-gray-50 rounded-xl p-8">
          <img :src="previewUrl" alt="Preview" class="max-h-64 object-contain" />
        </div>
      </div>
    </div>
  </div>

  <!-- Fallback para outras tabs -->
  <div v-else class="text-center py-16 text-gray-400">
    <p class="text-sm">Aba "{{ activeTab }}" não implementada.</p>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue'
import {
  Image as ImageIcon,
  Search,
  X,
  ChevronLeft,
  ChevronRight,
  Upload,
  Eye,
  Trash2,
} from 'lucide-vue-next'

/* ── Props ─────────────────────────────────────────────── */
const props = defineProps({
  activeTab: {
    type: String,
    default: null,
  },
  list: {
    type: Array,
    default: () => [],
  },
})

/* ── Emits ─────────────────────────────────────────────── */
const emit = defineEmits(['upload', 'delete'])

/* ── Estado local ──────────────────────────────────────── */
const search = ref('')
const page = ref(1)
const perPage = ref(10)
const previewUrl = ref(null)

/* ── Computeds ─────────────────────────────────────────── */

const filteredList = computed(() => {
  if (!search.value.trim()) return props.list
  const term = search.value.toLowerCase()
  return props.list.filter((logo) =>
    logo.nome?.toLowerCase().includes(term)
  )
})

const totalPages = computed(() =>
  Math.ceil(filteredList.value.length / perPage.value) || 1
)

const paginatedList = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredList.value.slice(start, start + perPage.value)
})

const showingFrom = computed(() => {
  if (!filteredList.value.length) return 0
  return (page.value - 1) * perPage.value + 1
})

const showingTo = computed(() =>
  Math.min(page.value * perPage.value, filteredList.value.length)
)

const pageLinks = computed(() => {
  const links = []
  const maxVisible = 5
  const half = Math.floor(maxVisible / 2)

  let start = Math.max(1, page.value - half)
  let end = Math.min(totalPages.value, start + maxVisible - 1)

  if (end - start + 1 < maxVisible) {
    start = Math.max(1, end - maxVisible + 1)
  }

  if (start > 1) {
    links.push({ label: '1', page: 1, active: page.value === 1 })
    if (start > 2) links.push({ label: '...', page: null, active: false })
  }

  for (let i = start; i <= end; i++) {
    links.push({ label: String(i), page: i, active: page.value === i })
  }

  if (end < totalPages.value) {
    if (end < totalPages.value - 1) links.push({ label: '...', page: null, active: false })
    links.push({
      label: String(totalPages.value),
      page: totalPages.value,
      active: page.value === totalPages.value,
    })
  }

  return links
})

/* ── Métodos ───────────────────────────────────────────── */

function goToPage(newPage) {
  if (newPage >= 1 && newPage <= totalPages.value) {
    page.value = newPage
  }
}

function openUploadModal() {
  emit('upload')
}

function previewLogo(logo) {
  previewUrl.value = logo.url || null
}

function deleteLogo(logo) {
  if (confirm(`Tem certeza que deseja excluir a logo de "${logo.nome}"?`)) {
    emit('delete', logo.id)
  }
}

function formatFileSize(bytes) {
  if (!bytes || bytes === 0) return '—'
  const sizes = ['B', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(1024))
  return `${(bytes / Math.pow(1024, i)).toFixed(1)} ${sizes[i]}`
}

function formatDateShort(dateStr) {
  if (!dateStr) return null
  const d = new Date(dateStr)
  if (isNaN(d.getTime())) return dateStr
  return d.toLocaleDateString('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  })
}
</script>
