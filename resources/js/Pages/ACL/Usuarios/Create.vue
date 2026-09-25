<script setup>
import AclLayout from "../Partials/AclLayout.vue";
import UserForm from "../Partials/UserForm.vue";

defineProps({
    roles: { type: Array, required: true },
    permissions: { type: Array, required: true },
});
</script>

<template>
    <AclLayout title="Novo usuário"
        :breadcrumbs="[{ label: 'Usuários', route: 'users.index' }, { label: 'Novo usuário' }]" description="Cadastre o acesso e defina os perfis do usuário.">
        <UserForm :roles="roles" :permissions="permissions" />
    </AclLayout>
</template>
