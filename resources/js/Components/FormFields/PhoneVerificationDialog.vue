<script setup>
import { computed, nextTick, onUnmounted, ref, watch } from "vue";
import { LoaderCircle, MessageSquareText } from "lucide-vue-next";
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from "@/Components/ui/dialog";

// Confirmação por SMS dos telefones, aberta ao clicar em enviar o cadastro
const props = defineProps({
    slug: { type: String, required: true },
    // [{ field, phone }] — telefones (somente números) que ainda precisam de confirmação
    pending: { type: Array, default: () => [] },
    // Variáveis de cor do formulário (--form-primary, --form-button): o dialog é renderizado fora do form
    theme: { type: Object, default: () => ({}) },
});

const open = defineModel("open", { type: Boolean, default: false });
const emit = defineEmits(["verified", "complete", "change-number"]);

const index = ref(0);
const status = ref("idle"); // idle | sending | sent | verifying
const code = ref("");
const error = ref("");
const countdown = ref(0);
const codeInput = ref(null);
let timer = null;

const current = computed(() => props.pending[index.value] || null);

const formatPhone = (n) =>
    n.length === 11 ? `(${n.slice(0, 2)})${n.slice(2, 7)}-${n.slice(7)}` : `(${n.slice(0, 2)})${n.slice(2, 6)}-${n.slice(6)}`;

const startCountdown = (seconds) => {
    clearInterval(timer);
    countdown.value = seconds;
    timer = setInterval(() => {
        countdown.value = Math.max(0, countdown.value - 1);
        if (!countdown.value) clearInterval(timer);
    }, 1000);
};
onUnmounted(() => clearInterval(timer));

const messageFrom = (e, fallback) =>
    e.response?.data?.error
    || Object.values(e.response?.data?.errors || {})[0]?.[0]
    || e.response?.data?.message
    || fallback;

const sendCode = async () => {
    if (!current.value) return;
    status.value = "sending";
    error.value = "";
    try {
        const { data } = await window.axios.post(route("forms.public.phone.send", props.slug), {
            field_id: current.value.field.id,
            phone: current.value.phone,
        });
        startCountdown(data.retry_after || 60);
    } catch (e) {
        error.value = messageFrom(e, "Não foi possível enviar o código. Tente novamente.");
        if (e.response?.data?.retry_after) startCountdown(e.response.data.retry_after);
    }
    status.value = "sent";
    await nextTick();
    codeInput.value?.focus();
};

const verifyCode = async () => {
    if (code.value.length !== 6 || status.value === "verifying") return;
    status.value = "verifying";
    error.value = "";
    try {
        const { data } = await window.axios.post(route("forms.public.phone.verify", props.slug), {
            field_id: current.value.field.id,
            phone: current.value.phone,
            code: code.value,
        });
        emit("verified", current.value.field.id, data.token);
        next();
    } catch (e) {
        error.value = messageFrom(e, "Não foi possível confirmar o código.");
        status.value = "sent";
        code.value = "";
    }
};

// Próximo telefone pendente ou conclusão (o cadastro é reenviado pelo pai)
const next = () => {
    if (index.value < props.pending.length - 1) {
        index.value++;
        reset();
        sendCode();
        return;
    }
    open.value = false;
    emit("complete");
};

const reset = () => {
    code.value = "";
    error.value = "";
    status.value = "idle";
};

const onCodeInput = (event) => {
    code.value = event.target.value.replace(/\D/g, "").substring(0, 6);
    event.target.value = code.value;
    if (code.value.length === 6) verifyCode();
};

const changeNumber = () => {
    open.value = false;
    emit("change-number", current.value?.field);
};

// Ao abrir, já envia o código para o primeiro telefone
watch(open, (value) => {
    if (!value) return;
    index.value = 0;
    reset();
    sendCode();
});
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md" :style="theme" @interact-outside.prevent>
            <DialogHeader>
                <div class="mx-auto mb-2 flex h-12 w-12 items-center justify-center rounded-full"
                    :style="{ backgroundColor: 'color-mix(in srgb, var(--form-primary, #0891b2) 15%, transparent)' }">
                    <MessageSquareText class="h-6 w-6" :style="{ color: 'var(--form-primary, #0891b2)' }" />
                </div>
                <DialogTitle class="text-center">Confirme seu celular</DialogTitle>
                <DialogDescription class="text-center">
                    <template v-if="status === 'sending'">Enviando código por SMS...</template>
                    <template v-else-if="current">
                        Enviamos um código de 6 dígitos por SMS para
                        <strong class="whitespace-nowrap text-gray-900">{{ formatPhone(current.phone) }}</strong>.
                    </template>
                    <span v-if="pending.length > 1" class="mt-1 block text-xs">
                        {{ current?.field.label }} ({{ index + 1 }} de {{ pending.length }})
                    </span>
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="verifyCode">
                <div class="flex justify-center">
                    <input ref="codeInput" :value="code" type="text" inputmode="numeric" autocomplete="one-time-code"
                        maxlength="6" placeholder="000000" aria-label="Código de verificação"
                        :disabled="status === 'sending' || status === 'verifying'"
                        class="w-48 rounded-lg border border-gray-300 px-3 py-3 text-center font-mono text-2xl tracking-[0.4em] focus:outline-none focus:ring-2 disabled:bg-gray-50"
                        :style="{ '--tw-ring-color': 'var(--form-primary, #0891b2)' }" @input="onCodeInput" />
                </div>

                <p v-if="error" role="alert" class="text-center text-sm font-medium text-red-600">{{ error }}</p>

                <button type="submit" :disabled="code.length !== 6 || status === 'verifying' || status === 'sending'"
                    class="flex w-full items-center justify-center gap-2 rounded-lg px-4 py-3 text-sm font-bold uppercase text-white transition-opacity disabled:opacity-60"
                    :style="{ backgroundColor: 'var(--form-button, var(--form-primary, #0891b2))' }">
                    <LoaderCircle v-if="status === 'verifying'" class="h-4 w-4 animate-spin" />
                    {{ status === 'verifying' ? 'Confirmando...' : 'Confirmar e enviar cadastro' }}
                </button>

                <div class="flex items-center justify-between text-sm">
                    <button type="button" class="font-medium text-gray-600 hover:underline" @click="changeNumber">
                        Alterar número
                    </button>
                    <button type="button" :disabled="countdown > 0 || status === 'sending'"
                        class="font-medium text-gray-600 hover:underline disabled:no-underline disabled:opacity-60"
                        @click="sendCode">
                        {{ countdown > 0 ? `Reenviar em ${countdown}s` : 'Reenviar código' }}
                    </button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
