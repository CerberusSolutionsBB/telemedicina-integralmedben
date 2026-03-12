<script setup>
import { Link, useForm, usePage } from "@inertiajs/vue3";
import { LayoutDashboard, Building2, ClipboardList, Users, MessageSquare, ScrollText, BarChart3, LogOut, ChevronRight } from "lucide-vue-next";
import { computed, ref } from "vue";

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const logoutForm = useForm({});
const open = ref(false);

const logout = () => {
  logoutForm.post(route("logout"));
};

const navLinks = [
  { label: "Dashboard", routeName: "dashboard", icon: LayoutDashboard },
  { label: "Credenciados", routeName: "credenciados.index", icon: Building2 },
  { label: "Registros", routeName: "registros.index", icon: ClipboardList },
  { label: "Usuários", routeName: "central-users.index", icon: Users },
  { label: "SMS Templates", routeName: "sms-templates.index", icon: MessageSquare },
  { label: "Logs de SMS",   routeName: "admin.sms-logs.index", icon: ScrollText },
  { label: "Relatórios",   routeName: "admin.reports.index",  icon: BarChart3 },
];
</script>

<template>
  <div class="min-h-screen bg-gray-50">

    <!-- Backdrop -->
    <transition name="fade">
      <div
        v-if="open"
        class="fixed inset-0 z-30 bg-black/30"
        @click="open = false"
      />
    </transition>

    <!-- Sidebar -->
    <aside
      :class="[
        'fixed inset-y-0 left-0 z-40 flex flex-col bg-white border-r border-gray-200 transition-all duration-300 ease-in-out',
        open ? 'w-64 shadow-xl' : 'w-16',
      ]"
    >
      <!-- Toggle -->
      <button
        @click="open = !open"
        class="absolute -right-3 top-6 z-10 w-6 h-6 bg-white border border-gray-200 rounded-full flex items-center justify-center shadow-sm hover:bg-gray-50 transition-colors"
      >
        <ChevronRight
          :class="['w-3.5 h-3.5 text-gray-500 transition-transform duration-300', open ? 'rotate-180' : '']"
        />
      </button>

      <!-- Header -->
      <div class="p-4 border-b border-gray-200 overflow-hidden">
        <template v-if="open">
          <span class="text-lg font-bold text-gray-900 tracking-tight whitespace-nowrap">
            IntegralMedBen
          </span>
          <p class="text-xs text-gray-400 mt-0.5">Painel Administrativo</p>
        </template>
        <template v-else>
          <div class="w-8 h-8 rounded-lg bg-cyan-600 flex items-center justify-center mx-auto">
            <span class="text-white text-sm font-bold">I</span>
          </div>
        </template>
      </div>

      <!-- Nav -->
      <nav class="flex-1 p-2 space-y-1 overflow-hidden">
        <Link
          v-for="link in navLinks"
          :key="link.routeName"
          :href="route(link.routeName)"
          :title="!open ? link.label : undefined"
          @click="open = false"
          :class="[
            'flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors',
            !open ? 'justify-center' : '',
            route().current(link.routeName)
              ? 'bg-cyan-50 text-cyan-700'
              : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900',
          ]"
        >
          <component :is="link.icon" class="w-5 h-5 shrink-0" />
          <span v-if="open" class="whitespace-nowrap">{{ link.label }}</span>
        </Link>
      </nav>

      <!-- Footer -->
      <div class="p-2 border-t border-gray-200 space-y-2 overflow-hidden">
        <div v-if="authUser && open" class="px-3 py-1">
          <p class="text-sm font-medium text-gray-800 truncate">{{ authUser.name }}</p>
          <p class="text-xs text-gray-400 truncate">{{ authUser.email }}</p>
        </div>

        <button
          @click="logout"
          :disabled="logoutForm.processing"
          :title="!open ? 'Sair' : undefined"
          :class="[
            'flex items-center gap-3 w-full px-3 py-2.5 rounded-lg text-sm font-medium text-red-600 hover:bg-red-50 transition-colors',
            !open ? 'justify-center' : '',
          ]"
        >
          <LogOut class="w-5 h-5 shrink-0" />
          <span v-if="open">Sair</span>
        </button>
      </div>
    </aside>

    <!-- Main content — sempre com ml-16 para o strip de ícones -->
    <main class="ml-16 px-4 py-6 sm:px-6 sm:py-8 md:px-8 md:py-10 min-h-screen overflow-auto">
      <slot />
    </main>
  </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.25s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
