<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import FormField from "@/Components/FormFields/FormField.vue";
import { showToast } from '@/Utils/toast';
import LeiModal from '@/Components/LeiModal.vue';
import {
    Send, Shield, AlertTriangle, CheckSquare
} from "lucide-vue-next";

const props = defineProps({
    form: { type: Object, required: true }
});

const showLeiModal = ref(false);

// ⭐ CORES: Primária = Fundo, Secundária = Botão
const primaryColor = computed(() => props.form.primary_color || '#06b6d4');   // Fundo (ciano)
const secondaryColor = computed(() => props.form.secondary_color || '#0891b2'); // Botão (azul escuro)

const formLogo = computed(() => props.form.logo?.url || null);

const formStyles = computed(() => ({
    '--form-primary': primaryColor.value,
    '--form-secondary': secondaryColor.value,
}));

const answers = ref({});
const submitting = ref(false);
const errors = ref({});
const acceptedTerms = ref(false);
const termsError = ref(false);

// Inicializar respostas
if (props.form.fields) {
    props.form.fields.forEach(field => {
        answers.value[field.id] = field.type === 'checkbox' ? [] : '';
    });
}

const validateTerms = () => {
    if (!acceptedTerms.value) {
        termsError.value = true;
        showToast('Você precisa aceitar a Política de Privacidade para continuar.', 'error');
        return false;
    }
    termsError.value = false;
    return true;
};

const submitForm = () => {
    if (!validateTerms()) return;

    const slug = props.form.slug || props.form.code || props.form.id;
    if (!slug) {
        showToast('Erro: Identificador do formulário não encontrado.', 'error');
        return;
    }

    submitting.value = true;
    errors.value = {};

    router.post(route('forms.public.store', { slug }), { answers: answers.value }, {
        onSuccess: () => showToast('Cadastro realizado com sucesso!', 'success'),
        onError: (e) => {
            errors.value = e;
            const errorMessages = [];
            Object.keys(e).forEach(key => {
                if (key.startsWith('answers.')) {
                    const fieldId = key.replace('answers.', '');
                    const field = props.form.fields.find(f => f.id == fieldId);
                    errorMessages.push(`${field?.label || 'Campo'}: ${Array.isArray(e[key]) ? e[key][0] : e[key]}`);
                }
            });
            showToast(errorMessages[0] || 'Erro ao enviar cadastro. Verifique os campos.', 'error');
        },
        onFinish: () => submitting.value = false
    });
};

const progress = computed(() => {
    if (!props.form.fields) return 0;
    const total = props.form.fields.length;
    const filled = props.form.fields.filter(f => {
        const val = answers.value[f.id];
        return Array.isArray(val) ? val.length > 0 : val !== '' && val != null;
    }).length;
    return total > 0 ? Math.round((filled / total) * 100) : 0;
});
</script>

