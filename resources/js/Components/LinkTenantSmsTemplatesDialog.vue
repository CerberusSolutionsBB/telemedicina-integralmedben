<script setup>
import { ref, watch, computed } from "vue";
import { useForm, router } from "@inertiajs/vue3";
import { CheckCircle2 } from "lucide-vue-next";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "@/Components/ui/dialog";
import { Button } from "@/Components/ui/button";

const props = defineProps({
  open: { type: Boolean, required: true },
  template: { type: Object, default: null }, // o template que está sendo vinculado
  tenants: { type: Array, default: () => [] }, // todos os tenants disponíveis
});

const emit = defineEmits(["update:open"]);

// IDs dos tenants selecionados
const selectedTenants = ref([]);

const form = useForm({ tenants: [] });

const selectedCount = computed(() => selectedTenants.value.length);

// Quando o template mudar, pré-seleciona os tenants já vinculados
watch(
  () => props.template,
  (template) => {
    if (template?.tenants) {
      selectedTenants.value = template.tenants.map((t) => t.id);
    } else {
      selectedTenants.value = [];
    }
  },
  { immediate: true }
);

const allSelected = computed(() => props.tenants.length > 0 && selectedTenants.value.length === props.tenants.length);

const toggleAll = () => {
  if (allSelected.value) {
    selectedTenants.value = [];
  } else {
    selectedTenants.value = props.tenants.map((t) => t.id);
  }
};

const toggleTenant = (id) => {
  const index = selectedTenants.value.indexOf(id);
  if (index === -1) {
    selectedTenants.value.push(id);
  } else {
    selectedTenants.value.splice(index, 1);
  }
};

const close = () => emit("update:open", false);

const submit = () => {
  if (!props.template) return;

  form.tenants = selectedTenants.value;
  form.post(route("sms-templates.assign-tenants", props.template.id), {
    onSuccess: () => {
      close();
      router.reload({ only: ["templates"] });
    },
  });
};
</script>

<template>
  <Dialog :open="open" @update:open="close">
    <DialogContent class="sm:max-w-[600px]">
      <form @submit.prevent="submit">
        <DialogHeader>
          <DialogTitle class="text-xl font-bold">Vincular Tenants ao Template</DialogTitle>
          <p class="text-sm text-gray-500 mt-1">
            Selecione quais tenants receberão o template
            <strong v-if="template">"{{ template.name }}"</strong>

            <span
              v-if="selectedCount > 0"
              class="inline-flex ml-2 px-2 py-0.5 bg-cyan-100 text-cyan-800 rounded-full text-xs font-semibold"
            >
              {{ selectedCount }} {{ selectedCount === 1 ? "selecionado" : "selecionados" }}
            </span>
          </p>
        </DialogHeader>

        <div class="flex justify-end mb-1">
          <button
            type="button"
            @click="toggleAll"
            class="text-xs font-medium px-3 py-1 rounded-full transition-colors"
            :class="allSelected
              ? 'bg-red-100 text-red-700 hover:bg-red-200'
              : 'bg-cyan-100 text-cyan-700 hover:bg-cyan-200'"
          >
            {{ allSelected ? "Desmarcar todos" : "Selecionar todos" }}
          </button>
        </div>

        <div class="py-4 max-h-[400px] overflow-y-auto space-y-2">
          <div
            v-for="tenant in tenants"
            :key="tenant.id"
            @click="toggleTenant(tenant.id)"
            class="group flex items-center gap-4 border-2 p-4 rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md"
            :class="{
              'border-cyan-500 bg-cyan-50 shadow-sm': selectedTenants.includes(tenant.id),
              'border-gray-200 hover:border-gray-300 bg-white': !selectedTenants.includes(tenant.id),
            }"
          >
            <input
              type="checkbox"
              :checked="selectedTenants.includes(tenant.id)"
              class="w-4 h-4 text-cyan-600 border-gray-300 rounded focus:ring-cyan-500 pointer-events-none"
            />

            <span class="flex-1 text-sm font-medium text-gray-900">
              {{ tenant.id }}
            </span>

            <CheckCircle2
              v-if="selectedTenants.includes(tenant.id)"
              class="w-5 h-5 text-cyan-600 flex-shrink-0"
            />
          </div>

          <div v-if="tenants.length === 0" class="text-center py-8 text-gray-500 text-sm">
            Nenhum tenant disponível.
          </div>
        </div>

        <DialogFooter class="gap-2">
          <Button type="button" variant="outline" @click="close">
            Cancelar
          </Button>
          <Button type="submit" :disabled="form.processing">
            Salvar vínculo
          </Button>
        </DialogFooter>
      </form>
    </DialogContent>
  </Dialog>
</template>
