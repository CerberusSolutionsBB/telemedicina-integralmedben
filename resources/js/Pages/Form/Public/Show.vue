<script setup>
import { ref, computed } from "vue";
import { Head, router } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import FormField from "@/Components/FormFields/FormField.vue";
import { showToast } from '@/Utils/toast';
import {
    Send,
    Clock,
    Shield,
    FileText,
    AlertTriangle,
    Lock,
    PauseCircle,
    XCircle
} from "lucide-vue-next";

const props = defineProps({
    form: {
        type: Object,
        required: true
    }
});

// ⭐ DEBUG: Verifique se o slug existe
console.log('Form data:', props.form);
console.log('Form slug:', props.form?.slug);

const isActive = computed(() => {
    return props.form.status === 'ativo';
});

const isExpired = computed(() => {
    if (!props.form.expires_at) return false;
    return new Date(props.form.expires_at) <= new Date();
});

const isLimitReached = computed(() => {
    if (!props.form.response_limit) return false;
    return props.form.responses_count >= props.form.response_limit;
});

const availabilityStatus = computed(() => {
    if (isLimitReached.value) return 'limite';
    if (isExpired.value) return 'expirado';
    if (!isActive.value) return props.form.status || 'inativo';
    return 'ativo';
});

const statusConfig = computed(() => {
    const configs = {
        ativo: {
            icon: null,
            title: '',
            message: '',
            color: 'cyan',
            canSubmit: true
        },
        rascunho: {
            icon: Lock,
            title: 'Formulário em Edição',
            message: 'Este formulário está em rascunho e ainda não está disponível para respostas.',
            color: 'gray',
            canSubmit: false
        },
        pausado: {
            icon: PauseCircle,
            title: 'Formulário Pausado',
            message: 'Este formulário está pausado temporariamente e não pode receber respostas no momento.',
            color: 'yellow',
            canSubmit: false
        },
        encerrado: {
            icon: XCircle,
            title: 'Formulário Encerrado',
            message: 'Este formulário foi encerrado e não aceita mais respostas.',
            color: 'red',
            canSubmit: false
        },
        expirado: {
            icon: Clock,
            title: 'Formulário Expirado',
            message: 'O prazo para respostas deste formulário encerrou.',
            color: 'orange',
            canSubmit: false
        },
        limite: {
            icon: AlertTriangle,
            title: 'Limite Atingido',
            message: 'Este formulário atingiu o número máximo de respostas permitidas.',
            color: 'purple',
            canSubmit: false
        }
    };
    return configs[availabilityStatus.value] || configs.rascunho;
});

const answers = ref({});
const submitting = ref(false);
const errors = ref({});

// Inicializa respostas se ativo
if (statusConfig.value.canSubmit && props.form.fields) {
    props.form.fields.forEach(field => {
        if (field.type === 'checkbox') {
            answers.value[field.id] = [];
        } else {
            answers.value[field.id] = '';
        }
    });
}

const submitForm = () => {
    if (!statusConfig.value.canSubmit) {
        showToast('Este formulário não pode receber respostas no momento.', 'error');
        return;
    }

    // ⭐ VERIFICAÇÃO: Garante que o slug existe
    const slug = props.form.slug || props.form.code || props.form.id;

    if (!slug) {
        console.error('Slug não encontrado:', props.form);
        showToast('Erro: Identificador do formulário não encontrado.', 'error');
        return;
    }

    console.log('Enviando para slug:', slug); // Debug

    submitting.value = true;
    errors.value = {};

    // ⭐ USA O SLUG CORRETO NA ROTA
    router.post(route('forms.public.store', { slug: slug }), {
        answers: answers.value
    }, {
        onSuccess: () => {
            showToast('Formulário respondido com sucesso!', 'success');
        },
        onError: (e) => {
            errors.value = e;
            console.error('Erros:', e);

            let errorMessages = [];

            if (e.status) errorMessages.push(e.status);

            Object.keys(e).forEach(key => {
                if (key.startsWith('answers.')) {
                    const fieldId = key.replace('answers.', '');
                    const field = props.form.fields.find(f => f.id == fieldId);
                    const fieldName = field ? field.label : 'Campo';
                    errorMessages.push(`${fieldName}: ${Array.isArray(e[key]) ? e[key][0] : e[key]}`);
                }
            });

            const message = errorMessages.length > 0
                ? errorMessages[0]
                : 'Erro ao enviar resposta. Verifique os campos.';

            showToast(message, 'error');
        },
        onFinish: () => {
            submitting.value = false;
        }
    });
};

