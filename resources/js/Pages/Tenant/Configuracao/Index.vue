<script setup>
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import SearchInput from "@/Components/SearchInput.vue";
import { computed, ref, reactive, watch, nextTick, onUnmounted } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Settings, Info } from "lucide-vue-next";

const props = defineProps({
    configurations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["saved", "error"]);

const search = ref("");
const activeCategory = ref("all");
const expandedKeys = ref(new Set());
const savingKeys = ref(new Set());
const toast = ref(null);

const previews = reactive({});
const fileInputs = reactive({});

const form = useForm({
    logo: null,
});

const initExpanded = () => {
    props.configurations.forEach((config) => {
        if (config.type === "image") {
            expandedKeys.value.add(config.key);
        }
    });
};

initExpanded();

watch(
    () => props.configurations,
    () => initExpanded(),
    { deep: true }
);

const allCategories = computed(() => {
    const categories = new Set(
        props.configurations.map((config) => config.category || "Geral")
    );

    return ["all", ...Array.from(categories)];
});

const categoryLabels = computed(() => {
    const labels = {
        all: "Todas",
    };

    props.configurations.forEach((config) => {
        const category = config.category || "Geral";
        labels[category] = category;
    });

    return labels;
});

const filteredConfigurations = computed(() => {
    let configs = props.configurations;

    if (activeCategory.value !== "all") {
        configs = configs.filter(
            (config) => (config.category || "Geral") === activeCategory.value
        );
    }

    if (!search.value.trim()) {
        return configs;
    }

    const term = search.value.toLowerCase().trim();

    return configs.filter(
        (config) =>
            config.label?.toLowerCase().includes(term) ||
            config.description?.toLowerCase().includes(term) ||
            config.key?.toLowerCase().includes(term)
    );
});

const configsByCategory = computed(() => {
    const grouped = {};

    filteredConfigurations.value.forEach((config) => {
        const category = config.category || "Geral";

        if (!grouped[category]) {
            grouped[category] = [];
        }

        grouped[category].push(config);
    });

    return grouped;
});

const isExpanded = (key) => expandedKeys.value.has(key) || search.value.length > 0;

const toggleExpand = (key) => {
    if (expandedKeys.value.has(key)) {
        expandedKeys.value.delete(key);
        return;
    }

    expandedKeys.value.add(key);
};

const setFileRef = (el, key) => {
    if (el) {
        fileInputs[key] = el;
    }
};

let toastTimer = null;

const showToast = (type, message) => {
    clearTimeout(toastTimer);

    toast.value = {
        type,
        message,
    };

    toastTimer = setTimeout(() => {
        toast.value = null;
    }, 3000);
};

const handleImageUpload = (event, config) => {
    const file = event.target.files?.[0];

    if (!file) return;

    const allowedTypes = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
        "image/svg+xml",
    ];

    if (!allowedTypes.includes(file.type)) {
        showToast("error", "Formato inválido. Use JPG, PNG, GIF, WEBP ou SVG.");
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        showToast("error", "Imagem muito grande. Máximo permitido: 2MB.");
        return;
    }

    if (previews[config.key]?.url?.startsWith("blob:")) {
        URL.revokeObjectURL(previews[config.key].url);
    }

    previews[config.key] = {
        url: URL.createObjectURL(file),
        file,
        name: file.name,
        size: `${(file.size / 1024).toFixed(1)} KB`,
    };

    expandedKeys.value.add(config.key);
};

const removeImage = (config) => {
    if (previews[config.key]?.url?.startsWith("blob:")) {
        URL.revokeObjectURL(previews[config.key].url);
    }

    delete previews[config.key];

    nextTick(() => {
        if (fileInputs[config.key]) {
            fileInputs[config.key].value = "";
        }
    });
};

const saveConfig = (config) => {
    const key = config.key;

    if (savingKeys.value.has(key)) return;

    if (config.type !== "image") return;

    if (!previews[key]?.file) {
        showToast("error", "Selecione uma imagem antes de salvar.");
        return;
    }

    savingKeys.value.add(key);

    form.logo = previews[key].file;

    form.post(route("configuracao.logo.update"), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            showToast("success", `"${config.label}" salva com sucesso!`);
            emit("saved", { key });

            if (previews[key]?.url?.startsWith("blob:")) {
                URL.revokeObjectURL(previews[key].url);
            }

            delete previews[key];
        },
        onError: (errors) => {
            const message = errors.logo || "Erro ao salvar configuração.";
            showToast("error", message);
            emit("error", { key, errors });
        },
        onFinish: () => {
            savingKeys.value.delete(key);
            form.logo = null;
        },
    });
};

