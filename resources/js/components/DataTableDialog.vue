<script setup lang="ts">
import { Dialog, DialogContent } from "@/components/ui/dialog";
import { computed } from "vue";

const props = defineProps<{
    isDialogOpen: boolean;
    operation: 'Create' | 'Edit';
}>();

const emit = defineEmits<{
    (e: 'onDialogClose'): void;
}>();

const isDialogOpen = computed({
    get: () => props.isDialogOpen,
    set: (value) => !value && emit('onDialogClose'),
});
</script>

<template>
    <Dialog v-model:open="isDialogOpen">
        <DialogContent class="sm:max-w-[425px]">
            <slot name="dialog-content" :operation="operation" />
        </DialogContent>
    </Dialog>
</template>