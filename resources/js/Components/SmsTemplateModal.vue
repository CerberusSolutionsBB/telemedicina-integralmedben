<script setup>
import { ref, watch } from 'vue';
import { Button } from '@/Components/ui/button';
import { X, Loader2, Save, MessageSquare } from 'lucide-vue-next';
import { showToast } from '@/Utils/toast';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    show: { type: Boolean, default: false },
    template: { type: Object, default: null },
    event: { type: String, default: 'patient.created' },
});

const emit = defineEmits(['close', 'saved']);

const availableTags = {
    'patient.created': [
        { tag: '{nome}', label: 'Nome do paciente' },
        { tag: '{cpf}', label: 'CPF' },
        { tag: '{telefone}', label: 'Telefone' },
        { tag: '{email}', label: 'E-mail' },
        { tag: '{sexo}', label: 'Sexo' },
        { tag: '{data_nascimento}', label: 'Data de nascimento' },
        { tag: '{data}', label: 'Data atual' },
        { tag: '{hora}', label: 'Hora atual' },
    ],
    'siprov.integrated': [
        { tag: '{nome}', label: 'Nome do paciente' },
        { tag: '{cpf}', label: 'CPF' },
        { tag: '{telefone}', label: 'Telefone' },
        { tag: '{email}', label: 'E-mail' },
        { tag: '{plano}', label: 'Plano' },
        { tag: '{data}', label: 'Data atual' },
        { tag: '{hora}', label: 'Hora atual' },
    ],
};

const form = ref({
    name: '',
    message: '',
    event: props.event,
    plan_id: null,
    variables: [],
    is_active: true,
});

const isProcessing = ref(false);

watch(() => props.show, (val) => {
    if (val && props.template) {
        form.value = {
            name: props.template.name || '',
            message: props.template.message || '',
            event: props.template.event || props.event,
            plan_id: props.template.plan_id || null,
            variables: props.template.variables || [],
            is_active: props.template.is_active ?? true,
        };
    } else if (val) {
        form.value = {
            name: '',
            message: '',
            event: props.event,
            plan_id: null,
            variables: [],
            is_active: true,
        };
    }
});

const insertTag = (tag) => {
    form.value.message += tag;
};

const handleClose = () => {
    if (!isProcessing.value) {
        emit('close');
    }
};

const handleSubmit = async () => {
    if (!form.value.name.trim() || !form.value.message.trim()) {
        showToast('Preencha todos os campos obrigatórios.', 'warning');
        return;
    }

    isProcessing.value = true;

    try {
        const url = props.template
            ? route('forms.sms-templates.update', props.template.id)
            : route('forms.sms-templates.store');

        const method = props.template ? 'PUT' : 'POST';

        const response = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] || ''),
            },
            body: JSON.stringify(form.value),
        });

        const data = await response.json();

        if (response.ok) {
            showToast(data.message, 'success');
            emit('saved');
            emit('close');
            router.reload({ only: ['smsTemplates'] });
        } else {
            showToast(data.message || 'Erro ao salvar template.', 'error');
        }
    } catch {
        showToast('Erro ao salvar template. Tente novamente.', 'error');
    } finally {
        isProcessing.value = false;
    }
};
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition ease-out duration-200" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="handleClose"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden">
                    <div class="flex items-center justify-between p-6 pb-4 border-b border-gray-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-cyan-100 flex items-center justify-center">
                                <MessageSquare class="w-5 h-5 text-cyan-600" />
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900">
                                    {{ template ? 'Editar Template SMS' : 'Novo Template SMS' }}
                                </h3>
                                <p class="text-sm text-gray-500">Configure a mensagem enviada por SMS</p>
                            </div>
                        </div>
                        <button @click="handleClose" :disabled="isProcessing"
                            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-lg transition-colors">
                            <X class="w-5 h-5" />
                        </button>
                    </div>

                    <div class="p-6 overflow-y-auto max-h-[calc(90vh-140px)] space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Nome do Template *</label>
                            <input v-model="form.name" type="text" placeholder="Ex: Boas-vindas paciente"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm" />
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mensagem *</label>
                            <textarea v-model="form.message" rows="4"
                                placeholder="Ex: {nome}, seu cadastro foi realizado com sucesso!"
                                class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm resize-none"></textarea>
                            <p class="text-xs text-gray-400 mt-1">{{ form.message.length }} caracteres</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tags disponíveis</label>
                            <div class="flex flex-wrap gap-2">
                                <button v-for="tag in availableTags[props.event]" :key="tag.tag" type="button"
                                    @click="insertTag(tag.tag)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium bg-gray-100 text-gray-700 rounded-full hover:bg-cyan-100 hover:text-cyan-700 transition-colors cursor-pointer"
                                    :title="tag.label">
                                    <span class="font-mono text-cyan-600">{{ tag.tag }}</span>
                                    <span class="text-gray-400">{{ tag.label }}</span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <input v-model="form.is_active" type="checkbox" id="is_active"
                                class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500" />
                            <label for="is_active" class="text-sm text-gray-700">Template ativo</label>
                        </div>
                    </div>

                    <div class="flex gap-3 px-6 py-4 bg-gray-50 border-t border-gray-100">
                        <Button variant="outline" class="flex-1" @click="handleClose" :disabled="isProcessing">
                            Cancelar
                        </Button>
                        <Button class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white gap-2" @click="handleSubmit"
                            :disabled="isProcessing">
                            <Loader2 v-if="isProcessing" class="w-4 h-4 animate-spin" />
                            <Save v-else class="w-4 h-4" />
                            <span v-if="isProcessing">Salvando...</span>
                            <span v-else>Salvar</span>
                        </Button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
