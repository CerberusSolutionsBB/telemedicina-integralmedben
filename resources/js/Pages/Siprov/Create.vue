<script setup>
import { computed } from 'vue';
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import Breadcrumb from '@/Components/Breadcrumb.vue';
import InputError from '@/Components/InputError.vue';
import { showToast } from '@/Utils/toast';

import {
    Save,
    User,
    Mail,
    Phone,
    CreditCard,
    Calendar,
    Activity,
    Home,
    ShieldAlert,
    CircleCheck,
    Circle,
    Plus,
    Trash2,
} from 'lucide-vue-next';

const props = defineProps({
    planos: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    nomePessoa: '',
    cpfCnpj: '',
    email: '',
    sexo: 'M',
    dataNascimento: '',
    telefones: [{ numero: '' }],
    plano: '',
    diaVencimento: 10,
    ativo: true,
    situacao: 'Ativo',
});

const inputClass =
    'w-full px-3 py-2 border border-primary-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all';

const inputIconClass =
    'w-full pl-10 pr-3 py-2 border border-primary-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all';

const inputErrorClass =
    'w-full pl-10 pr-3 py-2 border border-red-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 bg-red-50 transition-all';

const filledClass =
    'w-full pl-10 pr-3 py-2 border border-primary-300 rounded-md focus:outline-none focus:ring-2 focus:ring-primary-500 bg-primary-50/30 transition-all';

const getInputClass = (field, hasValue = false, withIcon = true) => {
    if (form.errors[field]) {
        return withIcon
            ? inputErrorClass
            : inputClass.replace('border-primary-300', 'border-red-300 bg-red-50');
    }

    if (hasValue) {
        return withIcon
            ? filledClass
            : inputClass.replace('border-primary-300', 'border-primary-300 bg-primary-50/30');
    }

    return withIcon ? inputIconClass : inputClass;
};

const onlyNumbers = (value) => String(value || '').replace(/\D/g, '');

const maskCpfCnpj = (value) => {
    const numbers = onlyNumbers(value).slice(0, 14);

    if (numbers.length <= 11) {
        return numbers
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1-$2');
    }

    return numbers
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1/$2')
        .replace(/(\d{4})(\d)/, '$1-$2');
};

const maskTelefone = (value) => {
    const numbers = onlyNumbers(value).slice(0, 11);

    if (numbers.length <= 10) {
        return numbers
            .replace(/^(\d{2})(\d)/, '($1) $2')
            .replace(/(\d{4})(\d)/, '$1-$2');
    }

    return numbers
        .replace(/^(\d{2})(\d)/, '($1) $2')
        .replace(/(\d{5})(\d)/, '$1-$2');
};

const isValidCpfCnpj = (value) => {
    const numbers = onlyNumbers(value);
    return numbers.length === 11 || numbers.length === 14;
};

const isValidEmail = (value) => {
    if (!value) return false;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value);
};

const isValidTelefone = (value) => {
    const numbers = onlyNumbers(value);
    return numbers.length === 10 || numbers.length === 11;
};

const applyCpfCnpjMask = () => {
    form.cpfCnpj = maskCpfCnpj(form.cpfCnpj);
};

const applyTelefoneMask = (index) => {
    form.telefones[index].numero = maskTelefone(form.telefones[index].numero);
};

const validateBeforeSubmit = () => {
    form.clearErrors();

    if (!form.nomePessoa?.trim()) {
        form.setError('nomePessoa', 'O nome é obrigatório.');
        showToast('Informe o nome do associado.', 'error');
        return false;
    }

    if (!isValidCpfCnpj(form.cpfCnpj)) {
        form.setError('cpfCnpj', 'CPF/CNPJ inválido. Informe 11 ou 14 dígitos.');
        showToast('CPF/CNPJ inválido.', 'error');
        return false;
    }

    if (!isValidEmail(form.email)) {
        form.setError('email', 'E-mail inválido.');
        showToast('E-mail inválido.', 'error');
        return false;
    }

    const hasTelefoneInvalido = form.telefones.some(
        (telefone) => !isValidTelefone(telefone.numero)
    );

    if (hasTelefoneInvalido) {
        form.setError('telefones', 'Informe telefones válidos com DDD. Ex: (86) 99999-9999.');
        showToast('Telefone inválido. Informe DDD + número.', 'error');
        return false;
    }

    if (!form.dataNascimento) {
        form.setError('dataNascimento', 'A data de nascimento é obrigatória.');
        showToast('Informe a data de nascimento.', 'error');
        return false;
    }

    if (!form.plano) {
        form.setError('plano', 'O plano é obrigatório.');
        showToast('Selecione um plano.', 'error');
        return false;
    }

    return true;
};

const associadoProgress = computed(() => {
    const fields = [
        form.nomePessoa,
        form.cpfCnpj,
        form.email,
        form.sexo,
        form.dataNascimento,
    ];

    const total = fields.filter(Boolean).length;

    return {
        total,
        max: fields.length,
        complete: total === fields.length,
    };
});

