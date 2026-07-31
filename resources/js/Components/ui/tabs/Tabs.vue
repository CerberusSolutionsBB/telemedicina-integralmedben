<script setup>
import { TabsRoot, useForwardPropsEmits } from "reka-ui";

const props = defineProps({
  defaultValue: { type: [String, Number], required: false },
  modelValue: { type: [String, Number], required: false },
  orientation: { type: String, required: false },
  dir: { type: String, required: false },
  activationMode: { type: String, required: false },
  asChild: { type: Boolean, required: false },
  as: { type: null, required: false },
  unmountOnHide: { type: Boolean, required: false },
});
const emits = defineEmits(["update:modelValue"]);

const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
  <TabsRoot v-bind="forwarded">
    <slot />
  </TabsRoot>
</template>
