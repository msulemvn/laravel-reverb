<script setup lang="ts" generic="TData, TValue">
import { onMounted, ref, watch } from 'vue';
import type { ColumnDef, Table } from '@tanstack/vue-table';
import { type PaginationMeta } from '@/types';
import { ScrollArea } from '@/components/ui/scroll-area';

import {
    Table as TableComponent,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import {
    FlexRender,
    getCoreRowModel,
    getPaginationRowModel,
    useVueTable,
} from '@tanstack/vue-table';

const props = defineProps<{
    columns: ColumnDef<TData, TValue>[];
    data: TData[];
    meta: PaginationMeta;
}>();

const emit = defineEmits(['table-instance']);

const table = ref<Table<TData> | undefined>();

const createTable = (data: TData[]) => {
    table.value = useVueTable({
        data,
        columns: props.columns,
        getCoreRowModel: getCoreRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        manualPagination: true,
    });

    emit('table-instance', table.value);
};

onMounted(() => {
    createTable(props.data);
});

watch(() => props.data, (newData) => {
    createTable(newData);
}, { deep: true });

</script>

<template>
    <div class="border rounded-md">
        <ScrollArea class="h-[70vh] w-full">
            <TableComponent v-if="table">
                <TableHeader>
                    <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                        <TableHead v-for="header in headerGroup.headers" :key="header.id">
                            <FlexRender v-if="!header.isPlaceholder" :render="header.column.columnDef.header"
                                :props="header.getContext()" />
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <template v-if="table.getRowModel().rows?.length">
                        <TableRow v-for="row in table.getRowModel().rows" :key="row.id"
                            :data-state="row.getIsSelected() ? 'selected' : undefined">
                            <TableCell v-for="cell in row.getVisibleCells()" :key="cell.id">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </TableCell>
                        </TableRow>
                    </template>
                    <template v-else>
                        <TableRow>
                            <TableCell :colspan="props.columns.length" class="h-24 text-center">
                                No results.
                            </TableCell>
                        </TableRow>
                    </template>
                </TableBody>
            </TableComponent>
        </ScrollArea>
    </div>
</template>