const beneficioProgress = computed(() => {
    const fields = [form.plano, form.diaVencimento, form.situacao];

    const total = fields.filter(Boolean).length + (form.ativo !== null ? 1 : 0);

    return {
        total,
        max: 4,
        complete: total === 4,
    };
});

const telefonesProgress = computed(() => {
    const validos = form.telefones.filter((telefone) =>
        isValidTelefone(telefone.numero)
    ).length;

    return {
        total: validos,
        max: form.telefones.length,
        complete: validos === form.telefones.length,
    };
});

const totalProgress = computed(() => {
    const total =
        associadoProgress.value.total +
        beneficioProgress.value.total +
        telefonesProgress.value.total;

    const max =
        associadoProgress.value.max +
        beneficioProgress.value.max +
        telefonesProgress.value.max;

    return Math.round((total / max) * 100);
});

const addTelefone = () => {
    form.telefones.push({ numero: '' });
};

const removeTelefone = (index) => {
    if (form.telefones.length <= 1) return;
    form.telefones.splice(index, 1);
};

const submit = () => {
    if (!validateBeforeSubmit()) return;

    form.post(route('siprov.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showToast('Integração SIPROV criada com sucesso!', 'success');
            form.reset();
        },
        onError: (errors) => {
            const message =
                Object.values(errors)[0] ||
                'Erro ao salvar integração SIPROV.';

            showToast(message, 'error');
        },
    });
};

const breadcrumbs = computed(() => [
    { label: 'Telemedicina', href: route('siprov.index'), icon: Home },
    { label: 'Adicionar', href: null },
]);
</script>

