<script setup>
import { computed, ref } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { Check, ChevronDown, Eye, EyeOff, ShieldCheck } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError.vue";
import FormErrors from "./FormErrors.vue";
import PermissionMatrix from "./PermissionMatrix.vue";
import { fieldError, submitFeedback, useAcl } from "./useAcl";

const props = defineProps({
    user: { type: Object, default: null },
    roles: { type: Array, required: true },
    permissions: { type: Array, required: true },
    isCurrentUser: { type: Boolean, default: false },
});

const { aclRoute } = useAcl();
const isEdit = computed(() => Boolean(props.user));

const form = useForm({
    name: props.user?.name ?? "",
    email: props.user?.email ?? "",
    password: "",
    password_confirmation: "",
    roles: [...(props.user?.roles ?? [])],
    permissions: [...(props.user?.permissions ?? [])],
});

const showPassword = ref(false);
const showExtraPermissions = ref(form.permissions.length > 0);


// Permissões que o usuário já recebe pelos perfis selecionados
const inheritedPermissions = computed(() => {
    const names = new Set();
    props.roles
        .filter((role) => form.roles.includes(role.name))
        .forEach((role) => role.permissions.forEach((p) => names.add(p)));
    return [...names];
});

const toggleRole = (name) => {
    form.roles = form.roles.includes(name)
        ? form.roles.filter((r) => r !== name)
        : [...form.roles, name];
};

const submit = () => {
    // Evita gravar como permissão direta o que já vem de um perfil
    form.transform((data) => ({
        ...data,
        permissions: data.permissions.filter((p) => !inheritedPermissions.value.includes(p)),
    }));

    const options = submitFeedback(() => form.reset("password", "password_confirmation"));
    if (isEdit.value) {
        form.put(aclRoute("users.update", props.user.id), options);
    } else {
        form.post(aclRoute("users.store"), options);
    }
};

const inputClass = (field) => [
    "w-full rounded-lg border px-3 py-2 text-sm focus:outline-none focus:ring-2",
    form.errors[field]
        ? "border-red-400 bg-red-50/40 focus:border-red-500 focus:ring-red-500/30"
        : "border-gray-300 focus:border-cyan-500 focus:ring-cyan-500/30",
];

