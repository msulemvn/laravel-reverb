<script setup lang="ts">
import { Button } from '@/components/ui/button'
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { MoreHorizontal } from 'lucide-vue-next'

const emit = defineEmits(['edit', 'delete']);

const handleEdit = () => {
    emit('edit');
};

const handleDelete = () => {
    emit('delete');
};
</script>

<template>
    <!-- <DropdownMenu v-if="can('update:posts | delete:posts')"> -->
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button variant="ghost" class="w-8 h-8 p-0">
                <span class="sr-only">Open menu</span>
                <MoreHorizontal class="w-4 h-4" />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuLabel>Actions</DropdownMenuLabel>
            <DropdownMenuItem @click="handleEdit">
                Edit
            </DropdownMenuItem>
            <DropdownMenuItem @click="handleDelete">
                Delete
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>