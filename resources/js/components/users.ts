import { h } from 'vue'
import { type User } from '@/types'
import type { ColumnDef } from '@tanstack/vue-table'
import DropdownAction from '@/components/DataTableDropDown.vue'

export const getColumns = (
    handleEdit: (user: User) => void,
    handleDelete: (user: User) => void
): ColumnDef<User>[] => [{
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => h('div', row.getValue('id')),
},
{
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => h('div', row.getValue('name')),
},
{
    accessorKey: 'email',
    header: 'Email',
    cell: ({ row }) => h('div', row.getValue('email')),
},
{
    id: 'actions',
    enableHiding: false,
    cell: ({ row }) => {
        const user = row.original;
        return h('div', { class: 'relative' }, h(DropdownAction, {
            onEdit: () => handleEdit(user),
            onDelete: () => handleDelete(user),
        }));
    },
},
    ];
