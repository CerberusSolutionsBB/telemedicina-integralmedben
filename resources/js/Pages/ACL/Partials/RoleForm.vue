<script setup>
import { computed } from "vue";
import { Link, useForm } from "@inertiajs/vue3";
import { ShieldCheck } from "lucide-vue-next";
import { Button } from "@/Components/ui/button";
import InputError from "@/Components/InputError.vue";
import FormErrors from "./FormErrors.vue";
import PermissionMatrix from "./PermissionMatrix.vue";
import { fieldError, submitFeedback, useAcl } from "./useAcl";

const props = defineProps({
    role: { type: Object, default: null },
    permissions: { type: Array, required: true },
});

const { aclRoute } = useAcl();
const isEdit = computed(() => Boolean(props.role));
const isProtected = computed(() => Boolean(props.role?.protected));

const form = useForm({
    name: props.role?.name ?? "",
    permissions: isProtected.value ? [...props.permissions] : [...(props.role?.permissions ?? [])],
});

const submit = () => {
    if (isEdit.value) {
        form.put(aclRoute("roles.update", props.role.id), submitFeedback());
    } else {
        form.post(aclRoute("roles.store"), submitFeedback());
    }
};
</script>

<template>
    <form class="space-y-5" novalidate @submit.prevent="submit">
        <FormErrors :errors="form.errors" />

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <label for="role-name" class="mb-1 block text-sm font-medium text-gray-700">Nome do perfil</label>
            <input id="role-name" v-model="form.name" type="text" required :disabled="isProtected"
                placeholder="Ex.: Atendente, Supervisor..." :aria-invalid="Boolean(form.errors.name)"
                aria-describedby="role-name-error" @input="form.errors.name && form.clearErrors('name')"
                :class="form.errors.name ? 'border-red-400 bg-red-50/40' : 'border-gray-300'"
                class="w-full max-w-md rounded-lg border px-3 py-2 text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30 disabled:bg-gray-100 disabled:text-gray-500" />
            <InputError id="role-name-error" :message="form.errors.name" class="mt-1" />
            <p v-if="role?.users_count" class="mt-2 text-xs text-gray-500">
                {{ role.users_count }} usuário(s) com este perfil serão afetados pelas alterações.
            </p>
        </section>

        <section class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h2 class="text-base font-semibold text-gray-900">Permissões</h2>
            <p class="mb-4 text-sm text-gray-500">Defina o que os usuários com este perfil podem fazer.</p>

            <div v-if="isProtected" class="mb-4 flex items-start gap-2 rounded-lg border border-cyan-200 bg-cyan-50 p-3 text-sm text-cyan-900">
                <ShieldCheck class="mt-0.5 h-4 w-4 flex-shrink-0" />
                O perfil <strong>Admin</strong> é protegido: possui acesso total e recebe automaticamente toda nova permissão.
            </div>

            <PermissionMatrix v-model="form.permissions" :permissions="permissions" :disabled="isProtected" />
            <InputError :message="fieldError(form.errors, 'permissions')" class="mt-2" />
        </section>

        <div class="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <Link :href="aclRoute('roles.index')"
                class="inline-flex h-10 items-center justify-center rounded-lg border-2 border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                {{ isProtected ? "Voltar" : "Cancelar" }}
            </Link>
            <Button v-if="!isProtected" type="submit" :disabled="form.processing">
                {{ form.processing ? "Salvando..." : isEdit ? "Salvar alterações" : "Criar perfil" }}
            </Button>
        </div>
    </form>
</template>
