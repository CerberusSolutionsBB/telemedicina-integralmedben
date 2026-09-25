<script setup>
import AclLayout from "../Partials/AclLayout.vue";
import UserForm from "../Partials/UserForm.vue";

defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, required: true },
    permissions: { type: Array, required: true },
    isCurrentUser: { type: Boolean, default: false },
});
</script>

<template>
    <AclLayout :title="`Editar ${user.name}`"
        :breadcrumbs="[{ label: 'Usuários', route: 'users.index' }, { label: user.name }]" :description="user.email">
        <UserForm :user="user" :roles="roles" :permissions="permissions" :is-current-user="isCurrentUser" />
    </AclLayout>
</template>
