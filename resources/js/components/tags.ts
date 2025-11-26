import { h } from 'vue';
import type { ColumnDef } from '@tanstack/vue-table';
import DropdownAction from '@/components/DataTableDropDown.vue';
import type { Tag } from '@/types';

export const getColumns = (
    handleEdit: (tag: Tag) => void,
    handleDelete: (tag: Tag) => void
): ColumnDef<Tag, unknown>[] => [
        {
            accessorFn: (row: Tag) => row.id,
            id: 'id',
            header: 'ID',
            cell: ({ row }) => h('div', row.getValue('id')),
        },
        {
            accessorFn: (row: Tag) => row.title,
            id: 'title',
            header: 'Title',
            cell: ({ row }) => h('div', row.getValue('title')),
        },
        {
            id: 'actions',
            enableHiding: false,
            cell: ({ row }) => {
                const tag = row.original as Tag;
                return h('div', { class: 'relative' }, h(DropdownAction, {
                    onEdit: () => handleEdit(tag),
                    onDelete: () => handleDelete(tag),
                }));
            },
        },
    ];