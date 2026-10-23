<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordInput from '@/Components/PasswordInput.vue';
import { Button } from '@/Components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from 'lucide-vue-next';

// Usada pelo painel central e pelo parceiro: `rotas` diz para onde postar e voltar.
const props = defineProps({
    email: {
        type: String,
        default: '',
    },
    token: {
        type: String,
        required: true,
    },
    rotas: {
        type: Object,
        default: () => ({ store: 'password.store', login: 'login' }),
    },
});

const form = useForm({
    token: props.token,
    email: props.email ?? '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route(props.rotas.store), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Redefinir senha" />

        <div class="mb-6 text-center">
            <h1 class="text-2xl font-bold tracking-tight text-foreground">Criar nova senha</h1>
            <p class="mt-2 text-sm text-muted-foreground">Use pelo menos 8 caracteres.</p>
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="email" value="E-mail" />
                <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required
                    autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <PasswordInput id="password" label="Nova senha" placeholder="Digite a nova senha" v-model="form.password"
                required :error="form.errors.password" />

            <PasswordInput id="password_confirmation" label="Confirmar nova senha" placeholder="Repita a nova senha"
                v-model="form.password_confirmation" required :error="form.errors.password_confirmation" />

            <Button type="submit" size="lg" class="w-full justify-center bg-primary hover:bg-primary-hover focus-visible:ring-primary" :disabled="form.processing">
                {{ form.processing ? 'Salvando...' : 'Redefinir senha' }}
            </Button>

            <div class="text-center">
                <Link :href="route(rotas.login)"
                    class="inline-flex items-center gap-1 text-sm font-medium text-primary hover:underline">
                    <ArrowLeft class="h-4 w-4" />
                    Voltar para o login
                </Link>
            </div>
        </form>
    </GuestLayout>
</template>
