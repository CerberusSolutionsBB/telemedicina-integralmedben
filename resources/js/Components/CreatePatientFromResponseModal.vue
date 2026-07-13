<script setup>
import { useForm } from "@inertiajs/vue3";
import { watch } from "vue";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import {
    User,
    Mail,
    Phone,
    CreditCard,
    Calendar,
    Heart,
    MapPin,
    Hash,
} from "lucide-vue-next";

const props = defineProps({
    open: { type: Boolean, default: false },
    response: { type: Object, default: null },
    fields: { type: Array, default: () => [] },
});

const emit = defineEmits(["update:open", "patient-created"]);

const form = useForm({
    nome: "",
    cpf: "",
    rg: "",
    data_nascimento: "",
    sexo: "",
    email: "",
    numero: "",
    status: false,
    response_id: null,
    enderecos: {
        cep: "",
        logradouro: "",
        numero: "",
        complemento: "",
        bairro: "",
        cidade: "",
        estado: "",
    },
});

watch(
    () => props.response,
    (response) => {
        if (!response) return;

        form.response_id = response.id;
        const answers = response.answers || {};

        for (const field of props.fields) {
            const value = answers[field.id];
            if (value === null || value === undefined || value === "") continue;

            const label = (field.label || "").toLowerCase();
            const type = (field.type || "").toLowerCase();
            const stringValue = String(value);

            if (type === "nome" || label.includes("nome") || label.includes("name")) {
                if (!form.nome) form.nome = stringValue;
            } else if (type === "email" || label.includes("email") || label.includes("e-mail")) {
                if (!form.email) form.email = stringValue;
            } else if (type === "cpf" || label.includes("cpf")) {
                form.cpf = stringValue;
            } else if (type === "rg" || label.includes("rg")) {
                form.rg = stringValue;
            } else if (type === "phone" || label.includes("telefone") || label.includes("celular") || label.includes("phone")) {
                form.numero = stringValue;
            } else if (type === "date" || label.includes("nascimento") || label.includes("data de nasc")) {
                form.data_nascimento = stringValue;
            } else if (type === "sexo" || label.includes("sexo") || label.includes("gênero")) {
                form.sexo = stringValue.toLowerCase().includes("femin") ? "feminino" : "masculino";
            } else if (label.includes("cep") || label.includes("código postal")) {
                form.enderecos.cep = stringValue;
            } else if (label.includes("logradouro") || label.includes("rua") || label.includes("avenida")) {
                form.enderecos.logradouro = stringValue;
            } else if ((label.includes("número") || label.includes("numero")) && (label.includes("endereço") || label.includes("residencial") || label.includes("logradouro") || label.includes("rua"))) {
                form.enderecos.numero = stringValue;
            } else if (label.includes("complemento")) {
                form.enderecos.complemento = stringValue;
            } else if (label.includes("bairro")) {
                form.enderecos.bairro = stringValue;
            } else if (label.includes("cidade") || label.includes("município")) {
                form.enderecos.cidade = stringValue;
            } else if (label.includes("estado") || label.includes("uf")) {
                form.enderecos.estado = stringValue;
            }
        }
    },
    { immediate: true }
);

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

const submit = () => {
    form.post(route("patients.store"), {
        preserveScroll: true,
        onSuccess: () => {
            emit("patient-created", props.response);
            form.reset();
            emit("update:open", false);
        },
    });
};
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="sm:max-w-[560px] max-h-[90vh] overflow-y-auto">
            <DialogHeader>
                <DialogTitle>Criar Paciente a partir da Resposta</DialogTitle>
            </DialogHeader>

            <!-- Respostas do formulário como referência -->
            <div v-if="response?.answers && Object.keys(response.answers).length" class="rounded-lg border bg-gray-50 p-4 space-y-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">
                    Respostas do formulário
                </p>
                <div v-for="field in fields" :key="field.id" class="flex items-center justify-between text-sm">
                    <span class="text-gray-500">{{ field.label }}</span>
                    <span class="font-medium text-gray-900">
                        {{ response.answers[field.id] || '-' }}
                    </span>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-4 mt-2">
                <div class="space-y-4">
                    <div>
                        <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                            <User class="w-4 h-4 text-gray-400" />
                            Nome <span class="text-red-500">*</span>
                        </Label>
                        <Input v-model="form.nome" type="text" placeholder="Nome completo"
                            :class="form.errors.nome ? 'border-red-500' : ''" />
                        <p v-if="form.errors.nome" class="mt-1 text-sm text-red-600">{{ form.errors.nome }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                                <CreditCard class="w-4 h-4 text-gray-400" />
                                CPF
                            </Label>
                            <Input v-model="form.cpf" type="text" inputmode="numeric" maxlength="14"
                                placeholder="000.000.000-00" @input="maskCpf" />
                        </div>
                        <div>
                            <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                                <Hash class="w-4 h-4 text-gray-400" />
                                RG
                            </Label>
                            <Input v-model="form.rg" type="text" placeholder="Número do RG" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                                <Phone class="w-4 h-4 text-gray-400" />
                                Telefone
                            </Label>
                            <Input v-model="form.numero" type="text" inputmode="numeric" maxlength="15"
                                placeholder="(00) 00000-0000" @input="maskPhone" />
                        </div>
                        <div>
                            <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                                <Calendar class="w-4 h-4 text-gray-400" />
                                Data de Nascimento
                            </Label>
                            <Input v-model="form.data_nascimento" type="date" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                                <Heart class="w-4 h-4 text-gray-400" />
                                Sexo
                            </Label>
                            <select v-model="form.sexo"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-sm focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                <option value="">Selecione...</option>
                                <option value="masculino">Masculino</option>
                                <option value="feminino">Feminino</option>
                            </select>
                        </div>
                        <div>
                            <Label class="flex items-center gap-1 text-gray-700 pb-1 font-medium">
                                <Mail class="w-4 h-4 text-gray-400" />
                                E-mail
                            </Label>
                            <Input v-model="form.email" type="email" placeholder="paciente@exemplo.com" />
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-4 mt-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">
                            Endereço
                        </p>
                        <div class="grid grid-cols-2 gap-3">
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">CEP</Label>
                            <Input v-model="form.enderecos.cep" type="text" maxlength="9" placeholder="00000-000" />
                        </div>
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">Logradouro</Label>
                            <Input v-model="form.enderecos.logradouro" type="text" placeholder="Rua, Avenida..." />
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3 mt-3">
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">Número</Label>
                            <Input v-model="form.enderecos.numero" type="text" placeholder="Nº" />
                        </div>
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">Complemento</Label>
                            <Input v-model="form.enderecos.complemento" type="text" placeholder="Apto, Bloco..." />
                        </div>
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">Bairro</Label>
                            <Input v-model="form.enderecos.bairro" type="text" placeholder="Bairro" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mt-3">
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">Cidade</Label>
                            <Input v-model="form.enderecos.cidade" type="text" placeholder="Cidade" />
                        </div>
                        <div>
                            <Label class="text-gray-700 pb-1 font-medium text-sm">Estado</Label>
                            <Input v-model="form.enderecos.estado" type="text" maxlength="2" placeholder="UF" />
                        </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-gray-100">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">
                        Cancelar
                    </Button>
                    <Button type="submit" :disabled="form.processing || !form.nome">
                        {{ form.processing ? 'Salvando...' : 'Criar Paciente' }}
                    </Button>
                </div>
            </form>
        </DialogContent>
    </Dialog>
</template>
