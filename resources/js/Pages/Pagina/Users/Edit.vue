<script setup>
import CentralAdminLayout from '@/Layouts/CentralAdminLayout.vue'
import Breadcrumb from '@/Components/Breadcrumb.vue'
import PasswordInput from '@/Components/PasswordInput.vue'
import { router, useForm } from '@inertiajs/vue3'
import { computed } from 'vue'
import { showToast } from '@/Utils/toast'
import Button from '@/Components/ui/button/Button.vue'
import {
    Home,
    User,
    Mail,
    Shield,
    Save,
    ArrowLeft,
} from 'lucide-vue-next'

const props = defineProps({
    tenant: Object,
    user: Object,
    roles: {
        type: Array,
        default: () => [],
    },
    selectedRoles: {
        type: Array,
        default: () => [],
    },
})

const inputClass =
    'input input-bordered w-full rounded-2xl pl-12 h-14 bg-slate-50 border-slate-100 focus:border-primary focus:ring-2 focus:ring-primary/20'

const passwordInputClass =
    'input input-bordered w-full rounded-2xl pl-12 pr-12 h-14 bg-slate-50 border-slate-100 focus:border-primary focus:ring-2 focus:ring-primary/20'

const inputErrorClass =
    'input input-bordered w-full rounded-2xl pl-12 h-14 bg-red-50 border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'

const passwordInputErrorClass =
    'input input-bordered w-full rounded-2xl pl-12 pr-12 h-14 bg-red-50 border-red-300 focus:border-red-500 focus:ring-2 focus:ring-red-100'

const breadcrumbs = computed(() => [
    {
        label: 'Páginas',
        href: route('pagina.index'),
        icon: Home,
    },
    {
        label: 'Usuários',
        href: route('pagina.users.index', props.tenant.id),
    },
    {
        label: 'Editar usuário',
    },
])

const form = useForm({
    name: props.user?.name || '',
    email: props.user?.email || '',
    password: '',
    role: props.selectedRoles?.[0] || '',
})

const submit = () => {
    form.put(
        route('pagina.users.update', {
            tenant: props.tenant.id,
            user: props.user.id,
        }),
        {
            preserveScroll: true,

            onSuccess: () => {
                form.reset('password')
                showToast('Usuário atualizado com sucesso!', 'success')
            },

            onError: (errors) => {
                const message =
                    Object.values(errors).flat()[0] ||
                    'Erro ao atualizar usuário'

                showToast(message, 'error')
            },
        }
    )
}

const goBack = () => {
    router.visit(route('pagina.users.index', props.tenant.id))
}
</script>

<template>
    <CentralAdminLayout>
        <div class="space-y-6">
            <Breadcrumb :items="breadcrumbs" />

            <div class="bg-white rounded-[2rem] p-5 md:p-8 border border-white shadow-sm">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl md:text-3xl font-bold text-slate-800">
                            Editar usuário
                        </h1>

                        <p class="text-sm text-slate-500 mt-2">
                            Tenant:
                            <span class="font-semibold text-slate-700">
                                {{ props.tenant?.name }}
                            </span>
                        </p>
                    </div>

                    <button type="button" class="btn rounded-2xl bg-white border-slate-200 hover:bg-slate-50"
                        @click="goBack">
                        <ArrowLeft class="w-4 h-4" />
                        Voltar
                    </button>
                </div>
            </div>

            <form class="grid grid-cols-1 lg:grid-cols-[1fr_380px] gap-6" @submit.prevent="submit">
                <div class="rounded-3xl bg-white border border-slate-100 p-6 shadow-sm space-y-5">
                    <div>
                        <h2 class="text-lg font-bold text-slate-800">
                            Dados do usuário
                        </h2>

                        <p class="text-sm text-slate-500">
                            Atualize nome, email e senha do usuário.
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">
                            Nome
                        </label>

                        <div class="relative">
                            <User class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />

                            <input v-model="form.name" type="text"
                                :class="form.errors.name ? inputErrorClass : inputClass" placeholder="Nome do usuário"
                                :disabled="form.processing" />
                        </div>

                        <p v-if="form.errors.name" class="text-sm text-red-500">
                            {{ form.errors.name }}
                        </p>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-semibold text-slate-700">
                            Email
                        </label>

                        <div class="relative">
                            <Mail class="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-slate-400" />

                            <input v-model="form.email" type="email"
                                :class="form.errors.email ? inputErrorClass : inputClass"
                                placeholder="email@exemplo.com" :disabled="form.processing" />
                        </div>

                        <p v-if="form.errors.email" class="text-sm text-red-500">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <PasswordInput v-model="form.password" id="password" label="Nova senha"
                        placeholder="Deixe vazio para manter a senha atual" :error="form.errors.password"
                        :required="false" :disabled="form.processing" :input-class="passwordInputClass"
                        :input-error-class="passwordInputErrorClass">
                        <template #hint>
                            <p class="mt-1 text-xs text-slate-400">
                                Preencha somente se quiser alterar a senha.
                            </p>
                        </template>
                    </PasswordInput>
                </div>

                <div class="rounded-3xl bg-white border border-slate-100 p-6 shadow-sm space-y-5">
                    <div>
                        <div class="flex items-center gap-2">
                            <Shield class="w-5 h-5 text-primary" />

                            <h2 class="text-lg font-bold text-slate-800">
                                Perfil de acesso
                            </h2>
                        </div>

                        <p class="text-sm text-slate-500 mt-1">
                            Selecione apenas um perfil para este usuário.
                        </p>
                    </div>

                    <div v-if="props.roles.length" class="space-y-2 max-h-80 overflow-y-auto pr-1">
                        <label v-for="role in props.roles" :key="role.id"
                            class="flex items-center justify-between gap-3 rounded-2xl border p-3 cursor-pointer transition"
                            :class="form.role === role.name
                                ? 'border-primary/30 bg-primary/10'
                                : 'border-slate-100 bg-slate-50 hover:bg-slate-100'
                                ">
                            <div>
                                <p class="text-sm font-semibold text-slate-700">
                                    {{ role.name }}
                                </p>
                            </div>

                            <input v-model="form.role" type="radio" name="role" class="radio radio-primary"
                                :value="role.name" :disabled="form.processing" />
                        </label>
                    </div>

                    <div v-else class="rounded-2xl bg-slate-50 border border-slate-100 p-4 text-sm text-slate-500">
                        Nenhum perfil de acesso disponível.
                    </div>

                    <p v-if="form.errors.role" class="text-sm text-red-500">
                        {{ form.errors.role }}
                    </p>

                    <div class="border-t border-slate-100 pt-5">
                        <Button type="submit"
                            class="btn w-full rounded-2xl bg-primary hover:bg-primary-hover text-white border-0"
                            :disabled="form.processing">
                            <span v-if="form.processing" class="loading loading-spinner loading-sm"></span>

                            <Save v-else class="w-4 h-4" />

                            {{ form.processing ? 'Salvando...' : 'Salvar alterações' }}
                        </Button>
                    </div>
                </div>
            </form>
        </div>
    </CentralAdminLayout>
</template>
