<script setup>
import AclLayout from "../Partials/AclLayout.vue";
import RoleForm from "../Partials/RoleForm.vue";

defineProps({
    permissions: { type: Array, required: true },
});
</script>

<template>
    <AclLayout title="Novo perfil"
        :breadcrumbs="[{ label: 'Perfis', route: 'roles.index' }, { label: 'Novo perfil' }]" description="Agrupe permissões para atribuir aos usuários.">
        <RoleForm :permissions="permissions" />
    </AclLayout>
</template>
