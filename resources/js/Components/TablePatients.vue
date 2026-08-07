<script setup>
import { ref, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { FileText, Download, Pencil, Trash2, Eye, Search } from "lucide-vue-next";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import PaginationSimple from "@/Components/PaginationSimple.vue";

const props = defineProps({
    patients: {
        type: Object,
        required: true,
    },
});

const emit = defineEmits(["delete-patient"]);

const page = usePage();

const queryParams = computed(() => {
    const params = new URLSearchParams(page.url?.split('?')[1] || '');
    return {
        search: params.get('search') || '',
        status: params.get('status') || '',
        registro: params.get('registro') || '',
    };
});

const search = ref(queryParams.value.search);
const statusFilter = ref(queryParams.value.status);
const registroFilter = ref(queryParams.value.registro);

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


const formatCpf = (cpf) => {
    if (!cpf) return '-';
    const cleaned = cpf.replace(/\D/g, '');
    if (cleaned.length !== 11) return cpf;
    return cleaned.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
};

const questions = computed(() => {
    const data = props.patients?.data;
    if (!data || data.length === 0) return [];
    const allQuestions = data.flatMap(p => p.answers?.map(a => a.question) || []);
    const unique = [...new Map(allQuestions.map(q => [q.id, q])).values()];
    return unique;
});

const getAnswer = (patient, questionId) => {
    const answer = patient.answers?.find(a => a.question_id === questionId);
    return answer?.answer || '-';
};

const termsQuestionId = computed(() => {
    const q = questions.value.find(q => {
        const title = (q.title || '').toLowerCase();
        return title.includes('aceito') || title.includes('termo') || title.includes('concordo');
    });
    return q?.id || null;
});

const sexoIcon = (sexo) => {
    if (!sexo) return null;
    return sexo.toLowerCase() === 'm' || sexo.toLowerCase() === 'masculino' ? 'M' : 'F';
};

const sexoColor = (sexo) => {
    if (!sexo) return 'bg-gray-100 text-gray-500';
    return sexo.toLowerCase() === 'm' || sexo.toLowerCase() === 'masculino'
        ? 'bg-blue-100 text-blue-700'
        : 'bg-pink-100 text-pink-700';
};

const registroLabel = (registro) => {
    const map = {
        'formulario': 'Formulario',
        'form-dinamico': 'Form. Dinamico',
        'importacao': 'Importacao',
        'form-publico': 'Form. Publico',
        'vinculo': 'Vinculo',
    };
    return map[registro] || registro || '-';
};

const registroColor = (registro) => {
    const map = {
        'formulario': 'bg-blue-50 text-blue-700 border border-blue-200',
        'form-dinamico': 'bg-purple-50 text-purple-700 border border-purple-200',
        'importacao': 'bg-orange-50 text-orange-700 border border-orange-200',
        'form-publico': 'bg-teal-50 text-teal-700 border border-teal-200',
        'vinculo': 'bg-cyan-50 text-cyan-700 border border-cyan-200',
    };
    return map[registro] || 'bg-gray-50 text-gray-600 border border-gray-200';
};

const registroIcon = (registro) => {
    const map = {
        'formulario': '📝',
        'form-dinamico': '⚡',
        'importacao': '📥',
        'form-publico': '🌐',
        'vinculo': '🔗',
    };
    return map[registro] || '•';
};

const navigate = () => {
    const params = {};
    if (search.value) params.search = search.value;
    if (statusFilter.value !== '' && statusFilter.value !== null) params.status = statusFilter.value;
    if (registroFilter.value) params.registro = registroFilter.value;

    router.visit(route('patients.index', params), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

let searchTimeout = null;
const onSearch = (value) => {
    search.value = value;
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => navigate(), 350);
};

const onFilterChange = () => {
    navigate();
};
</script>

<template>
    <div class="space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Pacientes Cadastrados</h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    {{ patients.total ?? patients.data?.length ?? 0 }} paciente(s) encontrado(s)
                </p>
            </div>
            <a :href="route('patients.report')" target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-cyan-600 text-white text-sm font-medium rounded-lg hover:bg-cyan-700 transition-colors shadow-sm">
                <Download class="w-4 h-4" />
                Relatório Geral
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50/50">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="relative flex-1 min-w-[220px]">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="search" @input="onSearch(search)" type="text"
                            placeholder="Buscar por nome, CPF ou e-mail..."
                            class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 rounded-lg bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all" />
                    </div>

                    <select v-model="statusFilter" @change="onFilterChange"
                        class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">
                        <option value="">Todos os status</option>
                        <option value="1">Ativo</option>
                        <option value="0">Inativo</option>
                    </select>

                    <select v-model="registroFilter" @change="onFilterChange"
                        class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm bg-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 outline-none transition-all">
                        <option value="">Todos os registros</option>
                        <option value="formulario">Formulario</option>
                        <option value="form-dinamico">Form. Dinamico</option>
                        <option value="importacao">Importacao</option>
                        <option value="form-publico">Form. Publico</option>
                        <option value="vinculo">Vinculo</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <Table>
                    <TableHeader>
                        <TableRow class="bg-gray-50/80">
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Código
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Nome
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                CPF
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Email
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Sexo
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Nascimento
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Status
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Registro
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Cadastro
                            </TableHead>
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Termos
                            </TableHead>
                            <!-- <TableHead v-for="question in questions" :key="question.id" class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                <div class="truncate max-w-[150px]" :title="question.title">
                                    {{ question.title }}
                                </div>
                            </TableHead> -->
                            <TableHead
                                class="text-center whitespace-nowrap text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Ações
                            </TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-for="patient in patients.data" :key="patient.id"
                            class="hover:bg-gray-50/50 transition-colors">
                            <TableCell class="text-center">
                                <span
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-gray-100 text-xs font-bold text-gray-600">
                                    {{ patient.id }}
                                </span>
                            </TableCell>

                            <TableCell class="text-center font-medium text-gray-900">
                                {{ patient.nome || '-' }}
                            </TableCell>

                            <TableCell class="text-center">
                                <span class="font-mono text-xs bg-gray-50 px-2 py-1 rounded">{{ formatCpf(patient.cpf)
                                }}</span>
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-600">
                                {{ patient.email || '-' }}
                            </TableCell>

                            <TableCell class="text-center">
                                <span v-if="patient.sexo"
                                    :class="['inline-flex items-center justify-center w-7 h-7 rounded-full text-xs font-bold', sexoColor(patient.sexo)]">
                                    {{ sexoIcon(patient.sexo) }}
                                </span>
                                <span v-else class="text-gray-300">-</span>
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-600">
                                {{ patient.data_nascimento_formatada || '-' }}
                            </TableCell>

                            <TableCell class="text-center">
                                <span v-if="patient.status"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                    Ativo
                                </span>
                                <span v-else
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    Inativo
                                </span>
                            </TableCell>

                            <TableCell class="text-center">
                                <span v-if="patient.status_registro"
                                    :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium', registroColor(patient.status_registro)]">
                                    <span class="text-[10px]">{{ registroIcon(patient.status_registro) }}</span>
                                    {{ registroLabel(patient.status_registro) }}
                                </span>
                                <span v-else class="text-gray-300 text-xs">-</span>
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-500">
                                {{ formatDate(patient.created_at) }}
                            </TableCell>

                            <TableCell class="text-center text-sm text-gray-600">
                                <span v-if="termsQuestionId && getAnswer(patient, termsQuestionId) !== '-'"
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200">
                                    ✓ Sim
                                </span>
                                <span v-else class="text-gray-300 text-xs">-</span>
                            </TableCell>

                            <!-- <TableCell v-for="question in questions" :key="question.id"
                                class="text-center text-sm text-gray-600">
                                <div class="truncate max-w-[200px]" :title="getAnswer(patient, question.id)">
                                    {{ getAnswer(patient, question.id) }}
                                </div>
                            </TableCell> -->

                            <TableCell class="text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button @click="router.visit(route('patients.show', patient.id))"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-all"
                                        title="Ver detalhes">
                                        <Eye class="w-4 h-4" />
                                    </button>
                                    <a :href="route('patients.pdf', patient.id)" target="_blank"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-green-600 hover:bg-green-50 transition-all"
                                        title="PDF">
                                        <FileText class="w-4 h-4" />
                                    </a>
                                    <button @click="router.visit(route('patients.edit', patient.id))"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-cyan-600 hover:bg-cyan-50 transition-all"
                                        title="Editar">
                                        <Pencil class="w-4 h-4" />
                                    </button>
                                    <button @click="emit('delete-patient', patient)"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition-all"
                                        title="Excluir">
                                        <Trash2 class="w-4 h-4" />
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow v-if="patients.data && patients.data.length === 0">
                            <TableCell :colspan="11 + questions.length" class="text-center py-12">
                                <div class="flex flex-col items-center gap-2">
                                    <div class="w-12 h-12 rounded-full bg-gray-100 flex items-center justify-center">
                                        <Search class="w-6 h-6 text-gray-400" />
                                    </div>
                                    <p class="text-sm font-medium text-gray-900">Nenhum paciente encontrado</p>
                                    <p class="text-xs text-gray-500">Tente ajustar os filtros de busca</p>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>

            <div class="border-t border-gray-100 bg-gray-50/50 px-4 py-3">
                <PaginationSimple :data="patients" :links="patients.links || []"
                    :has-data="(patients.data?.length ?? 0) > 0" label="pacientes" />
            </div>
        </div>
    </div>
</template>
