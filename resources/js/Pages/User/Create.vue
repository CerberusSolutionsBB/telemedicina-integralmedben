<script setup>
import { Head, router, useForm } from "@inertiajs/vue3";
import { computed } from "vue";
import { Button } from "@/Components/ui/button";
import PasswordInput from "@/Components/PasswordInput.vue";
import Breadcrumb from "@/Components/Breadcrumb.vue";
import TenantAdminLayout from "@/Layouts/TenantAdminLayout.vue";
import RoleSelectDialog from "@/Components/RoleSelectDialog.vue";
import {
    User,
    Mail,
    Save,
    ArrowLeft,
    UserPlus,
    Home,
} from "lucide-vue-next";

const props = defineProps({
    roles: {
        type: Array,
        default: () => [],
    },
    tenantName: {
        type: String,
        default: "",
    },
    tenantPhoto: {
        type: String,
        default: null,
    },
});

const breadcrumbs = computed(() => [
    { label: "Usuários", href: route("users.index"), icon: Home },
    { label: "Adicionar" },
]);

const inputClass =
    "block w-full border border-gray-300 rounded-lg py-2.5 pl-10 pr-4 text-sm bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition-shadow";

const inputErrorClass =
    "block w-full border border-red-300 rounded-lg py-2.5 pl-10 pr-4 text-sm bg-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-shadow";

const passwordInputClass =
    "w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-gray-900 focus:border-cyan-500 focus:ring-2 focus:ring-cyan-200 transition-colors";

const passwordInputErrorClass =
    "w-full rounded-lg border border-red-300 bg-white px-4 py-2.5 text-gray-900 focus:border-red-500 focus:ring-2 focus:ring-red-200 transition-colors";

const form = useForm({
    name: "",
    email: "",
    password: "",
    password_confirmation: "",
    role: "",
});

const submit = () => {
    form.post(route("users.store"), {
        onSuccess: () => router.visit(route("users.index")),
    });
};

const goBack = () => {
    router.visit(route("users.index"));
};
</script>

<template>

    <Head title="Novo Usuário" />

    <TenantAdminLayout :tenant-name="tenantName" :tenant-photo="tenantPhoto">
        <Breadcrumb :items="breadcrumbs" />
        <div class="space-y-6">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-cyan-100 flex items-center justify-center">
                        <UserPlus class="w-6 h-6 text-cyan-600" />
                    </div>
                    <div>
                        <h1 class="text-xl font-semibold text-gray-900">Novo Usuário</h1>
                        <p class="text-sm text-gray-500">
                            Preencha os dados para criar um novo usuário.
                        </p>
                    </div>
                </div>

            </div>

            <form class="grid grid-cols-1 gap-6" @submit.prevent="submit">
                <div class="bg-white rounded-lg shadow border border-gray-100 p-6 space-y-5">
                    <div>
                        <h2 class="text-base font-semibold text-gray-800">
                            Dados do usuário
                        </h2>
                        <p class="text-sm text-gray-500">
                            Informe nome, email e senha de acesso.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">Nome</label>
                        <div class="relative">
                            <User class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input v-model="form.name" type="text"
                                :class="form.errors.name ? inputErrorClass : inputClass" placeholder="Nome completo"
                                :disabled="form.processing" />
                        </div>
                        <p v-if="form.errors.name" class="text-sm text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-sm font-medium text-gray-700">E-mail</label>
                        <div class="relative">
                            <Mail class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                            <input v-model="form.email" type="email"
                                :class="form.errors.email ? inputErrorClass : inputClass"
                                placeholder="email@exemplo.com" :disabled="form.processing" />
                        </div>
                        <p v-if="form.errors.email" class="text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <PasswordInput v-model="form.password" id="password" label="Senha" placeholder="Mínimo 8 caracteres"
                        :error="form.errors.password" :required="true" :disabled="form.processing"
                        :input-class="passwordInputClass" :input-error-class="passwordInputErrorClass" />

                    <PasswordInput v-model="form.password_confirmation" id="password_confirmation"
                        label="Confirmar Senha" placeholder="Repita a senha" :error="form.errors.password_confirmation"
                        :required="false" :disabled="form.processing" :input-class="passwordInputClass"
                        :input-error-class="passwordInputErrorClass" />

                    <RoleSelectDialog v-model="form.role" :roles="roles" :disabled="form.processing" />

                    <p v-if="form.errors.role" class="text-sm text-red-500">
                        {{ form.errors.role }}
                    </p>

                    <div class="border-t border-gray-100 pt-5">
                        <Button type="submit" class="w-full gap-2" :disabled="form.processing">
                            <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>
                            <Save v-else class="w-4 h-4" />
                            {{ form.processing ? "Criando..." : "Criar Usuário" }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </TenantAdminLayout>
</template>
