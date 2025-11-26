<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type UsersResponse, type User, type ErrorMessages } from '@/types';
import { getColumns } from '@/components/roles';
import { Head } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';
import DataTable from '@/components/DataTable.vue';
import DataTablePagination from '@/components/DataTablePagination.vue';
import { Button } from '@/components/ui/button';
import { Plus } from 'lucide-vue-next';
import DataTableDialog from "@/components/DataTableDialog.vue";
import axios from "axios";
import { useToast } from '@/components/ui/toast/use-toast';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import Users from './Users.vue';
import _ from 'lodash';
import Checkbox from '@/components/RolesCheckbox.vue';
import { ScrollArea } from '@/components/ui/scroll-area'

const { toast } = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Roles', href: '/roles' },
];

const operation = ref<"Create" | "Edit">("Create");
const isDialogOpen = ref(false);
const role = ref<Partial<User>>({});

const props = defineProps<{
    auth: any;
    roles: any,
}>();

const tableInstance = ref<any>(null);
const selectedPermissions = ref([])

const setTableInstance = (instance: any) => {
    tableInstance.value = instance;
};

const formSchema = toTypedSchema(z.object({
    name: z.string().min(2, "Name must be at least 2 characters").max(50),
}).refine((data) => !data.password || data.password === data.password_confirmation, {
    message: "Passwords must match",
    path: ["password_confirmation"],
}));

const form = useForm({
    validationSchema: formSchema,
    initialValues: role.value,
});

watch(role, (newUser) => {
    form.setValues(newUser || {});
});

const handleCreateClick = () => {
    operation.value = 'Create';
    isDialogOpen.value = true;
    role.value = {};
};

const handleEdit = (data: any) => {
    operation.value = "Edit";
    isDialogOpen.value = true;
    role.value = { ...data };
};

const handleDelete = async (data: any) => {
    try {
        const response = await axios.delete(`/roles/${data.id}`);
        if (response.status === 204) {
            roles.value.data = roles.value.data.filter(u => u.id !== data.id);
            toast({ description: "User deleted successfully" });
        }
    } catch (error: any) {
        toast({ description: "Error deleting role: " + error, variant: "destructive" });
    }
};

const onCreate = (data: any) => {
    roles.value.data.unshift(data);
    isDialogOpen.value = false;
    toast({ description: "Role created successfully" });
};

const onEdit = (data: any) => {
    const index = roles.value.data.findIndex(u => u.id === data.id);
    if (index !== -1) {
        roles.value.data[index] = data;
    }
    isDialogOpen.value = false;
    toast({ description: "Role updated successfully" });
};

const onDialogClose = () => {
    isDialogOpen.value = false;
    form.resetForm();
};

const onSubmit = form.handleSubmit(async (values) => {
    try {
        const method = operation.value === "Create" ? "post" : "put";
        const url = `/roles${operation.value === "Create" ? "" : "/" + role.value.id}`;
        let data = {};
        if (operation.value === "Create") {
            data = { name: values.name };
        } else {
            data = { name: values.name, permissions: props.roles.data.find(el => el.name == values.name).permissions };
        }
        const response = await axios({ method, url, data: data });

        if (operation.value === "Create") {
            onCreate(response.data.data);
        } else {
            onEdit(response.data.data);
        }
    } catch (error: any) {
        const errors = error.response?.data.errors;

        if (errors && 'validation' in errors) {
            const validationErrors = errors['validation'];
            const firstErrorKey = Object.keys(errors)[0];
            const firstErrorMessages: ErrorMessages = errors[firstErrorKey];

            if (validationErrors) {
                form.setErrors(firstErrorMessages);
            } else {
                Object.entries(firstErrorMessages).forEach(([field, messages]: [string, string[]]) => {
                    toast({
                        title: field,
                        description: messages[0],
                        variant: 'destructive',
                    });
                });
            }
        } else {
            toast({
                description: 'An unexpected error occurred.',
                variant: 'destructive',
            });
        }
    }
});

const onChecked = (role, permission, category) => {
    props.roles.data.find((el) => el.name == role).categorizedPermissions[category].find((el) => el.id === permission.id).selected = !permission.selected;
    if (permission.selected) {
        props.roles.data.find((el) => el.name == role).permissions.push(permission.id);
    } else {
        props.roles.data.find((el) => el.name == role).permissions.pop(permission.id);
    }
}
</script>

<template>

    <Head title="Roles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Roles</h1>
                <div class="flex items-center justify-end py-4 space-x-2">
                    <Button size="sm" @click="handleCreateClick">
                        <Plus class="w-5 h-5 mr-2" />
                        Add Role
                    </Button>
                </div>
            </div>
            <DataTable :columns="getColumns(handleEdit, handleDelete)" :data="roles.data" :meta="roles.meta"
                @table-instance="setTableInstance" />
            <DataTablePagination :table="tableInstance" :meta="roles.meta" />
            <DataTableDialog :isDialogOpen="isDialogOpen" :operation="operation" @onDialogClose="onDialogClose">
                <template #dialog-content="{ operation }">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">{{ operation }} Role</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ operation === 'Create' ? "Create a new role below." : "Edit role details here." }}
                            </p>
                        </div>
                        <form @submit.prevent="onSubmit" class="space-y-4">
                            <FormField :validateOnBlur="false" v-slot="{ componentField }" name="name">
                                <FormItem>
                                    <FormLabel>Name</FormLabel>
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="Enter name" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                                <FormItem v-if="operation != 'Create'">
                                    <div class="mb-4">
                                        <FormLabel class="text-base">Permissions</FormLabel>
                                    </div>
                                    <FormControl>
                                        <ScrollArea class="h-[400px] w-[370px] rounded-md border p-4">
                                            <div v-for="(permissions, category) in role.categorizedPermissions"
                                                :key="category">
                                                <div class="font-semibold text-lg">
                                                    {{ category.charAt(0).toUpperCase() + category.slice(1) }}
                                                </div>

                                                <FormItem v-for="permission in permissions" :key="permission.id"
                                                    class="flex flex-row items-start space-x-3 space-y-1 mb-1">
                                                    <FormControl>
                                                        <Checkbox :checked="permission.selected"
                                                            @change="onChecked(role.name, permission, category)"
                                                            class="mt-1" />
                                                    </FormControl>
                                                    <FormLabel class="font-normal">{{ permission.name }}</FormLabel>
                                                </FormItem>
                                            </div>
                                        </ScrollArea>
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                                <FormMessage />
                            </FormField>
                            <div class="flex justify-end">
                                <Button type="submit">Save</Button>
                            </div>
                        </form>
                    </div>
                </template>
            </DataTableDialog>

        </div>
    </AppLayout>
</template>