// Remove o erro do campo assim que o usuário volta a digitar
const clear = (field) => form.errors[field] && form.clearErrors(field);
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="submit">
        <FormErrors :errors="form.errors" />

        <!-- Dados -->
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Dados do usuário</h2>
            <p class="mb-4 text-sm text-gray-500">Informações de identificação e acesso.</p>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-sm font-medium text-gray-700">Nome</label>
                    <input id="name" v-model="form.name" type="text" autocomplete="name" :class="inputClass('name')"
                        :aria-invalid="Boolean(form.errors.name)" aria-describedby="name-error" required @input="clear('name')" />
                    <InputError id="name-error" :message="form.errors.name" class="mt-1" />
                </div>
                <div>
                    <label for="email" class="mb-1 block text-sm font-medium text-gray-700">E-mail</label>
                    <input id="email" v-model="form.email" type="email" autocomplete="email" :class="inputClass('email')"
                        :aria-invalid="Boolean(form.errors.email)" aria-describedby="email-error" required @input="clear('email')" />
                    <InputError id="email-error" :message="form.errors.email" class="mt-1" />
                </div>
                <div>
                    <label for="password" class="mb-1 block text-sm font-medium text-gray-700">
                        Senha <span v-if="isEdit" class="font-normal text-gray-400">(deixe em branco para manter)</span>
                    </label>
                    <div class="relative">
                        <input id="password" v-model="form.password" :type="showPassword ? 'text' : 'password'"
                            autocomplete="new-password" :class="[inputClass('password'), 'pr-10']" :required="!isEdit"
                            :aria-invalid="Boolean(form.errors.password)" aria-describedby="password-hint password-error"
                            @input="clear('password')" />
                        <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-400 hover:text-gray-600"
                            :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'" @click="showPassword = !showPassword">
                            <EyeOff v-if="showPassword" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </button>
                    </div>
                    <p id="password-hint" class="mt-1 text-xs text-gray-400">Mínimo de 8 caracteres, com maiúsculas, minúsculas, números e símbolos.</p>
                    <InputError id="password-error" :message="form.errors.password" class="mt-1" />
                </div>
                <div>
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium text-gray-700">Confirmar senha</label>
                    <input id="password_confirmation" v-model="form.password_confirmation"
                        :type="showPassword ? 'text' : 'password'" autocomplete="new-password" :class="inputClass('password')"
                        :required="!isEdit || Boolean(form.password)" />
                </div>
            </div>
        </section>

        <!-- Perfis -->
        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Perfis</h2>
            <p class="mb-4 text-sm text-gray-500">O usuário recebe todas as permissões dos perfis selecionados.</p>

            <div v-if="roles.length" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <button v-for="role in roles" :key="role.id" type="button" role="checkbox"
                    :aria-checked="form.roles.includes(role.name)"
                    class="flex items-start gap-3 rounded-lg border-2 p-3 text-left transition-colors"
                    :class="form.roles.includes(role.name) ? 'border-cyan-600 bg-cyan-50' : 'border-gray-200 hover:border-gray-300'"
                    @click="toggleRole(role.name)">
                    <span class="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border"
                        :class="form.roles.includes(role.name) ? 'border-cyan-600 bg-cyan-600 text-white' : 'border-gray-300 bg-white'">
                        <Check v-if="form.roles.includes(role.name)" class="h-3.5 w-3.5" />
                    </span>
                    <span>
                        <span class="flex items-center gap-1.5 text-sm font-semibold text-gray-900">
                            <ShieldCheck v-if="role.name === 'Admin'" class="h-4 w-4 text-cyan-600" />
                            {{ role.name }}
                        </span>
                        <span class="text-xs text-gray-500">
                            {{ role.name === 'Admin' ? 'Acesso total' : `${role.permissions.length} permissões` }}
                        </span>
                    </span>
                </button>
            </div>
            <p v-else class="text-sm text-gray-500">Nenhum perfil cadastrado.</p>
            <InputError :message="fieldError(form.errors, 'roles')" class="mt-2" />
            <p v-if="isCurrentUser" class="mt-3 text-xs text-amber-700">
                Atenção: você está editando o seu próprio usuário. Remover perfis pode tirar o seu acesso a esta área.
            </p>
        </section>

        <!-- Permissões extras -->
        <section class="rounded-lg border border-gray-200 bg-white shadow-sm">
            <button type="button" class="flex w-full items-center justify-between gap-3 p-5 text-left"
                :aria-expanded="showExtraPermissions" @click="showExtraPermissions = !showExtraPermissions">
                <span>
                    <span class="block text-base font-semibold text-gray-900">
                        Permissões adicionais
                        <span v-if="form.permissions.length" class="ml-1 rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-medium text-cyan-800">
                            {{ form.permissions.length }}
                        </span>
                    </span>
                    <span class="block text-sm text-gray-500">Opcional: conceda permissões específicas além das dos perfis.</span>
                </span>
                <ChevronDown class="h-5 w-5 flex-shrink-0 text-gray-400 transition-transform" :class="{ 'rotate-180': showExtraPermissions }" />
            </button>
            <div v-show="showExtraPermissions" class="border-t border-gray-100 p-5">
                <PermissionMatrix v-model="form.permissions" :permissions="permissions" :inherited="inheritedPermissions" />
                <InputError :message="fieldError(form.errors, 'permissions')" class="mt-2" />
            </div>
        </section>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <Link :href="aclRoute('users.index')"
                class="inline-flex h-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Cancelar
            </Link>
            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? "Salvando..." : isEdit ? "Salvar alterações" : "Criar usuário" }}
            </Button>
        </div>
    </form>
</template>
