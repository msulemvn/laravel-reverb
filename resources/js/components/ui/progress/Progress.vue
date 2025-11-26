<script setup lang="ts">
import { computed } from "vue";
import { cn } from "@/lib/utils";

const props = defineProps<{
  modelValue?: number;
  class?: string;
}>();

const progressValue = computed(() => props.modelValue ?? 0);
</script>

<template>
  <div
    :class="
      cn(
        'relative h-2 w-full overflow-hidden rounded-full bg-secondary',
        props.class
      )
    "
  >
    <div
      class="h-full bg-primary transition-all duration-300"
      :style="{ width: `${progressValue}%` }"
    />
  </div>
</template>
