<script setup>
import { ref, computed, watch } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import FormField from "@/Components/FormFields/FormField.vue";
import { showToast } from '@/Utils/toast';
import LeiModal from '@/Components/LeiModal.vue';
import {
    Send, Clock, Shield, FileText, AlertTriangle,
    Lock, PauseCircle, XCircle, CheckSquare, ChevronDown
} from "lucide-vue-next";

const props = defineProps({
    form: { type: Object, required: true }
});

const showLeiModal = ref(false);

const primaryColor = computed(() => props.form.primary_color || '#06b6d4');
const secondaryColor = computed(() => props.form.secondary_color || '#0891b2');
const buttonColor = computed(() => props.form.secondary_color || '#ef4444');

const layoutMode = computed(() => props.form.layout || 'split');

const formLogo = computed(() => props.form.logo?.url || null);
const logoPosicao = computed(() => props.form.logo?.posicao || 'centro');
const formTitle = computed(() => props.form.title || 'Cadastro');
const formDescription = computed(() => props.form.description || 'Preencha os dados abaixo para se cadastrar no clube de benefícios');
const submitButtonText = computed(() => props.form.btn_confirmar_descricao || 'ENVIAR CADASTRO');
const securityMessage = computed(() => props.form.sub_descricao || null);

const logoContainerClasses = computed(() => {
    const classes = { esquerda: 'text-left', centro: 'text-center', direita: 'text-right' };
    return classes[logoPosicao.value] || classes.centro;
});

const logoImageClasses = computed(() => {
    const classes = { esquerda: 'mr-auto', centro: 'mx-auto', direita: 'ml-auto' };
    return classes[logoPosicao.value] || classes.centro;
});

const formStyles = computed(() => ({
    '--form-primary': primaryColor.value,
    '--form-secondary': secondaryColor.value,
    '--form-button': buttonColor.value,
}));