<template>

    <Head :title="form.title || 'Cadastro'" />

    <div class="min-h-screen" :style="formStyles">
        <!-- Layout Split-Screen -->
        <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">

            <!-- LADO ESQUERDO: Logo (Fundo Branco) -->
            <div class="bg-white flex flex-col items-center justify-center p-8 lg:p-16 relative">
                <div class="max-w-md w-full text-center">
                    <!-- Logo -->
                    <div v-if="formLogo" class="mb-8">
                        <img :src="formLogo" :alt="form.title" class="max-h-40 w-auto object-contain mx-auto" />
                    </div>

                    <!-- Logo Padrão (Integral Medben style) -->
                    <div v-else class="flex items-center justify-center gap-4 mb-8">
                        <div class="relative">
                            <!-- Ícone do coração com check -->
                            <div class="w-20 h-16 border-4 rounded-2xl flex items-center justify-center relative bg-white"
                                :style="{ borderColor: primaryColor }">
                                <div class="absolute -bottom-2 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[10px] border-r-[10px] border-t-[10px] border-l-transparent border-r-transparent"
                                    :style="{ borderTopColor: primaryColor }"></div>
                                <svg class="w-10 h-10" :style="{ color: secondaryColor }" fill="currentColor"
                                    viewBox="0 0 24 24">
                                    <path
                                        d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z" />
                                    <path class="text-white" d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2"
                                        fill="none" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                            </div>
                        </div>
                        <div class="text-left">
                            <div class="text-2xl font-bold text-gray-900 tracking-wider">INTEGRAL</div>
                            <div class="text-3xl font-bold tracking-widest" :style="{ color: primaryColor }">MEDBEN
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LADO DIREITO: Formulário (Fundo = Cor Primária) -->
            <div class="flex flex-col justify-center p-6 lg:p-12 overflow-y-auto"
                :style="{ backgroundColor: primaryColor }">
                <div class="max-w-lg w-full mx-auto space-y-6">

                    <!-- Header -->
                    <div class="text-white mb-8">
                        <h1 v-if="form.title" class="text-4xl font-bold mb-2">
                            {{ form.title }}
                        </h1>
                        <p v-if="form.description" class="text-white/90 text-lg">
                            {{ form.description }}
                        </p>
                    </div>

                    <!-- Formulário -->
                    <form @submit.prevent="submitForm" class="space-y-5">

                        <!-- Campos -->
                        <div v-for="field in form.fields" :key="field.id" class="space-y-2">
                            <label class="block text-white text-sm font-medium">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-300 ml-1">*</span>
                            </label>

                            <div class="relative">
                                <input
                                    v-if="field.type === 'text' || field.type === 'email' || field.type === 'tel' || field.type === 'date'"
                                    :type="field.type === 'date' ? 'text' : field.type" v-model="answers[field.id]"
                                    :placeholder="field.placeholder || getPlaceholder(field)"
                                    class="w-full bg-[#374151] text-white placeholder-gray-400 rounded-lg px-4 py-3 border-0 focus:ring-2 focus:ring-white/50 focus:bg-[#374151] transition-all"
                                    :class="{ 'ring-2 ring-red-400': errors[`answers.${field.id}`] }" />

                                <FormField v-else :field="field" v-model="answers[field.id]"
                                    class="w-full bg-[#374151] text-white rounded-lg border-0 focus:ring-2 focus:ring-white/50"
                                    :class="{ 'ring-2 ring-red-400': errors[`answers.${field.id}`] }" />
                            </div>

                            <p v-if="errors[`answers.${field.id}`]"
                                class="text-red-200 text-sm flex items-center gap-1">
                                <AlertTriangle class="w-4 h-4" />
                                {{ errors[`answers.${field.id}`] }}
                            </p>
                        </div>

                        <!-- Checkbox de Termos -->
                        <div class="pt-2">
                            <div class="flex items-start gap-3 p-4 rounded-lg border-2 transition-all duration-200"
                                :class="{
                                    'border-red-400 bg-red-500/20': termsError,
                                    'border-white/30 bg-white/10': !termsError
                                }">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="checkbox" id="terms" v-model="acceptedTerms"
                                        @change="termsError = false"
                                        class="w-5 h-5 rounded border-0 cursor-pointer bg-white/20 focus:ring-white"
                                        :style="{ accentColor: primaryColor }" />
                                </div>
                                <div class="flex-1">
                                    <label for="terms" class="text-white text-sm cursor-pointer select-none">
                                        <span>Li e aceito a </span>
                                        <button v-if="form.lei" type="button"
                                            class="underline font-semibold hover:text-white/80 transition-colors"
                                            @click.stop="showLeiModal = true">
                                            {{ form.lei.title }}
                                        </button>
                                        <a v-else href="/politica-de-privacidade" target="_blank"
                                            class="underline font-semibold hover:text-white/80 transition-colors"
                                            @click.stop>
                                            Política de Privacidade
                                        </a>
                                        <span class="text-red-300 font-bold ml-1">*</span>
                                    </label>

                                    <p v-if="termsError" class="text-red-200 text-sm mt-1 flex items-center gap-1">
                                        <AlertTriangle class="w-4 h-4" />
                                        Você deve aceitar os termos para enviar.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ⭐ BOTÃO COM COR SECUNDÁRIA -->
                        <button type="submit" :disabled="submitting"
                            class="w-full text-white font-bold text-lg py-4 px-6 rounded-lg transition-all duration-200 disabled:opacity-60 disabled:cursor-not-allowed transform hover:scale-[1.02] active:scale-[0.98] shadow-lg hover:shadow-xl"
                            :style="{
                                backgroundColor: secondaryColor,
                                boxShadow: `0 10px 25px -5px ${secondaryColor}66`
                            }" @mouseover="e => !submitting && (e.currentTarget.style.filter = 'brightness(1.1)')"
                            @mouseleave="e => !submitting && (e.currentTarget.style.filter = 'brightness(1)')">
                            <span v-if="!submitting">Enviar Resposta</span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                        stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor"
                                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                    </path>
                                </svg>
                                Enviando...
                            </span>
                        </button>

                        <!-- Mensagem de segurança -->
                        <!-- <div class="bg-emerald-500 rounded-lg p-4 flex items-start gap-3">
                            <Shield class="w-5 h-5 text-white flex-shrink-0 mt-0.5" />
                            <p class="text-white text-sm leading-relaxed">
                                Seus dados estão protegidos e serão usados somente para o cadastro no clube de
                                benefícios
                            </p>
                        </div> -->
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal da Lei/Política -->
    <LeiModal :lei="form.lei" :show="showLeiModal" @close="showLeiModal = false" :item="fo" />
</template>

<script>
// Helper para placeholders padrão
function getPlaceholder(field) {
    const placeholders = {
        'Nome Completo': 'Digite seu nome completo',
        'CPF': '000.000.000-00',
        'WhatsApp': '(00) 00000-0000',
        'E-mail': 'seu@email.com',
        'Data de Nascimento': 'dd/mm/aaaa',
    };
    return placeholders[field.label] || `Digite ${field.label.toLowerCase()}`;
}
</script>

<style scoped>
/* Scrollbar personalizada */
.overflow-y-auto::-webkit-scrollbar {
    width: 8px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 4px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

/* Animação suave nos inputs */
input {
    transition: all 0.2s ease-in-out;
}

input:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}
</style>
