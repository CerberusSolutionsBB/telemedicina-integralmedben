import { computed, nextTick } from "vue";
import { usePage } from "@inertiajs/vue3";
import { showToast } from "@/Utils/toast";

// Rótulos amigáveis para os módulos e ações das permissões (modulo.acao)
const MODULE_LABELS = {
    acl: "Controle de Acesso",
    "acl.users": "Usuários",
    "acl.roles": "Perfis",
    "acl.permissions": "Permissões",
    users: "Usuários",
    forms: "Formulários",
    paginas: "Páginas de Parceiros",
    leis: "Leis",
    siprov: "Telemedicina (SIPROV)",
    pacientes: "Beneficiários",
};

const ACTION_LABELS = {
    view: "Visualizar",
    show: "Detalhar",
    create: "Criar",
    edit: "Editar",
    update: "Atualizar",
    delete: "Excluir",
    manage: "Gerenciar",
    "manage.all": "Gerenciar todos",
    retry: "Reprocessar",
    "update.status": "Alterar status",
    "toggle.visibility": "Alterar visibilidade",
};

// acl.users.view → módulo "acl.users"; forms.update.status → módulo "forms"
export const permissionModule = (name) => {
    const parts = name.split(".");
    return parts[0] === "acl" && parts.length > 2 ? parts.slice(0, 2).join(".") : parts[0];
};

export const permissionAction = (name) => name.slice(permissionModule(name).length + 1);

export const moduleLabel = (module) =>
    MODULE_LABELS[module] || module.charAt(0).toUpperCase() + module.slice(1).replace(/[-_]/g, " ");

export const actionLabel = (action) => ACTION_LABELS[action] || action.replace(/[._-]/g, " ");

export const groupPermissions = (names) => {
    const groups = {};
    names.forEach((name) => {
        const module = permissionModule(name);
        (groups[module] ||= []).push(name);
    });
    return Object.keys(groups)
        .sort((a, b) => moduleLabel(a).localeCompare(moduleLabel(b)))
        .map((module) => ({ module, label: moduleLabel(module), permissions: groups[module] }));
};

export function useAcl() {
    const page = usePage();
    const acl = computed(() => page.props.acl || { context: "central", routePrefix: "acl.", can: {} });

    const aclRoute = (name, params) => route(`${acl.value.routePrefix}${name}`, params);
    const can = (ability) => Boolean(acl.value.can?.[ability]);

    return { acl, aclRoute, can };
}

// Primeiro erro de um campo, incluindo itens de lista (ex.: "roles.0", "permissions.3")
export const fieldError = (errors, field) =>
    errors?.[field] || Object.entries(errors || {}).find(([key]) => key.startsWith(`${field}.`))?.[1];

// Callbacks padrão para envio de formulários do módulo.
// Sucesso/erro de negócio chegam via flash (toast no AclLayout); aqui tratamos a validação.
export const submitFeedback = (onSuccess) => ({
    preserveScroll: true,
    onSuccess,
    onError: async (errors) => {
        const total = Object.keys(errors).length;
        showToast(total > 1 ? `Não foi possível salvar: corrija os ${total} campos destacados.` : "Não foi possível salvar: corrija o campo destacado.", "error");
        await nextTick();
        document.querySelector("[data-form-errors]")?.scrollIntoView({ behavior: "smooth", block: "center" });
    },
});

// Exclusões: o resultado vem via flash; falhas de rede/validação também avisam o usuário
export const deleteFeedback = () => ({
    preserveScroll: true,
    onError: (errors) => showToast(Object.values(errors)[0] || "Não foi possível excluir. Tente novamente.", "error"),
});