// ⭐ FUNÇÕES DE FORMATAÇÃO VISUAL
const formatCPF = (value) => {
    if (!value) return '';
    const numbers = value.replace(/\D/g, '').substring(0, 11);
    return numbers.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

const formatDate = (value) => {
    if (!value) return '';
    const numbers = value.replace(/\D/g, '').substring(0, 8);
    if (numbers.length <= 2) return numbers;
    if (numbers.length <= 4) return numbers.replace(/(\d{2})/, '$1/');
    return numbers.replace(/(\d{2})(\d{2})(\d{0,4})/, '$1/$2/$3');
};

// ⭐ DETECÇÃO DE CAMPOS
const isCPFField = (field) => field.is_cpf || false;
const isDateField = (field) => field.is_date || false;

// Status do formulário
const isActive = computed(() => props.form.status === 'ativo');
const isExpired = computed(() => props.form.expires_at ? new Date(props.form.expires_at) <= new Date() : false);
const isLimitReached = computed(() => props.form.response_limit ? props.form.responses_count >= props.form.response_limit : false);

const availabilityStatus = computed(() => {
    if (isLimitReached.value) return 'limite';
    if (isExpired.value) return 'expirado';
    if (!isActive.value) return props.form.status || 'inativo';
    return 'ativo';
});

const statusConfig = computed(() => {
    const configs = {
        ativo: { icon: null, title: '', message: '', color: 'cyan', canSubmit: true },
        rascunho: { icon: Lock, title: 'Formulário em Edição', message: 'Este formulário está em rascunho.', color: 'gray', canSubmit: false },
        pausado: { icon: PauseCircle, title: 'Formulário Pausado', message: 'Este formulário está pausado.', color: 'yellow', canSubmit: false },
        encerrado: { icon: XCircle, title: 'Formulário Encerrado', message: 'Este formulário foi encerrado.', color: 'red', canSubmit: false },
        expirado: { icon: Clock, title: 'Formulário Expirado', message: 'O prazo para respostas encerrou.', color: 'orange', canSubmit: false },
        limite: { icon: AlertTriangle, title: 'Limite Atingido', message: 'Número máximo de respostas atingido.', color: 'purple', canSubmit: false },
    };
    return configs[availabilityStatus.value] || configs.rascunho;
});

const answers = ref({});
const rawAnswers = ref({});
const submitting = ref(false);
const errors = ref({});
const acceptedTerms = ref(false);
const termsError = ref(false);

// ⭐ INICIALIZA RESPOSTAS COM WATCHERS
if (statusConfig.value.canSubmit && props.form.fields) {
    props.form.fields.forEach(field => {
        answers.value[field.id] = field.type === 'checkbox' ? [] : '';
        rawAnswers.value[field.id] = field.type === 'checkbox' ? [] : '';

        // Watcher para CPF e Data
        if (isCPFField(field) || isDateField(field)) {
            watch(() => answers.value[field.id], (newValue) => {
                if (newValue && typeof newValue === 'string') {
                    const numbersOnly = newValue.replace(/\D/g, '');

                    if (isCPFField(field)) {
                        // ⭐ CPF: envia números puros (12345678901)
                        rawAnswers.value[field.id] = numbersOnly.substring(0, 11);
                        answers.value[field.id] = formatCPF(newValue);
                    } else if (isDateField(field)) {
                        // ⭐ DATA: envia formato brasileiro (DD/MM/YYYY)
                        const formatted = formatDate(newValue);
                        rawAnswers.value[field.id] = formatted; // Envia DD/MM/YYYY
                        answers.value[field.id] = formatted;    // Visual também DD/MM/YYYY
                    }
                } else {
                    rawAnswers.value[field.id] = newValue;
                }
            });
        } else {
            // Campos normais
            watch(() => answers.value[field.id], (newValue) => {
                rawAnswers.value[field.id] = newValue;
            });
        }
    });
}

const validateTerms = () => {
    if (!acceptedTerms.value) {
        termsError.value = true;
        showToast('Você precisa aceitar os termos para continuar.', 'error');
        return false;
    }
    termsError.value = false;
    return true;
};

const submitForm = () => {
    if (!statusConfig.value.canSubmit) {
        showToast('Este formulário não pode receber respostas no momento.', 'error');
        return;
    }
    if (!validateTerms()) return;

    const slug = props.form.slug || props.form.code || props.form.id;
    if (!slug) {
        showToast('Erro: Identificador do formulário não encontrado.', 'error');
        return;
    }

    submitting.value = true;
    errors.value = {};

    // ⭐ ENVIA rawAnswers (CPF = números, Data = DD/MM/YYYY)
    router.post(route('forms.public.store', { slug }), { 
        answers: rawAnswers.value, 
        accepted_terms: acceptedTerms.value 
    }, {
        onSuccess: () => showToast('Cadastro realizado com sucesso!', 'success'),
        onError: (e) => {
            errors.value = e;
            let errorMessages = [];
            Object.keys(e).forEach(key => {
                if (key.startsWith('answers.')) {
                    const fieldId = key.replace('answers.', '');
                    const field = props.form.fields.find(f => f.id == fieldId);
                    errorMessages.push(`${field?.label || 'Campo'}: ${Array.isArray(e[key]) ? e[key][0] : e[key]}`);
                }
            });
            showToast(errorMessages[0] || 'Erro ao enviar cadastro.', 'error');
        },
        onFinish: () => submitting.value = false
    });
};

const progress = computed(() => {
    if (!statusConfig.value.canSubmit || !props.form.fields) return 0;
    const total = props.form.fields.length;
    const filled = props.form.fields.filter(f => {
        const val = rawAnswers.value[f.id];
        return Array.isArray(val) ? val.length > 0 : val !== '' && val != null;
    }).length;
    return total > 0 ? Math.round((filled / total) * 100) : 0;
});
</script>

<template>

    <Head :title="formTitle" />

    <!-- LAYOUT SPLIT-SCREEN -->
    <div v-if="layoutMode === 'split'" class="min-h-screen" :style="formStyles">
        <div class="grid grid-cols-1 lg:grid-cols-2 min-h-screen">
            <!-- LADO ESQUERDO: Logo -->
            <div class="bg-white flex flex-col items-center justify-center p-8 lg:p-16 relative">
                <div class="max-w-md w-full text-center">
                    <div v-if="formLogo" class="mb-8 group cursor-pointer"><img :src="formLogo" :alt="formTitle"
                            class="w-full h-full object-contain" />
                    </div>
                    <div v-else class="mb-8">
                        <div class="inline-flex items-center justify-center w-24 h-24 rounded-2xl mb-4 shadow-xl"
                            :style="{ background: `linear-gradient(135deg, ${primaryColor}, ${secondaryColor})` }">
                            <FileText class="w-12 h-12 text-white" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- LADO DIREITO: Formulário -->
            <div class="flex flex-col justify-center p-6 lg:p-12 xl:p-16 overflow-y-auto"
                :style="{ backgroundColor: primaryColor }">
                <div class="max-w-md w-full mx-auto space-y-5">
                    <div class="text-white mb-2">
                        <h1 class="text-3xl font-bold mb-3">{{ formTitle }}</h1>
                        <p v-if="formDescription" class="text-white/80 text-sm leading-relaxed">{{ formDescription }}
                        </p>
                    </div>

                    <form v-if="statusConfig.canSubmit" @submit.prevent="submitForm" class="space-y-4">
                        <div v-for="field in form.fields" :key="field.id" class="space-y-1.5">
                            <label class="block text-white text-xs font-medium">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-300 ml-0.5">*</span>
                            </label>

                            <div class="relative">
                                <!-- Select -->
                                <select v-if="field.type === 'select'" v-model="answers[field.id]"
                                    class="w-full bg-gray-800/90 text-white text-sm rounded-lg px-4 py-3 border-0 focus:ring-2 focus:ring-white/30 transition-all appearance-none cursor-pointer placeholder-gray-400"
                                    :class="{ 'ring-2 ring-red-400': errors[`answers.${field.id}`] }">
                                    <option value="" disabled class="text-gray-500">
                                        {{ field.placeholder || 'Selecione' }}
                                    </option>
                                    <option v-for="option in field.options || []" :key="option" :value="option"
                                        class="bg-gray-800">
                                        {{ option }}
                                    </option>
                                </select>

                                <!-- ⭐ INPUT CPF -->
                                <input v-else-if="isCPFField(field)" type="text" v-model="answers[field.id]"
                                    :placeholder="field.placeholder || '000.000.000-00'" maxlength="14"
                                    inputmode="numeric"
                                    class="w-full bg-gray-800/90 text-white text-sm rounded-lg px-4 py-3 border-0 focus:ring-2 focus:ring-white/30 transition-all placeholder-gray-400 font-mono"
                                    :class="{ 'ring-2 ring-red-400': errors[`answers.${field.id}`] }" />

                                <!-- ⭐ INPUT DATA (DD/MM/AAAA) -->
                                <input v-else-if="isDateField(field)" type="text" v-model="answers[field.id]"
                                    :placeholder="field.placeholder || 'DD/MM/AAAA'" maxlength="10" inputmode="numeric"
                                    class="w-full bg-gray-800/90 text-white text-sm rounded-lg px-4 py-3 border-0 focus:ring-2 focus:ring-white/30 transition-all placeholder-gray-400 font-mono"
                                    :class="{ 'ring-2 ring-red-400': errors[`answers.${field.id}`] }" />

                                <!-- Input padrão -->
                                <input v-else :type="field.type === 'date' ? 'text' : (field.type || 'text')"
                                    v-model="answers[field.id]" :placeholder="field.placeholder || ''"
                                    class="w-full bg-gray-800/90 text-white text-sm rounded-lg px-4 py-3 border-0 focus:ring-2 focus:ring-white/30 transition-all placeholder-gray-400"
                                    :class="{ 'ring-2 ring-red-400': errors[`answers.${field.id}`] }" />

                                <ChevronDown v-if="field.type === 'select'"
                                    class="absolute right-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400 pointer-events-none" />
                            </div>

                            <p v-if="errors[`answers.${field.id}`]"
                                class="text-red-200 text-xs flex items-center gap-1">
                                <AlertTriangle class="w-3 h-3" />
                                {{ errors[`answers.${field.id}`] }}
                            </p>
                        </div>

                        <!-- Checkbox de Termos -->
                        <div class="pt-2">
                            <div class="flex items-start gap-3">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="checkbox" id="terms-split" v-model="acceptedTerms"
                                        @change="termsError = false"
                                        class="w-4 h-4 rounded border-0 cursor-pointer bg-white/20 focus:ring-white/50"
                                        :style="{ accentColor: primaryColor }" />
                                </div>
                                <div class="flex-1">
                                    <label for="terms-split" class="text-white text-xs cursor-pointer select-none">
                                        <span>Li e aceito a </span>
                                        <button v-if="form.lei" type="button"
                                            class="underline font-medium hover:text-white/80"
                                            @click.stop="showLeiModal = true">
                                            {{ form.lei.title }}
                                        </button>
                                        <a v-else href="/politica-de-privacidade" target="_blank"
                                            class="underline font-medium hover:text-white/80" @click.stop>
                                            Política de Privacidade
                                        </a>
                                        <span class="text-red-300 font-bold ml-0.5">*</span>
                                    </label>

                                    <p v-if="termsError" class="text-red-200 text-xs mt-1 flex items-center gap-1">
                                        <AlertTriangle class="w-3 h-3" />
                                        Você deve aceitar os termos.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botão -->
                        <button type="submit" :disabled="submitting"
                            class="w-full text-white font-bold text-sm py-3.5 px-6 rounded-lg transition-all duration-200 disabled:opacity-60 uppercase tracking-wider shadow-lg mt-2"
                            :style="{
                                backgroundColor: buttonColor,
                                boxShadow: `0 4px 14px ${buttonColor}66`
                            }" @mouseover="e => !submitting && (e.currentTarget.style.filter = 'brightness(1.1)')"
                            @mouseleave="e => !submitting && (e.currentTarget.style.filter = 'brightness(1)')">
                            <span v-if="!submitting">{{ submitButtonText }}</span>
                            <span v-else class="flex items-center justify-center gap-2">
                                <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
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

                        <!-- Box de Segurança -->
                        <div v-if="securityMessage" class="rounded-lg p-3.5 flex items-start gap-2.5 mt-3"
                            :style="{ backgroundColor: 'rgba(0, 0, 0, 0.2)' }">
                            <span class="text-sm">🔒</span>
                            <p class="text-white/90 text-xs leading-relaxed">
                                {{ securityMessage }}
                            </p>
                        </div>
                    </form>

                    <!-- Status não ativo -->
                    <div v-else class="bg-white/95 rounded-2xl p-8 text-center">
                        <div class="mx-auto flex items-center justify-center w-20 h-20 rounded-full mb-4 bg-gray-100"
                            :class="`text-${statusConfig.color}-500`">
                            <component :is="statusConfig.icon" class="w-10 h-10" />
                        </div>
                        <h2 class="text-2xl font-bold mb-2 text-gray-800">{{ statusConfig.title }}</h2>
                        <p class="text-gray-600">{{ statusConfig.message }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LAYOUT CENTRALIZADO -->
    <div v-else class="min-h-screen bg-gray-50 py-8 px-4" :style="formStyles">
        <div class="max-w-2xl mx-auto">
            <div class="mb-8" :class="logoContainerClasses">
                <div v-if="formLogo" class="mb-4">
                    <img :src="formLogo" :alt="formTitle"
                        :class="['max-h-24 w-auto object-contain rounded-lg', logoImageClasses]" />
                </div>
                <div v-else class="text-center">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-xl mb-4"
                        :style="{ backgroundColor: primaryColor }">
                        <FileText class="w-6 h-6 text-white" />
                    </div>
                </div>
                <p class="text-sm text-gray-500">Formulário Online</p>
            </div>

            <!-- Status não ativo -->
            <div v-if="!statusConfig.canSubmit" class="bg-white rounded-2xl shadow-sm border-2 overflow-hidden"
                :class="`border-${statusConfig.color}-200`">
                <div class="p-8 text-center" :class="`bg-${statusConfig.color}-50`">
                    <div class="mx-auto flex items-center justify-center w-20 h-20 rounded-full mb-4 bg-white shadow-sm"
                        :class="`text-${statusConfig.color}-500`">
                        <component :is="statusConfig.icon" class="w-10 h-10" />
                    </div>
                    <h1 class="text-2xl font-bold mb-2" :class="`text-${statusConfig.color}-800`">
                        {{ statusConfig.title }}
                    </h1>
                    <p class="text-lg text-gray-700 font-medium">{{ formTitle }}</p>
                </div>
                <div class="p-8 text-center">
                    <div class="rounded-lg p-4 mb-6"
                        :class="`bg-${statusConfig.color}-50 border border-${statusConfig.color}-200`">
                        <p class="text-gray-700">{{ statusConfig.message }}</p>
                    </div>
                </div>
            </div>

            <!-- Formulário Ativo -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="p-6 text-white"
                    :style="{ background: `linear-gradient(to right, ${primaryColor}, ${secondaryColor})` }">
                    <h1 class="text-2xl font-bold">{{ formTitle }}</h1>
                    <p v-if="formDescription" class="mt-2 opacity-90 text-sm">{{ formDescription }}</p>
                </div>

                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">Progresso</span>
                        <span class="font-medium" :style="{ color: primaryColor }">{{ progress }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300"
                            :style="{ width: progress + '%', backgroundColor: primaryColor }"></div>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <div v-for="field in form.fields" :key="field.id" class="space-y-2">

                        <!-- ⭐ CPF -->
                        <template v-if="isCPFField(field)">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                            </label>
                            <input type="text" v-model="answers[field.id]"
                                :placeholder="field.placeholder || '000.000.000-00'" maxlength="14" inputmode="numeric"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm font-mono"
                                :class="{ 'border-red-300': errors[`answers.${field.id}`] }" />
                            <p v-if="errors[`answers.${field.id}`]"
                                class="text-sm text-red-600 flex items-center gap-1">
                                <AlertTriangle class="w-4 h-4" />
                                {{ errors[`answers.${field.id}`] }}
                            </p>
                        </template>

                        <!-- ⭐ DATA (DD/MM/AAAA) -->
                        <template v-else-if="isDateField(field)">
                            <label class="block text-sm font-medium text-gray-700">
                                {{ field.label }}
                                <span v-if="field.required" class="text-red-500 ml-0.5">*</span>
                            </label>
                            <input type="text" v-model="answers[field.id]"
                                :placeholder="field.placeholder || 'DD/MM/AAAA'" maxlength="10" inputmode="numeric"
                                class="w-full rounded-lg border-gray-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 text-sm font-mono"
                                :class="{ 'border-red-300': errors[`answers.${field.id}`] }" />
                            <p v-if="errors[`answers.${field.id}`]"
                                class="text-sm text-red-600 flex items-center gap-1">
                                <AlertTriangle class="w-4 h-4" />
                                {{ errors[`answers.${field.id}`] }}
                            </p>
                        </template>

                        <!-- Campos normais -->
                        <template v-else>
                            <FormField :field="field" v-model="answers[field.id]"
                                :class="{ 'border-red-300': errors[`answers.${field.id}`] }" />
                            <p v-if="errors[`answers.${field.id}`]"
                                class="text-sm text-red-600 flex items-center gap-1">
                                <AlertTriangle class="w-4 h-4" />
                                {{ errors[`answers.${field.id}`] }}
                            </p>
                        </template>
                    </div>

                    <!-- Checkbox -->
                    <div class="pt-4 border-t border-gray-200">
                        <div class="flex items-start gap-3 p-4 rounded-lg border-2 transition-all duration-200" :style="{
                            borderColor: termsError ? '#FCA5A5' : acceptedTerms ? primaryColor + '99' : '#E5E7EB',
                            backgroundColor: termsError ? '#FEF2F2' : acceptedTerms ? primaryColor + '0D' : '#F9FAFB',
                        }">
                            <div class="flex-shrink-0 mt-0.5">
                                <input type="checkbox" id="terms" v-model="acceptedTerms" @change="termsError = false"
                                    class="w-5 h-5 rounded border-gray-300 cursor-pointer"
                                    :style="{ accentColor: primaryColor }" :class="{ 'border-red-400': termsError }" />
                            </div>
                            <div class="flex-1">
                                <label for="terms" class="text-sm text-gray-700 cursor-pointer select-none">
                                    <span class="font-medium">Li e concordo com </span>
                                    <button v-if="form.lei" type="button" class="underline font-medium hover:opacity-75"
                                        :style="{ color: primaryColor }" @click.stop="showLeiModal = true">
                                        {{ form.lei.title }}
                                    </button>
                                    <template v-else>
                                        <a href="/termos-de-uso" target="_blank"
                                            class="underline font-medium hover:opacity-75"
                                            :style="{ color: primaryColor }">Termos de uso</a>
                                        <span class="font-medium"> e </span>
                                        <a href="/politica-de-privacidade" target="_blank"
                                            class="underline font-medium hover:opacity-75"
                                            :style="{ color: primaryColor }">Política de Privacidade</a>
                                    </template>
                                    <span class="text-red-500 font-bold">*</span>
                                </label>
                                <p v-if="termsError" class="text-sm text-red-600 mt-1 flex items-center gap-1">
                                    <AlertTriangle class="w-4 h-4" />
                                    Você deve aceitar os termos.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Botão -->
                    <div class="pt-2">
                        <button type="submit" :disabled="submitting"
                            class="w-full flex items-center justify-center gap-2 py-3 px-6 rounded-lg text-white font-medium text-sm transition-all duration-200 disabled:opacity-60 hover:scale-[1.02]"
                            :style="{
                                backgroundColor: primaryColor,
                                boxShadow: `0 4px 14px ${primaryColor}40`
                            }"
                            @mouseover="e => !submitting && (e.currentTarget.style.backgroundColor = secondaryColor)"
                            @mouseleave="e => !submitting && (e.currentTarget.style.backgroundColor = primaryColor)">
                            <Send v-if="!submitting" class="w-4 h-4" />
                            <span>{{ submitting ? 'Enviando...' : submitButtonText }}</span>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-8 text-center space-y-2">
                <div class="flex items-center justify-center gap-4 text-sm text-gray-500">
                    <span class="flex items-center gap-1">
                        <Shield class="w-4 h-4" /> Dados protegidos
                    </span>
                    <span class="flex items-center gap-1">
                        <Clock class="w-4 h-4" /> Resposta rápida
                    </span>
                </div>
                <p class="text-xs text-gray-400">Powered by FormSystem</p>
            </div>
        </div>
    </div>

    <LeiModal :lei="form.lei" :show="showLeiModal" @close="showLeiModal = false" :primary-color="primaryColor"
        :secondary-color="secondaryColor" />
</template>

<style scoped>
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

input,
select,
textarea {
    transition: all 0.2s ease-in-out;
}

input:focus,
select:focus,
textarea:focus {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
}

select {
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

.font-mono {
    font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
}
</style>
