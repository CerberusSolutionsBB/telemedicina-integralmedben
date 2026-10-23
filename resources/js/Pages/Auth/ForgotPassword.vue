<script setup>
import { computed, ref } from 'vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, KeyRound, Loader2, MailCheck } from 'lucide-vue-next';

// Usada pelo painel central e pelo parceiro: `rotas` diz para onde postar e voltar.
const props = defineProps({
    status: {
        type: String,
        default: null,
    },
    rotas: {
        type: Object,
        default: () => ({ email: 'password.email', login: 'login' }),
    },
    expiraMinutos: {
        type: Number,
        default: 60,
    },
});

const form = useForm({
    email: '',
});

const emailTocado = ref(false);
// E-mail para o qual o link saiu; a tela de sucesso só aparece depois de um envio aceito.
const enviadoPara = ref(null);

const formatoValido = computed(() => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim()));
const erroFormato = computed(() =>
    emailTocado.value && form.email && !formatoValido.value
        ? 'Digite um e-mail no formato nome@exemplo.com.'
        : null,
);
const erroEmail = computed(() => form.errors.email || erroFormato.value);
const podeEnviar = computed(() => formatoValido.value && !form.processing);

const submit = () => {
    emailTocado.value = true;
    if (!podeEnviar.value) return;

    form.clearErrors();
    form.post(route(props.rotas.email), {
        preserveScroll: true,
        onSuccess: () => {
            enviadoPara.value = form.email.trim();
        },
    });
};

const usarOutroEmail = () => {
    enviadoPara.value = null;
    form.reset();
    emailTocado.value = false;
};
</script>

<template>
    <GuestLayout>
        <Head title="Esqueci minha senha" />

        <!-- Sucesso: link enviado -->
        <div v-if="enviadoPara" class="space-y-6 text-center" aria-live="polite">
            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                <MailCheck class="h-7 w-7 text-primary" aria-hidden="true" />
            </div>

            <div class="space-y-2">
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Confira seu e-mail</h1>
                <p class="text-sm text-muted-foreground">
                    Enviamos o link para criar uma nova senha para
                    <strong class="break-all text-foreground">{{ enviadoPara }}</strong>.
                </p>
            </div>

            <ul class="space-y-2 rounded-lg border border-primary/30 bg-primary/5 p-4 text-left text-sm text-gray-700">
                <li>1. Abra o e-mail "Redefinição de senha".</li>
                <li>2. Clique em <strong>Redefinir senha</strong>.</li>
                <li>3. O link vale por {{ expiraMinutos }} minutos.</li>
            </ul>

            <p class="text-sm text-muted-foreground">Não chegou? Veja a caixa de spam ou envie de novo.</p>

            <div class="flex flex-col gap-2">
                <button type="button" :disabled="form.processing" @click="submit"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 font-medium text-white transition-colors duration-150 hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:opacity-60 motion-reduce:transition-none">
                    <Loader2 v-if="form.processing" class="h-4 w-4 motion-safe:animate-spin" aria-hidden="true" />
                    {{ form.processing ? 'Enviando…' : 'Enviar novamente' }}
                </button>
                <button type="button" @click="usarOutroEmail"
                    class="min-h-11 w-full rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors duration-150 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                    Usar outro e-mail
                </button>
            </div>

            <p v-if="form.errors.email" class="flex items-start justify-center gap-1.5 text-sm text-red-600" role="alert">
                <AlertCircle class="mt-0.5 h-4 w-4 flex-shrink-0" aria-hidden="true" />
                {{ form.errors.email }}
            </p>
        </div>

        <!-- Pedido do link -->
        <div v-else class="space-y-6">
            <div class="text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                    <KeyRound class="h-7 w-7 text-primary" aria-hidden="true" />
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground">Esqueceu a senha?</h1>
                <p class="mt-2 text-sm text-muted-foreground">
                    Digite o e-mail da sua conta. Vamos enviar um link para você criar uma nova senha.
                </p>
            </div>

            <form novalidate class="space-y-5" @submit.prevent="submit">
                <div class="space-y-1">
                    <InputLabel for="email" value="E-mail da conta *" />
                    <input id="email" v-model="form.email" type="email" inputmode="email" required autofocus
                        autocomplete="username" placeholder="nome@exemplo.com" aria-required="true"
                        :aria-invalid="!!erroEmail" :aria-describedby="erroEmail ? 'email-erro' : 'email-ajuda'"
                        @blur="emailTocado = true" @input="form.clearErrors('email')"
                        class="block min-h-11 w-full rounded-md border bg-white px-3 py-2 text-base text-gray-900 shadow-sm transition-colors duration-150 focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-0 sm:text-sm"
                        :class="erroEmail
                            ? 'border-red-500 focus-visible:ring-red-500'
                            : 'border-gray-300 focus-visible:border-primary focus-visible:ring-primary'" />

                    <p v-if="erroEmail" id="email-erro" class="flex items-start gap-1.5 text-sm text-red-600" role="alert">
                        <AlertCircle class="mt-0.5 h-4 w-4 flex-shrink-0" aria-hidden="true" />
                        {{ erroEmail }}
                    </p>
                    <p v-else id="email-ajuda" class="text-sm text-muted-foreground">
                        Use o mesmo e-mail com que você entra no sistema.
                    </p>
                </div>

                <button type="submit" :disabled="!podeEnviar"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-md bg-primary px-4 py-2 font-medium text-white transition-colors duration-150 hover:bg-primary-hover focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 motion-reduce:transition-none">
                    <Loader2 v-if="form.processing" class="h-4 w-4 motion-safe:animate-spin" aria-hidden="true" />
                    {{ form.processing ? 'Verificando e enviando…' : 'Enviar link de redefinição' }}
                </button>
            </form>
        </div>

        <div class="mt-6 border-t border-gray-200 pt-4 text-center">
            <Link :href="route(rotas.login)"
                class="inline-flex min-h-11 items-center gap-1 rounded-md px-2 text-sm font-medium text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                <ArrowLeft class="h-4 w-4" aria-hidden="true" />
                Voltar para o login
            </Link>
        </div>
    </GuestLayout>
</template>
