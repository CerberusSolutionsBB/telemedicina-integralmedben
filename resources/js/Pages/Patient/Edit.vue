<script setup>
import { computed, ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import { Button } from "@/Components/ui/button";
import { Label } from "@/Components/ui/label";
import AppSwitch from "@/Components/ui/switch/Switch.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import {
    ArrowLeft,
    Save,
    User,
    Mail,
    Phone,
    CreditCard,
    Calendar,
    Heart,
    AlertCircle,
    MapPin,
    Loader2,
    Search,
    Users,
} from "lucide-vue-next";

const props = defineProps({
    patient: { type: Object, required: true },
});

const breadcrumbs = computed(() => [
    { label: "Pacientes", href: route("patients.index"), icon: Users },
    { label: `Editar Paciente #${props.patient.id}`, href: null },
]);

const enderecosIniciais = computed(() => {
    const e = props.patient.enderecos;
    if (!e || typeof e !== 'object') {
        return { cep: "", logradouro: "", numero: "", complemento: "", bairro: "", cidade: "", estado: "" };
    }
    return {
        cep: e.cep || "",
        logradouro: e.logradouro || "",
        numero: e.numero || "",
        complemento: e.complemento || "",
        bairro: e.bairro || "",
        cidade: e.cidade || "",
        estado: e.estado || "",
    };
});

const form = useForm({
    nome: props.patient.nome || "",
    cpf: props.patient.cpf || "",
    rg: props.patient.rg || "",
    data_nascimento: props.patient.data_nascimento || "",
    sexo: props.patient.sexo || "",
    email: props.patient.email || "",
    numero: props.patient.numero || "",
    status: Boolean(props.patient.status),
    enderecos: { ...enderecosIniciais.value },
});

const buscandoCep = ref(false);

const inputClass =
    "w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500";

const maskCpf = (e) => {
    let v = e.target.value.replace(/\D/g, "").slice(0, 11);
    v = v.replace(/(\d{3})(\d)/, "$1.$2");
    v = v.replace(/(\d{3})(\d)/, "$1.$2");
    v = v.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
    form.cpf = v;
};

const maskPhone = (e) => {
    let v = e.target.value.replace(/\D/g, "").slice(0, 11);
    v = v.replace(/^(\d{2})(\d)/, "($1) $2");
    v = v.replace(/(\d{5})(\d)/, "$1-$2");
    form.numero = v;
};

const maskCep = (e) => {
    let v = e.target.value.replace(/\D/g, "").slice(0, 8);
    v = v.replace(/^(\d{5})(\d)/, "$1-$2");
    form.enderecos.cep = v;

    if (v.replace(/\D/g, "").length === 8) {
        buscarCep(v.replace(/\D/g, ""));
    }
};

const buscarCep = async (cep) => {
    buscandoCep.value = true;
    try {
        const response = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
        const data = await response.json();

        if (data.erro) {
            form.enderecos.logradouro = "";
            form.enderecos.bairro = "";
            form.enderecos.cidade = "";
            form.enderecos.estado = "";
            return;
        }

        form.enderecos.logradouro = data.logradouro || "";
        form.enderecos.bairro = data.bairro || "";
        form.enderecos.cidade = data.localidade || "";
        form.enderecos.estado = data.uf || "";
    } catch {
        form.enderecos.logradouro = "";
        form.enderecos.bairro = "";
        form.enderecos.cidade = "";
        form.enderecos.estado = "";
    } finally {
        buscandoCep.value = false;
    }
};

const submit = () => {
    form.put(route("patients.update", props.patient.id), {
        preserveScroll: true,
        onError: () => { },
    });
};

const goBack = () => {
    router.visit(route("patients.index"));
};
</script>