const progress = computed(() => {
    if (!statusConfig.value.canSubmit || !props.form.fields) return 0;
    const total = props.form.fields.length;
    const filled = props.form.fields.filter(f => {
        const val = answers.value[f.id];
        if (Array.isArray(val)) return val.length > 0;
        return val !== '' && val !== null && val !== undefined;
    }).length;
    return total > 0 ? Math.round((filled / total) * 100) : 0;
});
</script>

<template>

    <Head :title="form.title || 'Formulário'" />

    <div class="min-h-screen bg-gray-50 py-8 px-4">
        <div class="max-w-2xl mx-auto">
            <!-- Logo/Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-12 h-12 bg-cyan-500 rounded-xl mb-4">
                    <FileText class="w-6 h-6 text-white" />
                </div>
                <p class="text-sm text-gray-500">Formulário Online</p>
            </div>

            <!-- CARD DE FORMULÁRIO INDISPONÍVEL -->
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

                    <p class="text-lg text-gray-700 font-medium">
                        {{ form.title }}
                    </p>
                </div>

                <div class="p-8 text-center">
                    <div class="rounded-lg p-4 mb-6"
                        :class="`bg-${statusConfig.color}-50 border border-${statusConfig.color}-200`">
                        <p class="text-gray-700">
                            {{ statusConfig.message }}
                        </p>
                    </div>

                    <div v-if="form.status === 'rascunho' || form.status === 'pausado'" class="text-sm text-gray-500">
                        <p class="mb-2">Para receber respostas, o responsável precisa:</p>
                        <div class="inline-flex items-center gap-2 px-4 py-2 bg-gray-100 rounded-lg">
                            <span class="font-medium text-gray-700">
                                Alterar o status para <span class="text-green-600 font-bold">ATIVO</span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <p class="text-xs text-gray-400">
                            Entre em contato com o administrador do formulário para mais informações.
                        </p>
                    </div>
                </div>
            </div>

            <!-- FORMULÁRIO NORMAL (ATIVO) -->
            <div v-else class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="bg-gradient-to-r from-cyan-500 to-cyan-600 p-6 text-white">
                    <h1 class="text-2xl font-bold">{{ form.title }}</h1>
                    <p v-if="form.description" class="mt-2 text-cyan-100">
                        {{ form.description }}
                    </p>
                </div>

                <div class="bg-gray-50 px-6 py-3 border-b border-gray-200">
                    <div class="flex items-center justify-between text-sm mb-2">
                        <span class="text-gray-600">Progresso</span>
                        <span class="font-medium text-cyan-600">{{ progress }}%</span>
                    </div>
                    <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-cyan-500 rounded-full transition-all duration-300"
                            :style="{ width: progress + '%' }"></div>
                    </div>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-6">
                    <div v-for="field in form.fields" :key="field.id" class="space-y-2">
                        <FormField :field="field" v-model="answers[field.id]"
                            :class="{ 'border-red-300': errors[`answers.${field.id}`] }" />

                        <p v-if="errors[`answers.${field.id}`]" class="text-sm text-red-600 flex items-center gap-1">
                            <AlertTriangle class="w-4 h-4" />
                            {{ errors[`answers.${field.id}`] }}
                        </p>
                    </div>

                    <div class="pt-4 border-t border-gray-200">
                        <Button type="submit" variant="primary" size="lg" class="w-full gap-2" :disabled="submitting">
                            <Send v-if="!submitting" class="w-4 h-4" />
                            <span v-if="submitting">Enviando...</span>
                            <span v-else>Enviar Resposta</span>
                        </Button>
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
                <p class="text-xs text-gray-400">
                    Powered by FormSystem
                </p>
            </div>
        </div>
    </div>
</template>