<template>

    <Head title="Nova Telemedicina" />

    <CentralAdminLayout>
        <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
            <div class="space-y-1">
                <Breadcrumb :items="breadcrumbs" />

                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    Adicionar Telemedicina
                </h1>

                <p class="text-sm text-gray-500">
                    Cadastro manual de associado e benefício
                </p>
            </div>

            <div class="flex items-center gap-3">
                <div class="hidden sm:flex items-center gap-2 text-sm text-gray-500">
                    <CircleCheck v-if="totalProgress === 100" class="w-4 h-4 text-primary-500" />
                    <Circle v-else class="w-4 h-4 text-gray-300" />

                    <span>{{ totalProgress }}% completo</span>
                </div>

                <Button @click="submit" :disabled="form.processing" class="gap-2 bg-cyan-600 hover:bg-cyan-700">
                    <Save class="w-4 h-4" />
                    {{ form.processing ? 'Integrando...' : 'Salvar Integração' }}
                </Button>
            </div>
        </div>

        <div class="space-y-6">
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <ShieldAlert class="w-5 h-5 text-red-600 mt-0.5" />

                    <div>
                        <h3 class="font-semibold text-red-900 mb-1">
                            Corrija os erros abaixo:
                        </h3>

                        <ul class="text-sm text-red-800 space-y-1">
                            <li v-for="(error, key) in form.errors" :key="key">
                                <strong>{{ key }}:</strong> {{ error }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section class="bg-white border border-primary-200 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/80 border-b border-primary-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-cyan-100 rounded-lg">
                                <User class="w-5 h-5 text-cyan-600" />
                            </div>

                            <div>
                                <h2 class="font-semibold text-gray-900">
                                    Dados do Associado
                                </h2>
                                <p class="text-xs text-gray-500">
                                    Informações pessoais do associado
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 text-xs font-medium">
                            <span :class="associadoProgress.complete ? 'text-primary-600' : 'text-gray-400'">
                                {{ associadoProgress.total }}/{{ associadoProgress.max }}
                            </span>

                            <CircleCheck v-if="associadoProgress.complete" class="w-4 h-4 text-primary-500" />
                            <Circle v-else class="w-4 h-4 text-gray-300" />
                        </div>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Nome <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <User class="absolute left-3 top-3 w-4 h-4 text-gray-400" />

                                <input v-model="form.nomePessoa" type="text" placeholder="Nome completo"
                                    :class="getInputClass('nomePessoa', form.nomePessoa)" :disabled="form.processing" />
                            </div>

                            <InputError :message="form.errors.nomePessoa" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                CPF/CNPJ <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <CreditCard class="absolute left-3 top-3 w-4 h-4 text-gray-400" />

                                <input v-model="form.cpfCnpj" @input="applyCpfCnpjMask" type="text" inputmode="numeric"
                                    maxlength="18" placeholder="000.000.000-00 ou 00.000.000/0000-00"
                                    :class="getInputClass('cpfCnpj', form.cpfCnpj)" :disabled="form.processing" />

                            </div>

                            <InputError :message="form.errors.cpfCnpj" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                E-mail <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <Mail class="absolute left-3 top-3 w-4 h-4 text-gray-400" />

                                <input v-model="form.email" type="email" autocomplete="email"
                                    placeholder="email@exemplo.com" :class="getInputClass('email', form.email)"
                                    :disabled="form.processing" />
                            </div>

                            <InputError :message="form.errors.email" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Sexo
                            </label>

                            <select v-model="form.sexo" :class="getInputClass('sexo', form.sexo, false)"
                                :disabled="form.processing">
                                <option value="M">Masculino</option>
                                <option value="F">Feminino</option>
                            </select>

                            <InputError :message="form.errors.sexo" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Data de nascimento <span class="text-red-500">*</span>
                            </label>

                            <div class="relative">
                                <Calendar class="absolute left-3 top-3 w-4 h-4 text-gray-400" />

                                <input v-model="form.dataNascimento" type="date"
                                    :class="getInputClass('dataNascimento', form.dataNascimento)"
                                    :disabled="form.processing" />
                            </div>

                            <InputError :message="form.errors.dataNascimento" class="mt-1" />
                        </div>
                    </div>
                </section>

                <section class="bg-white border border-primary-200 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/80 border-b border-primary-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <Phone class="w-5 h-5 text-blue-600" />
                            </div>

                            <div>
                                <h2 class="font-semibold text-gray-900">
                                    Telefones
                                </h2>
                                <p class="text-xs text-gray-500">
                                    Contatos vinculados ao associado
                                </p>
                            </div>
                        </div>

                        <Button type="button" variant="outline" @click="addTelefone">
                            <Plus class="w-4 h-4 mr-2" />
                            Adicionar
                        </Button>
                    </div>

                    <div class="p-6 space-y-3">
                        <div v-for="(telefone, index) in form.telefones" :key="index" class="flex gap-3">
                            <div class="relative flex-1">
                                <Phone class="absolute left-3 top-3 w-4 h-4 text-gray-400" />

                                <input v-model="telefone.numero" @input="applyTelefoneMask(index)" type="text"
                                    inputmode="numeric" maxlength="15"
                                    class="w-full pl-10 pr-3 py-2 border border-primary-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 transition-all"
                                    placeholder="(86) 99999-9999" :disabled="form.processing" />
                            </div>

                            <Button v-if="form.telefones.length > 1" type="button" variant="destructive"
                                @click="removeTelefone(index)">
                                <Trash2 class="w-4 h-4" />
                            </Button>
                        </div>

                        <InputError :message="form.errors.telefones" />
                    </div>
                </section>

                <section class="bg-white border border-primary-200 rounded-xl overflow-hidden">
                    <div class="px-6 py-4 bg-gray-50/80 border-b border-primary-200 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="p-2 bg-purple-100 rounded-lg">
                                <Activity class="w-5 h-5 text-purple-600" />
                            </div>

                            <div>
                                <h2 class="font-semibold text-gray-900">
                                    Benefício
                                </h2>
                                <p class="text-xs text-gray-500">
                                    Plano, vencimento e situação do benefício
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1.5 text-xs font-medium">
                            <span :class="beneficioProgress.complete ? 'text-primary-600' : 'text-gray-400'">
                                {{ beneficioProgress.total }}/{{ beneficioProgress.max }}
                            </span>

                            <CircleCheck v-if="beneficioProgress.complete" class="w-4 h-4 text-primary-500" />
                            <Circle v-else class="w-4 h-4 text-gray-300" />
                        </div>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Plano <span class="text-red-500">*</span>
                            </label>

                            <select v-model="form.plano" :class="getInputClass('plano', form.plano, false)"
                                :disabled="form.processing">
                                <option value="">Selecione</option>

                                <option v-for="plano in planos" :key="plano.value" :value="plano.value">
                                    {{ plano.label }}
                                </option>
                            </select>

                            <InputError :message="form.errors.plano" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Dia de vencimento
                            </label>

                            <input v-model="form.diaVencimento" type="number" min="1" max="31"
                                :class="getInputClass('diaVencimento', form.diaVencimento, false)"
                                :disabled="form.processing" />

                            <InputError :message="form.errors.diaVencimento" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Situação
                            </label>

                            <input v-model="form.situacao" type="text" placeholder="Ativo"
                                :class="getInputClass('situacao', form.situacao, false)" :disabled="form.processing" />

                            <InputError :message="form.errors.situacao" class="mt-1" />
                        </div>

                        <div>
                            <label class="text-gray-700 font-medium flex items-center gap-2 pb-2">
                                Ativo
                            </label>

                            <div class="flex items-center gap-3 min-h-[42px]">
                                <Activity class="w-5 h-5 text-cyan-500" />

                                <input v-model="form.ativo" type="checkbox" class="toggle toggle-success"
                                    :disabled="form.processing" />

                                <span class="text-sm text-gray-600">
                                    {{ form.ativo ? 'Sim' : 'Não' }}
                                </span>
                            </div>

                            <InputError :message="form.errors.ativo" class="mt-1" />
                        </div>
                    </div>
                </section>
            </form>
        </div>
    </CentralAdminLayout>
</template>
