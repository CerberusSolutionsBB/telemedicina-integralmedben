<script setup>
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import SearchInput from "@/Components/SearchInput.vue";
import { computed, ref, reactive, nextTick, onUnmounted } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { Settings, Info, Palette, Image as ImageIcon, ToggleRight, Sparkles, AlertCircle, QrCode } from "lucide-vue-next";

const props = defineProps({
    configurations: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(["saved", "error"]);

const search = ref("");
const activeCategory = ref("all");
// Cards-expandíveis: por padrão todos estão abertos. O usuário pode
// recolher individualmente quando houver muita informação na tela.
const collapsedKeys = ref(new Set());
const savingKeys = ref(new Set());
const toast = ref(null);

const previews = reactive({});
const fileInputs = reactive({});

const form = useForm({
    logo: null,
});

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

const isExpanded = (key) => !collapsedKeys.value.has(key) || search.value.trim().length > 0;

const toggleExpand = (key) => {
    if (collapsedKeys.value.has(key)) {
        collapsedKeys.value.delete(key);
    } else {
        collapsedKeys.value.add(key);
    }
};

// Ícone + cor por tipo de configuração: permite reconhecer o tipo de cada
// card sem precisar ler o texto (leitura visual rápida).
const typeIconMap = {
    image: ImageIcon,
    style: Palette,
    toggle: ToggleRight,
    qrcode: QrCode,
};

const typeIcon = (config) => typeIconMap[config.type] || Settings;

const typeIconClasses = (config) => {
    if (config.type === "toggle") {
        return config.value
            ? "bg-emerald-50 text-emerald-600"
            : "bg-gray-100 text-gray-400";
    }

    if (config.type === "style") {
        return "bg-purple-50 text-purple-600";
    }

    if (config.type === "image") {
        return "bg-cyan-50 text-cyan-600";
    }

    if (config.type === "qrcode") {
        return config.value?.habilitado
            ? "bg-indigo-50 text-indigo-600"
            : "bg-gray-100 text-gray-400";
    }

    return "bg-gray-100 text-gray-500";
};

const categoryIconMap = {
    all: Settings,
    "Aparência": Palette,
    "Cartão Dinâmico": Sparkles,
};

const categoryIcon = (category) => categoryIconMap[category] || Settings;

// Destaca o termo pesquisado no rótulo/descrição para reduzir o esforço de
// leitura ao escanear os resultados. Os textos vêm de metadados fixos do
// backend (não são entrada livre de usuário), então usar v-html é seguro.
const escapeHtml = (value) =>
    String(value).replace(/[&<>"']/g, (char) => ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#39;",
    }[char]));

const highlightText = (text) => {
    const safe = escapeHtml(text || "");
    const term = search.value.trim();

    if (!term) return safe;

    const escapedTerm = escapeHtml(term).replace(/[.*+?^${}()|[\]\\]/g, "\\$&");

    return safe.replace(
        new RegExp(`(${escapedTerm})`, "ig"),
        '<mark class="bg-amber-200 text-gray-900 rounded px-0.5">$1</mark>'
    );
};

// Indicador de "alterações não salvas": ajuda a lembrar o usuário do que
// ficou pendente sem precisar manter isso na memória.
const hasUnsavedChanges = (config) => {
    if (config.type === "image") {
        return !!previews[config.key]?.file;
    }

    if (config.type === "style") {
        const estiloForm = cartaoEstiloForms[config.key];

        if (!estiloForm) return false;

        return (
            estiloForm.cor_primaria !== (config.value?.cor_primaria || "#22d3ee") ||
            estiloForm.cor_secundaria !== (config.value?.cor_secundaria || "#0e7490") ||
            estiloForm.cor_texto !== (config.value?.cor_texto || "#ffffff") ||
            estiloForm.fonte !== (config.value?.fonte || "sans-serif")
        );
    }

    if (config.type === "qrcode") {
        const qrcodeForm = cartaoQrcodeForms[config.key];

        if (!qrcodeForm) return false;

        return (
            qrcodeForm.habilitado !== !!config.value?.habilitado ||
            qrcodeForm.dados !== (config.value?.dados || "") ||
            qrcodeForm.texto_info !== (config.value?.texto_info || "") ||
            qrcodeForm.rodape !== (config.value?.rodape || "")
        );
    }

    return false;
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

    collapsedKeys.value.delete(config.key);
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

    if (config.upload_mode === "fetch") {
        uploadImageFetch(config, previews[key].file);
        return;
    }

    form.logo = previews[key].file;

    form.post(route(config.upload_route || "configuracao.logo.update"), {
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

const uploadImageFetch = async (config, file) => {
    const key = config.key;

    try {
        const formData = new FormData();
        formData.append("imagem", file);

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        const response = await fetch(route(config.upload_route, config.upload_route_params ?? []), {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
            body: formData,
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Erro ao enviar imagem.");
        }

        showToast("success", `"${config.label}" salva com sucesso!`);
        emit("saved", { key });

        if (previews[key]?.url?.startsWith("blob:")) {
            URL.revokeObjectURL(previews[key].url);
        }

        delete previews[key];

        router.reload({ only: ["configurations"], preserveScroll: true });
    } catch (error) {
        showToast("error", error.message || "Erro ao enviar imagem. Tente novamente.");
        emit("error", { key, errors: { imagem: error.message } });
    } finally {
        savingKeys.value.delete(key);
    }
};

const deleteServerImage = async (config) => {
    const key = config.key;

    if (savingKeys.value.has(key) || !config.delete_route) return;

    // Fricção deliberada antes de uma ação destrutiva e irreversível.
    if (!window.confirm(`Remover "${config.label}"? Essa ação não pode ser desfeita.`)) {
        return;
    }

    savingKeys.value.add(key);

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

        const response = await fetch(route(config.delete_route, config.delete_route_params ?? []), {
            method: "DELETE",
            headers: {
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            credentials: "same-origin",
        });

        const data = await response.json();

        if (!response.ok || !data.success) {
            throw new Error(data.message || "Erro ao remover imagem.");
        }

        showToast("success", `"${config.label}" removida com sucesso!`);
        emit("saved", { key });

        router.reload({ only: ["configurations"], preserveScroll: true });
    } catch (error) {
        showToast("error", error.message || "Erro ao remover imagem. Tente novamente.");
    } finally {
        savingKeys.value.delete(key);
    }
};

const fontesCartaoDinamico = [
    { value: "sans-serif", label: "Sem serifa (padrão)" },
    { value: "serif", label: "Com serifa" },
    { value: "monospace", label: "Monoespaçada" },
];

const cartaoEstiloForms = reactive({});

const getEstiloForm = (config) => {
    if (!cartaoEstiloForms[config.key]) {
        cartaoEstiloForms[config.key] = {
            cor_primaria: config.value?.cor_primaria || "#22d3ee",
            cor_secundaria: config.value?.cor_secundaria || "#0e7490",
            cor_texto: config.value?.cor_texto || "#ffffff",
            fonte: config.value?.fonte || "sans-serif",
        };
    }

    return cartaoEstiloForms[config.key];
};

const saveEstilo = (config) => {
    const key = config.key;

    if (savingKeys.value.has(key)) return;

    savingKeys.value.add(key);

    const estilo = getEstiloForm(config);

    router.put(
        route(config.save_route),
        {
            cartao_cor_primaria: estilo.cor_primaria,
            cartao_cor_secundaria: estilo.cor_secundaria,
            cartao_cor_texto: estilo.cor_texto,
            cartao_fonte: estilo.fonte,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast("success", `"${config.label}" salvo com sucesso!`);
                emit("saved", { key });
            },
            onError: () => {
                showToast("error", "Erro ao salvar configuração.");
                emit("error", { key });
            },
            onFinish: () => {
                savingKeys.value.delete(key);
            },
        }
    );
};

const cartaoQrcodeForms = reactive({});

const getQrcodeForm = (config) => {
    if (!cartaoQrcodeForms[config.key]) {
        cartaoQrcodeForms[config.key] = {
            habilitado: !!config.value?.habilitado,
            dados: config.value?.dados || "",
            texto_info: config.value?.texto_info || "",
            rodape: config.value?.rodape || "",
        };
    }

    return cartaoQrcodeForms[config.key];
};

const saveQrcode = (config) => {
    const key = config.key;

    if (savingKeys.value.has(key)) return;

    savingKeys.value.add(key);

    const qrcode = getQrcodeForm(config);

    router.put(
        route(config.save_route),
        {
            cartao_qrcode_habilitado: qrcode.habilitado,
            cartao_qrcode_dados: qrcode.dados,
            cartao_verso_texto_info: qrcode.texto_info,
            cartao_verso_rodape: qrcode.rodape,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast("success", `"${config.label}" salvo com sucesso!`);
                emit("saved", { key });
            },
            onError: () => {
                showToast("error", "Erro ao salvar configuração.");
                emit("error", { key });
            },
            onFinish: () => {
                savingKeys.value.delete(key);
            },
        }
    );
};

const toggleConfig = (config) => {
    const key = config.key;

    if (savingKeys.value.has(key) || !config.toggle_route) return;

    savingKeys.value.add(key);

    router.put(
        route(config.toggle_route),
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                showToast("success", `"${config.label}" atualizado com sucesso!`);
                emit("saved", { key });
            },
            onError: () => {
                showToast("error", "Erro ao atualizar configuração.");
                emit("error", { key });
            },
            onFinish: () => {
                savingKeys.value.delete(key);
            },
        }
    );
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
                <!-- Busca em primeiro lugar: é o caminho mais rápido até a configuração desejada -->
                <div class="max-w-lg">
                    <SearchInput v-model="search" placeholder="Buscar configurações..." size="lg" />
                </div>

                <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-hide">
                    <button v-for="category in allCategories" :key="category" type="button"
                        @click="activeCategory = category"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg text-sm font-medium whitespace-nowrap transition-colors"
                        :class="activeCategory === category
                            ? 'bg-cyan-600 text-white shadow-sm'
                            : 'bg-white text-gray-600 hover:bg-gray-50 border border-gray-200'">
                        <component :is="categoryIcon(category)" class="w-3.5 h-3.5 shrink-0" />
                        {{ categoryLabels[category] || category }}
                    </button>
                </div>
            </div>

            <!-- CONTEÚDO -->
            <div v-if="Object.keys(configsByCategory).length" class="space-y-10">

                <section v-for="(configs, category) in configsByCategory" :key="category" class="space-y-4">
                    <div class="flex items-center gap-2">
                        <component :is="categoryIcon(category)" class="w-4 h-4 text-gray-400 shrink-0" />
                        <h2 class="text-lg font-semibold text-gray-800">
                            {{ category }}
                        </h2>
                        <div class="flex-1 h-px bg-gray-200"></div>
                        <span class="text-xs text-gray-400 font-medium">
                            {{ configs.length }} itens
                        </span>
                    </div>

                    <!-- No máximo 2 colunas: menos alvos visuais competindo por atenção ao mesmo tempo -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <div v-for="config in configs" :key="config.key"
                            class="group bg-white rounded-2xl border border-gray-200 overflow-hidden transition-all duration-300 hover:shadow-lg hover:border-gray-300"
                            :class="{
                                'shadow-md': isExpanded(config.key),
                            }">
                            <!-- HEADER CARD -->
                            <div class="p-5 cursor-pointer select-none" @click="toggleExpand(config.key)"
                                role="button" :aria-expanded="isExpanded(config.key)">
                                <div class="flex items-start gap-3">
                                    <!-- Ícone fixo por tipo: reconhecimento visual sem precisar ler o texto -->
                                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                        :class="typeIconClasses(config)">
                                        <component :is="typeIcon(config)" class="w-5 h-5" />
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <h3 class="font-semibold text-gray-900 text-sm"
                                                v-html="highlightText(config.label)"></h3>
                                            <span v-if="hasUnsavedChanges(config)"
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium text-amber-700 bg-amber-50 border border-amber-200 shrink-0">
                                                <AlertCircle class="w-3 h-3" />
                                                não salvo
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 line-clamp-2 leading-relaxed mt-1"
                                            v-html="highlightText(config.description)"></p>
                                    </div>

                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <img v-if="config.type === 'image' && (previews[config.key]?.url || config.value)"
                                            :src="previews[config.key]?.url || config.value"
                                            class="w-8 h-8 rounded-lg object-cover border border-gray-200"
                                            alt="Preview" />

                                        <div v-if="config.type === 'style'" class="flex items-center -space-x-1">
                                            <span class="w-4 h-4 rounded-full border-2 border-white shadow"
                                                :style="{ backgroundColor: config.value?.cor_primaria }"></span>
                                            <span class="w-4 h-4 rounded-full border-2 border-white shadow"
                                                :style="{ backgroundColor: config.value?.cor_secundaria }"></span>
                                        </div>

                                        <span v-if="config.type === 'toggle'" :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                            config.value
                                                ? 'bg-cyan-100 text-cyan-700 border border-cyan-200'
                                                : 'bg-gray-100 text-gray-500 border border-gray-200',
                                        ]">
                                            {{ config.value ? "Ativado" : "Desativado" }}
                                        </span>

                                        <span v-if="config.type === 'qrcode'" :class="[
                                            'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                            config.value?.habilitado
                                                ? 'bg-indigo-100 text-indigo-700 border border-indigo-200'
                                                : 'bg-gray-100 text-gray-500 border border-gray-200',
                                        ]">
                                            {{ config.value?.habilitado ? "Ativado" : "Desativado" }}
                                        </span>

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
                                                        Cancelar
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

                                            <!-- FOOTER (imagem) -->
                                            <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                                                <button v-if="config.delete_route && config.value && !previews[config.key]?.file"
                                                    type="button" @click="deleteServerImage(config)"
                                                    :disabled="savingKeys.has(config.key)"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg border border-red-200 text-red-600 text-sm font-medium hover:bg-red-50 transition-colors">
                                                    {{ savingKeys.has(config.key) ? "Removendo..." : "Remover imagem" }}
                                                </button>

                                                <button type="button" @click="saveConfig(config)"
                                                    :disabled="savingKeys.has(config.key)"
                                                    class="flex-1 inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm cursor-pointer">
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

                                        <!-- ESTILO DO CARTÃO DINÂMICO -->
                                        <div v-else-if="config.type === 'style'" class="space-y-4">
                                            <div
                                                class="flex items-start gap-3 p-4 rounded-xl border border-cyan-200 bg-cyan-50 text-cyan-800">
                                                <Info class="w-5 h-5 shrink-0 mt-0.5" />
                                                <div class="text-sm">
                                                    <p class="font-medium">
                                                        Cores do cartão dinâmico
                                                    </p>
                                                    <p class="text-xs text-cyan-700 mt-1">
                                                        Estas cores são aplicadas no cartão gerado a partir do botão
                                                        "Cartão Dinâmico" na listagem de pacientes. Caso a frente ou o verso
                                                        do cartão possua imagem própria, o gradiente é usado como fundo
                                                        de fallback quando não há imagem configurada.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div class="grid gap-2">
                                                    <label class="text-xs font-medium text-gray-700">Cor Primária</label>
                                                    <div class="flex items-center gap-3">
                                                        <input type="color" v-model="getEstiloForm(config).cor_primaria"
                                                            class="w-12 h-10 rounded border border-gray-300 cursor-pointer" />
                                                        <input type="text" v-model="getEstiloForm(config).cor_primaria"
                                                            placeholder="#22d3ee"
                                                            class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm flex-1" />
                                                    </div>
                                                </div>
                                                <div class="grid gap-2">
                                                    <label class="text-xs font-medium text-gray-700">Cor Secundária</label>
                                                    <div class="flex items-center gap-3">
                                                        <input type="color" v-model="getEstiloForm(config).cor_secundaria"
                                                            class="w-12 h-10 rounded border border-gray-300 cursor-pointer" />
                                                        <input type="text" v-model="getEstiloForm(config).cor_secundaria"
                                                            placeholder="#0e7490"
                                                            class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm flex-1" />
                                                    </div>
                                                </div>
                                                <div class="grid gap-2">
                                                    <label class="text-xs font-medium text-gray-700">Cor do Texto</label>
                                                    <div class="flex items-center gap-3">
                                                        <input type="color" v-model="getEstiloForm(config).cor_texto"
                                                            class="w-12 h-10 rounded border border-gray-300 cursor-pointer" />
                                                        <input type="text" v-model="getEstiloForm(config).cor_texto"
                                                            placeholder="#ffffff"
                                                            class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm flex-1" />
                                                    </div>
                                                </div>
                                                <div class="grid gap-2">
                                                    <label class="text-xs font-medium text-gray-700">Fonte</label>
                                                    <select v-model="getEstiloForm(config).fonte"
                                                        class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm">
                                                        <option v-for="fonte in fontesCartaoDinamico" :key="fonte.value"
                                                            :value="fonte.value">
                                                            {{ fonte.label }}
                                                        </option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="flex justify-end pt-3 border-t border-gray-100">
                                                <button type="button" @click="saveEstilo(config)"
                                                    :disabled="savingKeys.has(config.key)"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm cursor-pointer">
                                                    {{ savingKeys.has(config.key) ? "Salvando..." : "Salvar Estilo" }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- QR CODE E TEXTOS DO VERSO -->
                                        <div v-else-if="config.type === 'qrcode'" class="space-y-4">
                                            <div
                                                class="flex items-start gap-3 p-4 rounded-xl border border-cyan-200 bg-cyan-50 text-cyan-800">
                                                <Info class="w-5 h-5 shrink-0 mt-0.5" />
                                                <div class="text-sm">
                                                    <p class="font-medium">
                                                        QR Code e textos do verso do cartão
                                                    </p>
                                                    <p class="text-xs text-cyan-700 mt-1">
                                                        O QR Code é exibido no centro do verso do cartão dinâmico. Os
                                                        textos abaixo substituem os textos fixos ao lado do QR Code e
                                                        no rodapé do verso.
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between gap-4 p-4 rounded-xl border border-gray-200 bg-gray-50">
                                                <div class="text-sm">
                                                    <p class="font-medium text-gray-800">QR Code habilitado</p>
                                                    <p class="text-xs text-gray-500 mt-0.5">
                                                        Quando desativado, o verso do cartão exibe "QR Code desativado" no lugar da imagem.
                                                    </p>
                                                </div>
                                                <button type="button"
                                                    @click="getQrcodeForm(config).habilitado = !getQrcodeForm(config).habilitado"
                                                    :class="[
                                                        'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out',
                                                        getQrcodeForm(config).habilitado ? 'bg-indigo-600' : 'bg-gray-200',
                                                    ]">
                                                    <span :class="[
                                                        'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                                        getQrcodeForm(config).habilitado ? 'translate-x-5' : 'translate-x-0',
                                                    ]" />
                                                </button>
                                            </div>

                                            <div class="grid gap-2">
                                                <label class="text-xs font-medium text-gray-700">O que colocar no QR Code</label>
                                                <textarea v-model="getQrcodeForm(config).dados" rows="2"
                                                    placeholder="URL, texto ou dado que será codificado no QR Code"
                                                    class="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"></textarea>
                                                <p class="text-xs text-gray-400">
                                                    Se deixado em branco, é usado o link padrão configurado no sistema.
                                                </p>
                                            </div>

                                            <div class="grid gap-2">
                                                <label class="text-xs font-medium text-gray-700">Texto ao lado do QR Code</label>
                                                <input type="text" v-model="getQrcodeForm(config).texto_info"
                                                    placeholder="Solicite atendimento 24h"
                                                    class="flex h-10 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm" />
                                            </div>

                                            <div class="grid gap-2">
                                                <label class="text-xs font-medium text-gray-700">Texto de rodapé do verso</label>
                                                <textarea v-model="getQrcodeForm(config).rodape" rows="4"
                                                    placeholder="Uma linha por frase"
                                                    class="flex w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm"></textarea>
                                                <p class="text-xs text-gray-400">
                                                    Cada linha vira uma linha separada no cartão.
                                                </p>
                                            </div>

                                            <div class="flex justify-end pt-3 border-t border-gray-100">
                                                <button type="button" @click="saveQrcode(config)"
                                                    :disabled="savingKeys.has(config.key)"
                                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-cyan-600 text-white text-sm font-medium hover:bg-cyan-700 transition-colors shadow-sm cursor-pointer">
                                                    {{ savingKeys.has(config.key) ? "Salvando..." : "Salvar" }}
                                                </button>
                                            </div>
                                        </div>

                                        <!-- STATUS (TOGGLE) -->
                                        <div v-else-if="config.type === 'toggle'" class="space-y-4">
                                            <div
                                                class="flex items-start gap-3 p-4 rounded-xl border border-cyan-200 bg-cyan-50 text-cyan-800">
                                                <Info class="w-5 h-5 shrink-0 mt-0.5" />
                                                <div class="text-sm">
                                                    <p class="font-medium">
                                                        {{ config.label }}
                                                    </p>
                                                    <p class="text-xs text-cyan-700 mt-1">
                                                        {{ config.description }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-between gap-4">
                                                <p class="text-sm text-gray-600 flex-1">
                                                    Status atual do cartão dinâmico.
                                                </p>
                                                <button type="button" @click="toggleConfig(config)"
                                                    :disabled="savingKeys.has(config.key)" :class="[
                                                        'relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2',
                                                        config.value ? 'bg-cyan-600' : 'bg-gray-200',
                                                        savingKeys.has(config.key) ? 'opacity-50 cursor-not-allowed' : '',
                                                    ]">
                                                    <span :class="[
                                                        'pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out',
                                                        config.value ? 'translate-x-5' : 'translate-x-0',
                                                    ]" />
                                                </button>
                                            </div>
                                            <span :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium',
                                                config.value
                                                    ? 'bg-cyan-100 text-cyan-700 border border-cyan-200'
                                                    : 'bg-gray-100 text-gray-500 border border-gray-200',
                                            ]">
                                                <span :class="['w-1.5 h-1.5 rounded-full', config.value ? 'bg-cyan-500' : 'bg-gray-400']" />
                                                {{ config.value ? "Ativado" : "Desativado" }}
                                            </span>
                                        </div>

                                        <!-- FALLBACK -->
                                        <div v-else class="text-sm text-gray-500 bg-gray-50 rounded-lg p-3">
                                            Tipo de configuração ainda não implementado:
                                            <strong>{{ config.type }}</strong>
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
