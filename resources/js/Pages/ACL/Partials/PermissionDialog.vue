<script setup>
import { computed, watch } from "vue";
import { useForm } from "@inertiajs/vue3";
import { Button } from "@/Components/ui/button";
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from "@/Components/ui/dialog";
import InputError from "@/Components/InputError.vue";
import { actionLabel, moduleLabel, permissionAction, permissionModule, submitFeedback, useAcl } from "./useAcl";

const props = defineProps({
    permission: { type: Object, default: null },
});

const open = defineModel("open", { type: Boolean, default: false });
const { aclRoute } = useAcl();

const form = useForm({ name: "" });
const isEdit = computed(() => Boolean(props.permission));

watch(open, (value) => {
    if (!value) return;
    form.clearErrors();
    form.name = props.permission?.name ?? "";
});

const preview = computed(() => {
    const name = form.name.trim().toLowerCase();
    if (!/^[a-z0-9_-]+(\.[a-z0-9_-]+)+$/.test(name)) return null;
    return { module: moduleLabel(permissionModule(name)), action: actionLabel(permissionAction(name)) };
});

const submit = () => {
    const options = submitFeedback(() => (open.value = false));
    if (isEdit.value) {
        form.put(aclRoute("permissions.update", props.permission.id), options);
    } else {
        form.post(aclRoute("permissions.store"), options);
    }
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit ? "Editar permissão" : "Nova permissão" }}</DialogTitle>
                <DialogDescription>
                    Use o formato <code class="rounded bg-gray-100 px-1">modulo.acao</code>, por exemplo
                    <code class="rounded bg-gray-100 px-1">pacientes.export</code>.
                </DialogDescription>
            </DialogHeader>

            <form id="permission-form" class="space-y-3" novalidate @submit.prevent="submit">
                <div>
                    <label for="permission-name" class="mb-1 block text-sm font-medium text-gray-700">Nome</label>
                    <input id="permission-name" v-model="form.name" type="text" required autocomplete="off"
                        placeholder="modulo.acao" :aria-invalid="Boolean(form.errors.name)" aria-describedby="permission-name-error"
                        @input="form.errors.name && form.clearErrors('name')"
                        :class="form.errors.name ? 'border-red-400 bg-red-50/40' : 'border-gray-300'"
                        class="w-full rounded-lg border px-3 py-2 font-mono text-sm focus:border-cyan-500 focus:outline-none focus:ring-2 focus:ring-cyan-500/30" />
                    <InputError id="permission-name-error" :message="form.errors.name" class="mt-1" />
                </div>
                <p v-if="preview" class="text-xs text-gray-500">
                    Será exibida como <strong class="text-gray-700">{{ preview.action }}</strong>
                    em <strong class="text-gray-700">{{ preview.module }}</strong>.
                </p>
                <p v-if="isEdit" class="rounded-md bg-amber-50 p-2 text-xs text-amber-800">
                    Renomear uma permissão usada pelo sistema pode bloquear o acesso às telas que dependem dela.
                </p>
            </form>

            <DialogFooter class="gap-2">
                <Button type="button" variant="outline" @click="open = false">Cancelar</Button>
                <Button type="submit" form="permission-form" :disabled="form.processing">
                    {{ form.processing ? "Salvando..." : isEdit ? "Salvar" : "Criar permissão" }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
