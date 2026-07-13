<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { FileText, Download, Pencil, Trash2, Eye } from "lucide-vue-next";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import SearchInput from "@/Components/SearchInput.vue";
import PaginationSimple from "@/Components/PaginationSimple.vue";

const props = defineProps({
    patients: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["delete-patient"]);

const page = usePage();

const search = ref(page.url?.split('?')[1]?.includes('search=') ? new URLSearchParams(page.url.split('?')[1]).get('search') || '' : '');
const statusFilter = ref(page.url?.split('?')[1]?.includes('status=') ? new URLSearchParams(page.url.split('?')[1]).get('status') || '' : '');

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

const formatDateShort = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('pt-BR');
};

const formatCpf = (cpf) => {
    if (!cpf) return '-';
    const cleaned = cpf.replace(/\D/g, '');
    if (cleaned.length !== 11) return cpf;
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

const questions = computed(() => {
    const data = props.patients?.data;
    if (!data || data.length === 0) return [];
    return data[0]?.answers?.map(a => a.question) || [];
});

const getAnswer = (patient, questionId) => {
    const answer = patient.answers?.find(a => a.question_id === questionId);
    return answer?.answer || '-';
};

const sexoLabel = (sexo) => {
    if (!sexo) return '-';
    const map = { masculino: 'Masculino', feminino: 'Feminino' };
    return map[sexo.toLowerCase()] || sexo;
};

const registroLabel = (registro) => {
    const map = {
        'formulario': 'Formulario',
        'form-dinamico': 'Form. Dinamico',
        'importacao': 'Importacao',
        'form-publico': 'Form. Publico',
    };
    return map[registro] || registro;
};

const registroColor = (registro) => {
    const map = {
        'formulario': 'bg-blue-100 text-blue-800',
        'form-dinamico': 'bg-purple-100 text-purple-800',
        'importacao': 'bg-orange-100 text-orange-800',
        'form-publico': 'bg-teal-100 text-teal-800',
    };
    return map[registro] || 'bg-gray-100 text-gray-800';
};

const navigate = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value !== '' && statusFilter.value !== null) params.status = statusFilter.value;

    router.visit(route('patients.index', params), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const onSearch = (value) => {
    search.value = value;
    navigate();
};

const onStatusChange = () => {
    navigate();
};
</script>

<template>
    <div class="p-3 sm:p-6 overflow-x-auto">
        <div class="flex flex-wrap items-center justify-between gap-2 mb-4">
            <h1 class="text-xl sm:text-2xl font-bold">Pacientes Cadastrados</h1>
            <a :href="route('patients.report')" target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 text-white text-sm font-medium rounded hover:bg-cyan-700 transition-colors">
                <Download class="w-4 h-4" />
                Relatório Geral
            </a>
        </div>

        <div class="flex flex-wrap items-center gap-3 mb-4">
            <SearchInput
                v-model="search"
                placeholder="Buscar por nome, CPF ou e-mail..."
                class="flex-1 min-w-[200px]"
                @search="onSearch"
            />

            <select
                v-model="statusFilter"
                @change="onStatusChange"
                class="border border-gray-300 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none"
            >
                <option value="">Todos os status</option>
                <option value="1">Ativo</option>
                <option value="0">Inativo</option>
            </select>
        </div>

        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead class="text-center whitespace-nowrap">Código</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Nome</TableHead>
                    <TableHead class="text-center whitespace-nowrap">CPF</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Email</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Sexo</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Nascimento</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Status</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Registro</TableHead>
                    <TableHead class="text-center whitespace-nowrap">Cadastro</TableHead>
                    <TableHead v-for="question in questions" :key="question.id" class="text-center whitespace-nowrap">
                        <div class="truncate max-w-[150px]" :title="question.title">
                            {{ question.title }}
                        </div>
                    </TableHead>
                    <TableHead class="text-center whitespace-nowrap">Ações</TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <TableRow v-for="patient in patients.data" :key="patient.id">
                    <TableCell class="text-center font-medium">
                        {{ patient.id }}
                    </TableCell>

                    <TableCell class="text-center font-medium text-gray-900">
                        {{ patient.nome || '-' }}
                    </TableCell>

                    <TableCell class="text-center">
                        <span class="font-mono text-sm">{{ formatCpf(patient.cpf) }}</span>
                    </TableCell>

                    <TableCell class="text-center">
                        {{ patient.email || '-' }}
                    </TableCell>

                    <TableCell class="text-center">
                        {{ sexoLabel(patient.sexo) }}
                    </TableCell>

                    <TableCell class="text-center">
                        {{ formatDateShort(patient.data_nascimento) }}
                    </TableCell>

                    <TableCell class="text-center">
                        <span v-if="patient.status" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            Ativo
                        </span>
                        <span v-else class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            Inativo
                        </span>
                    </TableCell>

                    <TableCell class="text-center">
                        <span v-if="patient.status_registro" :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', registroColor(patient.status_registro)]">
                            {{ registroLabel(patient.status_registro) }}
                        </span>
                        <span v-else class="text-gray-400 text-xs">-</span>
                    </TableCell>

                    <TableCell class="text-center">
                        {{ formatDate(patient.created_at) }}
                    </TableCell>

                    <TableCell v-for="question in questions" :key="question.id" class="text-center">
                        <div class="truncate max-w-[200px]" :title="getAnswer(patient, question.id)">
                            {{ getAnswer(patient, question.id) }}
                        </div>
                    </TableCell>

                    <TableCell class="text-center">
                        <div class="flex gap-3 justify-center">
                            <Eye class="w-4 h-4 cursor-pointer hover:text-cyan-600"
                                @click="router.visit(route('patients.show', patient.id))" title="Ver detalhes" />
                            <a :href="route('patients.pdf', patient.id)" target="_blank" class="inline-block">
                                <FileText class="w-4 h-4 cursor-pointer hover:text-green-600" />
                            </a>
                            <Pencil class="w-4 h-4 cursor-pointer hover:text-cyan-600"
                                @click="router.visit(route('patients.edit', patient.id))" />
                            <Trash2 class="w-4 h-4 cursor-pointer hover:text-red-600"
                                @click="emit('delete-patient', patient)" />
                        </div>
                    </TableCell>
                </TableRow>

                <TableRow v-if="patients.data && patients.data.length === 0">
                    <TableCell :colspan="10 + questions.length" class="text-center py-8 text-gray-500">
                        Nenhum paciente encontrado.
                    </TableCell>
                </TableRow>
            </TableBody>
        </Table>

        <PaginationSimple
            :data="patients"
            :links="patients.links || []"
            :has-data="(patients.data?.length ?? 0) > 0"
            label="pacientes"
        />
    </div>
</template>
