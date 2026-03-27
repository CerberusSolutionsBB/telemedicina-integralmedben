<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import {
    CheckCircle,
    FileText,
    ArrowLeft
} from "lucide-vue-next";

defineProps({
    form: {
        type: Object,
        required: true
    }
});
</script>

<template>

    <Head title="Obrigado!" />

    <div class="min-h-screen bg-gray-50 flex items-center justify-center p-4">
        <div class="max-w-md w-full text-center">
            <!-- Ícone de Sucesso -->
            <div class="inline-flex items-center justify-center w-20 h-20 bg-green-100 rounded-full mb-6">
                <CheckCircle class="w-10 h-10 text-green-600" />
            </div>

            <!-- Mensagem -->
            <h1 class="text-2xl font-bold text-gray-900 mb-2">
                Obrigado!
            </h1>
            <p class="text-gray-600 mb-2">
                Sua resposta para "{{ form.title }}" foi enviada com sucesso.
            </p>
            <p class="text-sm text-gray-500">
                Agradecemos sua participação.
            </p>

            <!-- Ações -->
            <div class="mt-8 space-y-3">
                <Link :href="route('forms.index')">
                    <Button variant="outline" class="w-full gap-2">
                        <FileText class="w-4 h-4" />
                        Ver mais formulários
                    </Button>
                </Link>

                <Button variant="ghost" class="w-full gap-2 text-gray-500" @click="window.location.reload()">
                    <ArrowLeft class="w-4 h-4" />
                    Responder novamente
                </Button>
            </div>
        </div>
    </div>
</template>
