<script setup>
import { ref, computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { showToast } from '@/Utils/toast';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import { Button } from '@/Components/ui/button';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';

const props = defineProps({
    canResetPassword: {
        type: Boolean,
        default: false,
    },
    status: {
        type: String,
        default: null,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);
const capsLockOn = ref(false);
const emailTouched = ref(false);
const passwordTouched = ref(false);

// Validação de email em tempo real
const emailValid = computed(() => {
    if (!form.email || !emailTouched.value) return null;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(form.email);
});

// Indicador de força da senha
const passwordStrength = computed(() => {
    if (!form.password) return 0;
    let strength = 0;
    if (form.password.length >= 8) strength += 25;
    if (/[a-z]/.test(form.password) && /[A-Z]/.test(form.password)) strength += 25;
    if (/\d/.test(form.password)) strength += 25;
    if (/[^a-zA-Z0-9]/.test(form.password)) strength += 25;
    return strength;
});

const passwordStrengthText = computed(() => {
    const strength = passwordStrength.value;
    if (strength === 0) return '';
    if (strength <= 25) return 'Fraca';
    if (strength <= 50) return 'Regular';
    if (strength <= 75) return 'Boa';
    return 'Excelente';
});

const passwordStrengthColor = computed(() => {
    const strength = passwordStrength.value;
    if (strength <= 25) return 'bg-destructive';
    if (strength <= 50) return 'bg-yellow-500';
    if (strength <= 75) return 'bg-blue-500';
    return 'bg-green-500';
});

// Detectar Caps Lock
const checkCapsLock = (event) => {
    capsLockOn.value = event.getModifierState('CapsLock');
};

const togglePassword = () => {
    showPassword.value = !showPassword.value;
};

const submit = () => {
    emailTouched.value = true;
    passwordTouched.value = true;

    // Validação client-side antes de enviar
    if (!emailValid.value) {
        showToast('Por favor, insira um email válido.', 'error');
        return;
    }

    if (form.password.length < 6) {
        showToast('A senha deve ter pelo menos 6 caracteres.', 'error');
        return;
    }

    form.post(route('login'), {
        onSuccess: () => {
            showToast('Login realizado com sucesso! 🎉', 'success');
        },
        onError: (errors) => {
            const errorMessages = Object.values(errors).flat();
            showToast(errorMessages[0] || 'Erro ao fazer login. Tente novamente.', 'error');
        },
        onFinish: () => {
            form.reset('password');
            passwordTouched.value = false;
        },
    });
};
</script>

<template>
    <GuestLayout>

        <Head title="Entrar" />
        <div>
            <div class="w-full bg-card">
                <div class="text-center">
                    <div
                        class="mx-auto h-16 w-16 bg-gradient-to-br from-primary to-primary-hover rounded-2xl flex items-center justify-center shadow-lg mb-6 transform transition-transform hover:scale-105">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-white" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>

                    <h2 class="text-3xl font-bold tracking-tight text-foreground sm:text-4xl">
                        Bem-vindo de volta
                    </h2>
                    <p class="mt-3 text-base text-muted-foreground">
                        Entre com suas credenciais para acessar sua conta
                    </p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="rounded-lg bg-green-50 p-4 border border-green-200 animate-pulse"
                    role="alert">
                    <div class="flex">
                        <svg class="h-5 w-5 text-green-400 mr-3" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm font-medium text-green-800">{{ status }}</p>
                    </div>
                </div>

                <!-- Formulário -->
                <form @submit.prevent="submit" class="mt-8 space-y-6">

                    <!-- Campo Email -->
                    <div class="relative">
                        <InputLabel for="email" value="Email"
                            class="block text-sm font-semibold text-foreground mb-2" />
                        <div class="relative">
                            <!-- Ícone Email -->
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                    <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                </svg>
                            </div>

                            <TextInput id="email" type="email" v-model="form.email" @blur="emailTouched = true" required
                                autofocus autocomplete="username" placeholder="seu@email.com"
                                class="block w-full pl-10 pr-10 py-3 border-2 border-input rounded-xl text-foreground placeholder-muted-foreground focus:border-primary focus:ring-4 focus:ring-primary/20 transition-all duration-200 ease-in-out hover:border-border"
                                :class="{
                                    'border-destructive focus:border-destructive focus:ring-destructive/20': emailValid === false,
                                    'border-green-300 focus:border-green-500 focus:ring-green-500/20': emailValid === true
                                }" />

                            <!-- Ícone de Validação -->
                            <div v-if="emailValid !== null"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <svg v-if="emailValid" class="h-5 w-5 text-green-500" fill="currentColor"
                                    viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                                        clip-rule="evenodd" />
                                </svg>
                                <svg v-else class="h-5 w-5 text-destructive" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>
                        </div>

                        <!-- Mensagem de Erro Email -->
                        <InputError class="mt-2 text-sm" :message="form.errors.email" />
                        <p v-if="emailValid === false" class="mt-1 text-xs text-destructive flex items-center">
                            <svg class="h-3 w-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            Email inválido. Verifique o formato.
                        </p>
                    </div>

                    <!-- Campo Senha -->
                    <div class="relative">
                        <InputLabel for="password" value="Senha"
                            class="block text-sm font-semibold text-foreground mb-2" />

                        <!-- Aviso Caps Lock -->
                        <div v-if="capsLockOn"
                            class="absolute right-0 top-0 flex items-center text-xs text-amber-600 font-medium animate-bounce">
                            <svg class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Caps Lock ativo
                        </div>

                        <div class="relative">
                            <!-- Ícone Cadeado -->
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-muted-foreground" xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd"
                                        d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                        clip-rule="evenodd" />
                                </svg>
                            </div>

                            <TextInput id="password" :type="showPassword ? 'text' : 'password'" v-model="form.password"
                                @keydown="checkCapsLock" @keyup="checkCapsLock" @blur="passwordTouched = true" required
                                autocomplete="current-password" placeholder="••••••••"
                                class="block w-full pl-10 pr-12 py-3 border-2 border-input rounded-xl text-foreground placeholder-muted-foreground focus:border-primary focus:ring-4 focus:ring-primary/20 transition-all duration-200 ease-in-out hover:border-border" />

                            <!-- Botão Toggle Senha -->
                            <button type="button" @click="togglePassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-muted-foreground hover:text-foreground focus:outline-none focus:text-primary transition-colors"
                                :aria-pressed="showPassword"
                                :aria-label="showPassword ? 'Ocultar senha' : 'Mostrar senha'">
                                <!-- Ícone Olho Aberto (senha oculta) -->
                                <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <!-- Ícone Olho Fechado (senha visível) -->
                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />
                                </svg>
                            </button>
                        </div>

                        <!-- Indicador de Força da Senha -->
                        <div v-if="form.password && passwordTouched" class="mt-2">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-xs text-muted-foreground">Força da senha:</span>
                                <span class="text-xs font-medium" :class="{
                                    'text-destructive': passwordStrength <= 25,
                                    'text-yellow-600': passwordStrength <= 50,
                                    'text-blue-600': passwordStrength <= 75,
                                    'text-green-600': passwordStrength > 75
                                }">{{ passwordStrengthText }}</span>
                            </div>
                            <div class="w-full bg-muted rounded-full h-1.5 overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500 ease-out"
                                    :class="passwordStrengthColor" :style="{ width: passwordStrength + '%' }"></div>
                            </div>
                        </div>

                        <InputError class="mt-2 text-sm" :message="form.errors.password" />
                    </div>

                    <!-- Opções Extras -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center cursor-pointer group">
                            <Checkbox name="remember" v-model:checked="form.remember"
                                class="rounded border-input text-primary focus:ring-primary transition-colors" />
                            <span
                                class="ml-2 text-sm text-muted-foreground group-hover:text-foreground transition-colors">Lembrar-me</span>
                        </label>

                        <Link v-if="canResetPassword" :href="route('password.request')"
                            class="text-sm font-medium text-primary hover:text-primary-hover hover:underline transition-colors">
                            Esqueceu a senha?
                        </Link>
                    </div>

                    <!-- Botão de Submit -->
                    <div class="pt-2">
                        <Button type="submit" :disabled="form.processing"
                            class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-xl shadow-sm text-base font-medium text-white bg-gradient-to-r from-primary to-primary-hover hover:from-primary-hover hover:to-primary focus:outline-none focus:ring-4 focus:ring-primary/30 disabled:opacity-50 disabled:cursor-not-allowed transform transition-all duration-200 hover:scale-[1.02] active:scale-[0.98]">
                            <svg v-if="form.processing" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                    stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor"
                                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                </path>
                            </svg>
                            {{ form.processing ? 'Entrando...' : 'Entrar' }}
                        </Button>
                    </div>

                    <!-- Link para Registro -->
                    <div class="text-center pt-4 border-t border-border">
                        <p class="text-sm text-muted-foreground">
                            Não tem uma conta?
                            <Link :href="route('register')"
                                class="font-medium text-primary hover:text-primary-hover hover:underline transition-colors">
                                Cadastre-se gratuitamente
                            </Link>
                        </p>
                    </div>
                </form>

                <!-- Footer com Segurança -->
                <div class="mt-6 flex items-center justify-center space-x-2 text-xs text-muted-foreground">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                    <span>Conexão segura com criptografia SSL</span>
                </div>
            </div>
        </div>
    </GuestLayout>
</template>

<style scoped>
/* Animações personalizadas */
@keyframes shake {

    0%,
    100% {
        transform: translateX(0);
    }

    10%,
    30%,
    50%,
    70%,
    90% {
        transform: translateX(-4px);
    }

    20%,
    40%,
    60%,
    80% {
        transform: translateX(4px);
    }
}

.animate-shake {
    animation: shake 0.5s ease-in-out;
}

/* Melhoria de foco para acessibilidade */
input:focus-visible {
    outline: 2px solid hsl(var(--primary));
    outline-offset: 2px;
}

/* Transição suave para o card */
.shadow-3xl {
    box-shadow: 0 35px 60px -15px rgba(0, 0, 0, 0.15);
}
</style>
