<script setup>
import TenantAdminLayout from '@/Layouts/TenantAdminLayout.vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Upload, ImageIcon, X, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
    logo: {
        type: String,
        default: null,
    },
});

const page = usePage();
const successMessage = ref(null);

const form = useForm({
    logo: null,
});

const preview = ref(props.logo);

const handleFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo = file;
        preview.value = URL.createObjectURL(file);
    }
};

const removeLogo = () => {
    form.logo = null;
    preview.value = props.logo; // Volta para o logo salvo ou null
};

const submit = () => {
    form.post(route('tenant.configuracao.logo.store'), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = 'Logo atualizado com sucesso!';
            form.reset();
            // Atualiza o preview com a nova URL do servidor
            if (page.props.logo) {
                preview.value = page.props.logo;
            }
        },
    });
};
</script>

<template>
    <TenantAdminLayout :tenant-name="$page.props.tenantName" :tenant-photo="$page.props.logo">
        <template #header>
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Configurações de Logo</h1>
                <p class="text-sm text-gray-500 mt-1">Personalize a identidade visual do seu tenant</p>
            </div>
        </template>

        <!-- Mensagem de sucesso -->
        <transition enter-active-class="transition ease-out duration-300" enter-from-class="opacity-0 -translate-y-2"
            enter-to-class="opacity-100 translate-y-0">
            <div v-if="successMessage"
                class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4 flex items-center gap-3">
                <CheckCircle class="w-5 h-5 text-green-600" />
                <p class="text-sm text-green-800 font-medium">{{ successMessage }}</p>
            </div>
        </transition>

        <div class="max-w-2xl">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <form @submit.prevent="submit" class="space-y-6">

                    <!-- Preview do Logo -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Logo Atual</label>

                        <div v-if="preview" class="relative inline-block group">
                            <img :src="preview" alt="Logo do tenant"
                                class="h-24 object-contain rounded-lg border border-gray-200 bg-white" />
                            <button type="button" @click="removeLogo"
                                class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full flex items-center justify-center hover:bg-red-600 transition-colors opacity-0 group-hover:opacity-100"
                                title="Remover logo">
                                <X class="w-3 h-3" />
                            </button>
                        </div>

                        <div v-else
                            class="h-24 w-48 bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg flex items-center justify-center">
                            <div class="text-center">
                                <ImageIcon class="w-8 h-8 text-gray-400 mx-auto" />
                                <span class="text-xs text-gray-500 mt-1 block">Nenhum logo cadastrado</span>
                            </div>
                        </div>
                    </div>

                    <!-- Upload -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Novo Logo</label>
                        <div class="flex items-center gap-4">
                            <label
                                class="flex items-center gap-2 px-4 py-2.5 bg-cyan-50 text-cyan-700 rounded-lg hover:bg-cyan-100 cursor-pointer transition-colors border border-cyan-200">
                                <Upload class="w-4 h-4" />
                                <span class="text-sm font-medium">Selecionar arquivo</span>
                                <input type="file" accept="image/*" class="hidden" @change="handleFileChange" />
                            </label>
                            <span v-if="form.logo" class="text-sm text-gray-600 font-medium">
                                {{ form.logo.name }}
                            </span>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">PNG, JPG, SVG ou WEBP. Máximo 2MB.</p>
                        <p v-if="form.errors.logo" class="text-sm text-red-600 mt-1">{{ form.errors.logo }}</p>
                    </div>

                    <!-- Ações -->
                    <div class="flex items-center gap-3 pt-4 border-t border-gray-100">
                        <button type="submit" :disabled="form.processing || !form.logo"
                            class="px-4 py-2.5 bg-cyan-600 text-white text-sm font-medium rounded-lg hover:bg-cyan-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex items-center gap-2">
                            <span v-if="form.processing">Salvando...</span>
                            <span v-else>Salvar Logo</span>
                        </button>

                        <Link :href="route('tenant.dashboard')"
                            class="px-4 py-2.5 text-gray-600 text-sm font-medium hover:text-gray-900 transition-colors">
                            Voltar ao Dashboard
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </TenantAdminLayout>
</template>