onUnmounted(() => {
    Object.values(previews).forEach((preview) => {
        if (preview?.url?.startsWith("blob:")) {
            URL.revokeObjectURL(preview.url);
        }
    });

    clearTimeout(toastTimer);
});
</script>

<template>
    <TenantAdminLayout>

        <div class="space-y-8">
            <!-- TOPO -->
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-6">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex items-start gap-4 w-full">
                        <div class="w-16 h-16 rounded-2xl bg-cyan-100 flex items-center justify-center shrink-0">
                            <Settings class="w-8 h-8 text-cyan-600" />
                        </div>

                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl md:text-3xl font-bold text-gray-900 truncate">
                                Opções de configuração
                            </h1>

                            <p class="text-sm text-gray-500 mt-1">
                                Busque e edite as configurações disponíveis para este tenant.
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 px-4 py-2 bg-cyan-50 text-cyan-700 rounded-lg text-sm shrink-0">
                        <span class="font-medium">
                            {{ props.configurations.length }} configurações
                        </span>
                    </div>
                </div>
            </div>

            <!-- TOAST -->
            <Transition name="toast">
                <div v-if="toast"
                    class="fixed top-4 right-4 z-50 flex items-center gap-3 px-6 py-4 rounded-xl shadow-2xl max-w-md"
                    :class="{
                        'bg-green-600 text-white': toast.type === 'success',
                        'bg-red-600 text-white': toast.type === 'error',
                    }">
                    <p class="text-sm font-medium">
                        {{ toast.message }}
                    </p>

                    <button type="button" @click="toast = null" class="ml-2 opacity-75 hover:opacity-100">
                        ✕
                    </button>
                </div>
            </Transition>

            <!-- FILTROS -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 overflow-x-auto pb-2 scrollbar-hide">
                    <button v-for="category in allCategories" :key="category" type="button"
                        @click="activeCategory = category"
                        class="px-4 py-2 rounded-lg text-sm font-medium transition-all whitespace-nowrap" :class="activeCategory === category
                            ? 'inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm cursor-pointer'
                            : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'
                            ">
                        {{ categoryLabels[category] || category }}
                    </button>
                </div>

                <div class="max-w-lg">
                    <SearchInput v-model="search" placeholder="Buscar configurações..." />
                </div>
            </div>

            <!-- CONTEÚDO -->
            <div v-if="Object.keys(configsByCategory).length" class="space-y-10">

                <section v-for="(configs, category) in configsByCategory" :key="category" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ category }}
                        </h2>
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <span class="text-xs text-gray-400 font-medium">
                            {{ configs.length }} itens
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div v-for="config in configs" :key="config.key"
                            class="group bg-white rounded-2xl border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-lg hover:border-gray-300"
                            :class="{
                                'shadow-md': isExpanded(config.key),
                            }">
                            <!-- HEADER CARD -->
                            <div class="p-5 cursor-pointer" @click="toggleExpand(config.key)">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <h3 class="font-semibold text-gray-900 text-sm truncate">
                                            {{ config.label }}
                                        </h3>
                                        <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed mt-1">
                                            {{ config.description }}
                                        </p>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <img v-if="config.type === 'image' && (previews[config.key]?.url || config.value)"
                                            :src="previews[config.key]?.url || config.value"
                                            class="w-8 h-8 rounded-lg object-cover border border-gray-200"
                                            alt="Preview" />

                                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-200"
                                            :class="{ 'rotate-180': isExpanded(config.key) }" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </div>

                            <!-- BODY -->
                            <Transition name="expand">
                                <div v-if="isExpanded(config.key)" class="border-t border-gray-100">
                                    <div class="p-5 space-y-4">
                                        <div v-if="config.type === 'image'" class="space-y-3">
                                            <div
                                                class="flex items-start gap-3 p-4 rounded-xl border border-cyan-200 bg-cyan-50 text-cyan-800">
                                                <Info class="w-5 h-5 shrink-0 mt-0.5" />
                                                <div class="text-sm">
                                                    <p class="font-medium">
                                                        Formatos aceitos: JPG, PNG, GIF, WEBP e SVG
                                                    </p>
                                                    <p class="text-xs text-cyan-700 mt-1">
                                                        Tamanho máximo de 2MB por arquivo. Recomendamos imagens em PNG
                                                        com
                                                        fundo transparente para melhor exibição da logo.
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="relative group/image rounded-xl overflow-hidden bg-gray-50 border-2 border-dashed border-gray-300 hover:border-blue-400 transition-colors">
                                                <img v-if="previews[config.key]?.url || config.value"
                                                    :src="previews[config.key]?.url || config.value"
                                                    class="w-full h-48 object-contain bg-gray-50" alt="Logo" />

                                                <div v-else
                                                    class="w-full h-48 flex flex-col items-center justify-center text-gray-400 gap-2">
                                                    <span class="text-sm">
                                                        Nenhuma imagem selecionada
                                                    </span>
                                                </div>

                                                <div v-if="previews[config.key]?.url"
                                                    class="absolute inset-0 bg-black/50 opacity-0 group-hover/image:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                                    <button type="button" @click.stop="removeImage(config)"
                                                        class="px-3 py-1.5 bg-red-500 text-white text-sm rounded-lg hover:bg-red-600 transition-colors">
                                                        Remover
                                                    </button>
                                                </div>
                                            </div>
                                            <span class="text-xs text-gray-400">
                                                Última atualização:
                                                {{ config.updated_at || "Nunca" }}
                                            </span>
                                            <div class="flex items-center gap-3">
                                                <label class="flex-1 cursor-pointer">
                                                    <input :ref="el => setFileRef(el, config.key)" type="file"
                                                        accept="image/*"
                                                        @change="event => handleImageUpload(event, config)"
                                                        class="hidden" />

                                                    <div
                                                        class="flex items-center justify-center gap-2 px-4 py-2.5 bg-white border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 hover:border-gray-400 transition-all">
                                                        {{ previews[config.key]?.name || "Escolher arquivo" }}
                                                    </div>
                                                </label>

                                                <span v-if="previews[config.key]?.size"
                                                    class="text-xs text-gray-500 whitespace-nowrap">
                                                    {{ previews[config.key].size }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- FALLBACK -->
                                        <div v-else class="text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
                                            Tipo de configuração ainda não implementado:
                                            <strong>{{ config.type }}</strong>
                                        </div>

                                        <!-- FOOTER -->
                                        <div class="flex items-center justify-between pt-3 border-t border-gray-100">


                                            <button v-if="config.type === 'image'" type="button"
                                                @click="saveConfig(config)" :disabled="savingKeys.has(config.key)"
                                                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm cursor-pointer"
                                                style="width: 100%;">
                                                <svg v-if="savingKeys.has(config.key)" class="w-3 h-3 animate-spin"
                                                    fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10"
                                                        stroke="currentColor" stroke-width="4" />
                                                    <path class="opacity-75" fill="currentColor"
                                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                                </svg>

                                                <span class="text-center">
                                                    {{ savingKeys.has(config.key) ? "Salvando..." : "Salvar" }}
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </Transition>
                        </div>
                    </div>
                </section>
            </div>

            <!-- EMPTY -->
            <div v-else class="text-center py-20">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gray-100 mb-6">
                    🔍
                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-2">
                    Nenhuma configuração encontrada
                </h3>

                <p class="text-sm text-gray-500 max-w-sm mx-auto">
                    Tente ajustar os filtros ou termos de busca.
                </p>

                <button v-if="search || activeCategory !== 'all'" type="button"
                    @click="search = ''; activeCategory = 'all'"
                    class="mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition-colors">
                    Limpar filtros
                </button>
            </div>
        </div>
    </TenantAdminLayout>
</template>

<style scoped>
.toast-enter-active,
.toast-leave-active {
    transition: all 0.3s ease;
}

.toast-enter-from,
.toast-leave-to {
    opacity: 0;
    transform: translateX(100%) scale(0.95);
}

.expand-enter-active,
.expand-leave-active {
    transition: all 0.25s ease;
    overflow: hidden;
}

.expand-enter-from,
.expand-leave-to {
    opacity: 0;
    max-height: 0;
}

.scrollbar-hide::-webkit-scrollbar {
    display: none;
}

.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
