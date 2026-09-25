<script setup>
import AclLayout from "../Partials/AclLayout.vue";
import RoleForm from "../Partials/RoleForm.vue";

defineProps({
    role: { type: Object, required: true },
    permissions: { type: Array, required: true },
});
</script>

<template>
    <AclLayout :breadcrumbs="[{ label: 'Perfis', route: 'roles.index' }, { label: role.name }]"
        :title="role.protected ? `Perfil ${role.name}` : `Editar perfil ${role.name}`">
        <RoleForm :role="role" :permissions="permissions" />
    </AclLayout>
</template>
