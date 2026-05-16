<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue';
import Button from '@/Components/ui/button/Button.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Save,
    User,
    Mail,
    Phone,
    CreditCard,
    Calendar,
    Activity
} from 'lucide-vue-next';

const props = defineProps({
    planos: {
        type: Array,
        default: () => [],
    },
});

const form = useForm({
    codigoIntegracao: '',
    nomePessoa: '',
    cpfCnpj: '',
    email: '',
    sexo: 'M',
    dataNascimento: '',
    telefones: [
        {
            numero: '',
        },
    ],
    plano: '',
    diaVencimento: 10,
    ativo: true,
    situacao: 'Ativo',
});

const addTelefone = () => {
    form.telefones.push({
        numero: '',
    });
};

const removeTelefone = (index) => {
    if (form.telefones.length <= 1) return;

    form.telefones.splice(index, 1);
};

const submit = () => {
    form.post(route('siprov.store'), {
        preserveScroll: true,
    });
};

const goBack = () => {
    router.visit(route('siprov.index'));
};
</script>

<template>

    <Head title="Nova Integração SIPROV" />

    <CentralAdminLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Nova Integração SIPROV
                    </h1>

                    <p class="text-sm text-gray-500 mt-1">
                        Cadastro manual de associado + benefício
                    </p>
                </div>

                <Button variant="outline" @click="goBack">
                    <ArrowLeft class="w-4 h-4 mr-2" />
                    Voltar
                </Button>
            </div>

            <!-- Form -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                <form @submit.prevent="submit" class="space-y-6">
                    <!-- Dados pessoais -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            Dados do Associado
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Código Integração</label>
                                <div class="relative">
                                    <User class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                    <input v-model="form.codigoIntegracao" type="text"
                                        class="input input-bordered w-full pl-10" />
                                </div>
                                <p v-if="form.errors.codigoIntegracao" class="text-red-500 text-xs mt-1">
                                    {{ form.errors.codigoIntegracao }}
                                </p>
                            </div>

                            <div>
                                <label class="label">Nome</label>
                                <div class="relative">
                                    <User class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                    <input v-model="form.nomePessoa" type="text"
                                        class="input input-bordered w-full pl-10" />
                                </div>
                            </div>

                            <div>
                                <label class="label">CPF/CNPJ</label>
                                <div class="relative">
                                    <CreditCard class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                    <input v-model="form.cpfCnpj" type="text"
                                        class="input input-bordered w-full pl-10" />
                                </div>
                            </div>

                            <div>
                                <label class="label">E-mail</label>
                                <div class="relative">
                                    <Mail class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                    <input v-model="form.email" type="email"
                                        class="input input-bordered w-full pl-10" />
                                </div>
                            </div>

                            <div>
                                <label class="label">Sexo</label>
                                <select v-model="form.sexo" class="select select-bordered w-full">
                                    <option value="M">Masculino</option>
                                    <option value="F">Feminino</option>
                                </select>
                            </div>

                            <div>
                                <label class="label">Data nascimento</label>
                                <div class="relative">
                                    <Calendar class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                    <input v-model="form.dataNascimento" type="date"
                                        class="input input-bordered w-full pl-10" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Telefones -->
                    <div>
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-lg font-semibold text-gray-800">
                                Telefones
                            </h2>

                            <Button type="button" variant="outline" @click="addTelefone">
                                Adicionar
                            </Button>
                        </div>

                        <div v-for="(telefone, index) in form.telefones" :key="index" class="flex gap-3 mb-3">
                            <div class="relative flex-1">
                                <Phone class="absolute left-3 top-3 w-4 h-4 text-gray-400" />
                                <input v-model="telefone.numero" type="text" class="input input-bordered w-full pl-10"
                                    placeholder="(86) 99999-9999" />
                            </div>

                            <Button v-if="form.telefones.length > 1" type="button" variant="destructive"
                                @click="removeTelefone(index)">
                                Remover
                            </Button>
                        </div>
                    </div>

                    <!-- Benefício -->
                    <div>
                        <h2 class="text-lg font-semibold text-gray-800 mb-4">
                            Benefício
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="label">Plano</label>
                                <select v-model="form.plano" class="select select-bordered w-full">
                                    <option value="">Selecione</option>
                                    <option v-for="plano in planos" :key="plano.value" :value="plano.value">
                                        {{ plano.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="label">Dia vencimento</label>
                                <input v-model="form.diaVencimento" type="number" min="1" max="31"
                                    class="input input-bordered w-full" />
                            </div>

                            <div>
                                <label class="label">Situação</label>
                                <input v-model="form.situacao" type="text" class="input input-bordered w-full" />
                            </div>

                            <div>
                                <label class="label">Ativo</label>
                                <div class="flex items-center gap-3 mt-3">
                                    <Activity class="w-5 h-5 text-cyan-500" />
                                    <input v-model="form.ativo" type="checkbox" class="toggle toggle-success" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="flex justify-end gap-3 pt-6 border-t">
                        <Button type="button" variant="outline" @click="goBack">
                            Cancelar
                        </Button>

                        <Button type="submit" :disabled="form.processing" class="bg-cyan-600 hover:bg-cyan-700">
                            <Save class="w-4 h-4 mr-2" />

                            {{
                                form.processing
                                    ? 'Integrando...'
                                    : 'Salvar Integração'
                            }}
                        </Button>
                    </div>
                </form>
            </div>
        </div>
    </CentralAdminLayout>
</template>
