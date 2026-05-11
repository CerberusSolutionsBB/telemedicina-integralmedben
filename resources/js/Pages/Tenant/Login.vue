<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Button } from '@/Components/ui/button';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const isLoading = ref(false);
const page = usePage();
const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const isValidEmail = computed(() => {
    if (!form.email) return null;
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email);
});

const canSubmit = computed(() => {
    return form.email && form.password && isValidEmail.value && !form.processing;
});

const tenantPublic = computed(() => page.props.tenant_public ?? {})

const submit = () => {
    if (!canSubmit.value) return;

    isLoading.value = true;

    form.post(route('tenant.login.store'), {
        onFinish: () => {
            form.reset('password');
            isLoading.value = false;
        },
        onError: () => {
            isLoading.value = false;
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head title="Área Administrativa" />

        <!-- Header -->
        <div class="mb-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16
                        bg-gradient-to-br from-cyan-500 to-cyan-600
                        rounded-2xl mb-4 shadow-lg shadow-cyan-500/30">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                <p v-if="tenantPublic && tenantPublic?.descricao != null">
                    {{ tenantPublic.descricao }}
                </p>
                Área Administrativa
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Acesse o painel de controle
            </p>
        </div>

        <!-- Erro global -->
        <div v-if="form.errors?.general"
            class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg flex items-start gap-3">
            <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <p class="text-sm text-red-700">{{ form.errors.general }}</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <!-- Email -->
            <div class="space-y-1">
                <InputLabel for="email" value="Email" class="font-medium text-gray-700" />

                <div class="relative">
                    <TextInput id="email" type="email" v-model="form.email" required autofocus autocomplete="username"
                        placeholder="admin@exemplo.com" :class="[
                            'block w-full px-4 py-3 rounded-lg border transition-all duration-200',
                            'focus:ring-2 focus:ring-cyan-500 focus:border-transparent',
                            form.errors.email
                                ? 'border-red-300 focus:ring-red-500 bg-red-50'
                                : 'border-gray-300 hover:border-gray-400',
                            isValidEmail === true ? 'border-green-300 focus:ring-green-500' : ''
                        ]" />
                    <div v-if="isValidEmail === true" class="absolute right-3 top-1/2 -translate-y-1/2">
                        <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </div>

                <InputError class="mt-1" :message="form.errors.email" />
            </div>

            <!-- Senha - Usando seu PasswordInput -->
            <div class="space-y-1">
                <div class="flex items-center justify-between">
                    <InputLabel for="password" class="font-medium text-gray-700" />
                </div>

                <PasswordInput id="password" v-model="form.password" placeholder="Digite sua senha" required
                    :error="form.errors.password" :input-class="[
                        'block w-full px-4 py-3 rounded-lg border transition-all duration-200',
                        'focus:ring-2 focus:ring-cyan-500 focus:border-transparent',
                        'border-gray-300 hover:border-gray-400'
                    ].join(' ')" :input-error-class="[
                        'block w-full px-4 py-3 rounded-lg border transition-all duration-200',
                        'focus:ring-2 focus:ring-red-500 focus:border-transparent',
                        'border-red-300 bg-red-50'
                    ].join(' ')" />
            </div>

            <!-- Lembrar-me -->
            <div class="flex items-center">
                <Checkbox id="remember" v-model:checked="form.remember"
                    class="rounded border-gray-300 text-cyan-600 focus:ring-cyan-500" />
                <label for="remember" class="ml-2 text-sm text-gray-600 cursor-pointer select-none">
                    Manter conectado neste dispositivo
                </label>
            </div>

            <!-- Submit -->
            <div class="pt-2">
                <Button type="submit" variant="primary" size="lg" class="w-full justify-center" :disabled="!canSubmit"
                    :class="[
                        'transition-all duration-200',
                        !canSubmit ? 'opacity-50 cursor-not-allowed' : 'hover:shadow-lg hover:shadow-cyan-500/25'
                    ]">
                    <svg v-if="isLoading" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" fill="none"
                        viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                    {{ isLoading ? 'Entrando...' : 'Entrar no Sistema' }}
                </Button>
            </div>

            <!-- Footer -->
            <!-- <div class="pt-4 flex items-center justify-between border-t border-gray-200">
                <Link :href="route('public_form.show')"
                    class="group text-sm text-gray-500 hover:text-gray-900 font-medium transition-colors flex items-center gap-1">
                    <svg class="w-4 h-4 transition-transform group-hover:-translate-x-1" fill="none"
                        stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Voltar ao formulário
                </Link>
            </div> -->
        </form>
    </GuestLayout>
</template>