<template>

    <Head title="Editar Paciente" />

    <TenantAdminLayout>
        <Breadcrumb :items="breadcrumbs" />

        <div class="flex flex-wrap items-center justify-between gap-2 mb-6">
            <div class="space-y-1">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">
                    Editar Paciente #{{ patient.id }}
                </h1>
                <p class="text-sm text-gray-500">
                    Altere os dados do paciente
                </p>
            </div>
            <div class="flex gap-2">

                <Button variant="primary" :disabled="form.processing || !form.nome" @click="submit" class="gap-2">
                    <Save class="w-4 h-4" />
                    <span v-if="form.processing">Salvando...</span>
                    <span v-else>Salvar</span>
                </Button>
            </div>
        </div>

        <div class="space-y-6">
            <div v-if="Object.keys(form.errors).length > 0" class="bg-red-50 border border-red-200 rounded-xl p-4">
                <div class="flex items-start gap-3">
                    <AlertCircle class="w-5 h-5 text-red-600 mt-0.5 shrink-0" />
                    <div>
                        <h3 class="font-semibold text-red-900 mb-1">Corrija os erros abaixo:</h3>
                        <ul class="text-sm text-red-800 space-y-1">
                            <li v-for="(error, key) in form.errors" :key="key">{{ error }}</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Status toggle -->
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-4 flex items-center justify-between">
                <span class="text-sm font-medium text-gray-700">Status do Paciente</span>
                <div class="flex items-center gap-3">
                    <AppSwitch v-model="form.status" />
                    <span class="text-sm font-medium" :class="form.status ? 'text-green-700' : 'text-red-700'">
                        {{ form.status ? 'Ativo' : 'Inativo' }}
                    </span>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                        <User class="w-5 h-5 text-cyan-600" />
                        <h2>Dados do Paciente</h2>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <Label for="nome" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                Nome <span class="text-red-500">*</span>
                            </Label>
                            <input id="nome" v-model="form.nome" type="text" placeholder="Nome completo"
                                :class="[inputClass, form.errors.nome ? 'border-red-500 focus:ring-red-500' : '']"
                                :disabled="form.processing" />
                            <p v-if="form.errors.nome" class="mt-1 text-sm text-red-600">{{ form.errors.nome }}</p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="cpf" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    <CreditCard class="w-4 h-4 text-gray-400" />
                                    CPF
                                </Label>
                                <input id="cpf" v-model="form.cpf" type="text" inputmode="numeric" maxlength="14"
                                    placeholder="000.000.000-00" @input="maskCpf"
                                    :class="[inputClass, form.errors.cpf ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.cpf" class="mt-1 text-sm text-red-600">{{ form.errors.cpf }}</p>
                            </div>

                            <div>
                                <Label for="rg" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    <CreditCard class="w-4 h-4 text-gray-400" />
                                    RG
                                </Label>
                                <input id="rg" v-model="form.rg" type="text" placeholder="RG"
                                    :class="[inputClass, form.errors.rg ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.rg" class="mt-1 text-sm text-red-600">{{ form.errors.rg }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="data_nascimento"
                                    class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    <Calendar class="w-4 h-4 text-gray-400" />
                                    Data de Nascimento
                                </Label>
                                <input id="data_nascimento" v-model="form.data_nascimento" type="date"
                                    :class="[inputClass, form.errors.data_nascimento ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.data_nascimento" class="mt-1 text-sm text-red-600">
                                    {{ form.errors.data_nascimento }}
                                </p>
                            </div>

                            <div>
                                <Label for="sexo" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    <Heart class="w-4 h-4 text-gray-400" />
                                    Sexo
                                </Label>
                                <select id="sexo" v-model="form.sexo"
                                    :class="[inputClass, form.errors.sexo ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing">
                                    <option value="">Selecione...</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="feminino">Feminino</option>
                                </select>
                                <p v-if="form.errors.sexo" class="mt-1 text-sm text-red-600">{{ form.errors.sexo }}</p>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="email" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    <Mail class="w-4 h-4 text-gray-400" />
                                    E-mail
                                </Label>
                                <input id="email" v-model="form.email" type="email" placeholder="paciente@exemplo.com"
                                    :class="[inputClass, form.errors.email ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}
                                </p>
                            </div>

                            <div>
                                <Label for="numero" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    <Phone class="w-4 h-4 text-gray-400" />
                                    Telefone
                                </Label>
                                <input id="numero" v-model="form.numero" type="text" inputmode="numeric" maxlength="15"
                                    placeholder="(00) 00000-0000" @input="maskPhone"
                                    :class="[inputClass, form.errors.numero ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <p v-if="form.errors.numero" class="mt-1 text-sm text-red-600">{{ form.errors.numero }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6">
                    <div class="flex items-center gap-2 mb-6 text-gray-900 font-semibold border-b border-gray-100 pb-4">
                        <MapPin class="w-5 h-5 text-cyan-600" />
                        <h2>Endereço</h2>
                    </div>

                    <div class="space-y-5">
                        <div>
                            <Label for="cep" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                <Search class="w-4 h-4 text-gray-400" />
                                CEP
                            </Label>
                            <div class="relative">
                                <input id="cep" v-model="form.enderecos.cep" type="text" inputmode="numeric"
                                    maxlength="9" placeholder="00000-000" @input="maskCep"
                                    :class="[inputClass, buscandoCep ? 'pr-10' : '', form.errors['enderecos.cep'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                                <Loader2 v-if="buscandoCep"
                                    class="w-4 h-4 text-gray-400 animate-spin absolute right-3 top-3" />
                            </div>
                            <p v-if="form.errors['enderecos.cep']" class="mt-1 text-sm text-red-600">
                                {{ form.errors['enderecos.cep'] }}
                            </p>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <Label for="logradouro" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Logradouro
                                </Label>
                                <input id="logradouro" v-model="form.enderecos.logradouro" type="text"
                                    placeholder="Rua, Avenida..."
                                    :class="[inputClass, form.errors['enderecos.logradouro'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="numero_endereco"
                                    class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Número
                                </Label>
                                <input id="numero_endereco" v-model="form.enderecos.numero" type="text" placeholder="Nº"
                                    :class="[inputClass, form.errors['enderecos.numero'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                            </div>

                            <div>
                                <Label for="complemento" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Complemento
                                </Label>
                                <input id="complemento" v-model="form.enderecos.complemento" type="text"
                                    placeholder="Apto, Bloco..."
                                    :class="[inputClass, form.errors['enderecos.complemento'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="bairro" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Bairro
                                </Label>
                                <input id="bairro" v-model="form.enderecos.bairro" type="text" placeholder="Bairro"
                                    :class="[inputClass, form.errors['enderecos.bairro'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                            </div>

                            <div>
                                <Label for="cidade" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Cidade
                                </Label>
                                <input id="cidade" v-model="form.enderecos.cidade" type="text" placeholder="Cidade"
                                    :class="[inputClass, form.errors['enderecos.cidade'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing" />
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <Label for="estado" class="flex items-center gap-1 text-gray-700 pb-2 font-medium">
                                    Estado
                                </Label>
                                <select id="estado" v-model="form.enderecos.estado"
                                    :class="[inputClass, form.errors['enderecos.estado'] ? 'border-red-500 focus:ring-red-500' : '']"
                                    :disabled="form.processing">
                                    <option value="">Selecione...</option>
                                    <option value="AC">Acre</option>
                                    <option value="AL">Alagoas</option>
                                    <option value="AP">Amapá</option>
                                    <option value="AM">Amazonas</option>
                                    <option value="BA">Bahia</option>
                                    <option value="CE">Ceará</option>
                                    <option value="DF">Distrito Federal</option>
                                    <option value="ES">Espírito Santo</option>
                                    <option value="GO">Goiás</option>
                                    <option value="MA">Maranhão</option>
                                    <option value="MT">Mato Grosso</option>
                                    <option value="MS">Mato Grosso do Sul</option>
                                    <option value="MG">Minas Gerais</option>
                                    <option value="PA">Pará</option>
                                    <option value="PB">Paraíba</option>
                                    <option value="PR">Paraná</option>
                                    <option value="PE">Pernambuco</option>
                                    <option value="PI">Piauí</option>
                                    <option value="RJ">Rio de Janeiro</option>
                                    <option value="RN">Rio Grande do Norte</option>
                                    <option value="RS">Rio Grande do Sul</option>
                                    <option value="RO">Rondônia</option>
                                    <option value="RR">Roraima</option>
                                    <option value="SC">Santa Catarina</option>
                                    <option value="SP">São Paulo</option>
                                    <option value="SE">Sergipe</option>
                                    <option value="TO">Tocantins</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </TenantAdminLayout>
</template>
