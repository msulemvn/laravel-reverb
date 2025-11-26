<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type UsersResponse, type User, type ErrorMessages } from '@/types';
import { getColumns } from '@/components/users';
import { Head } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
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

const { toast } = useToast();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
];

const operation = ref<"Create" | "Edit">("Create");
const isDialogOpen = ref(false);
const user = ref<Partial<User>>({});

const props = defineProps<{
    users: UsersResponse;
}>();

const users = ref<UsersResponse>(props.users);
const tableInstance = ref<any>(null);

const setTableInstance = (instance: any) => {
    tableInstance.value = instance;
};

const formSchema = toTypedSchema(z.object({
    name: z.string().min(2, "Name must be at least 2 characters").max(50),
    email: z.string().email("Invalid email address"),
    password: z.string().min(6, "Password must be at least 6 characters").optional().or(z.literal('')),
    password_confirmation: z.string().optional().or(z.literal('')),
}).refine((data) => !data.password || data.password === data.password_confirmation, {
    message: "Passwords must match",
    path: ["password_confirmation"],
}));

const form = useForm({
    validationSchema: formSchema,
    initialValues: user.value,
});

watch(user, (newUser) => {
    form.setValues(newUser || {});
});

const handleCreateClick = () => {
    operation.value = 'Create';
    isDialogOpen.value = true;
    user.value = {};
};

const handleEdit = (data: User) => {
    operation.value = "Edit";
    isDialogOpen.value = true;
    user.value = { ...data };
};

const handleDelete = async (data: User) => {
    try {
        const response = await axios.delete(`/users/${data.id}`);
        if (response.status === 204) {
            users.value.data = users.value.data.filter(u => u.id !== data.id);
            toast({ description: "User deleted successfully" });
        }
    } catch (error: any) {
        toast({ description: "Error deleting user: " + error, variant: "destructive" });
    }
};

const onCreate = (data: User) => {
    users.value.data.unshift(data);
    isDialogOpen.value = false;
    toast({ description: "User created successfully" });
};

const onEdit = (data: User) => {
    const index = users.value.data.findIndex(u => u.id === data.id);
    if (index !== -1) {
        users.value.data[index] = data;
    }
    isDialogOpen.value = false;
    toast({ description: "User updated successfully" });
};

const onDialogClose = () => {
    isDialogOpen.value = false;
    form.resetForm();
};

const onSubmit = form.handleSubmit(async (values) => {
    try {
        const method = operation.value === "Create" ? "post" : "put";
        const url = `/users${operation.value === "Create" ? "" : "/" + user.value.id}`;
        const response = await axios({ method, url, data: values });

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
</script>

<template>

    <Head title="Users" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="container mx-auto py-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Users</h1>
                <div class="flex items-center justify-end py-4 space-x-2">
                    <Button size="sm" @click="handleCreateClick">
                        <Plus class="w-5 h-5 mr-2" />
                        New User
                    </Button>
                </div>
            </div>
            <DataTable :columns="getColumns(handleEdit, handleDelete)" :data="users.data" :meta="users.meta"
                @table-instance="setTableInstance" />
            <DataTablePagination :table="tableInstance" :meta="users.meta" />
            <DataTableDialog :isDialogOpen="isDialogOpen" :operation="operation" @onDialogClose="onDialogClose">
                <template #dialog-content="{ operation }">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium">{{ operation }} User</h3>
                            <p class="text-sm text-muted-foreground">
                                {{ operation === 'Create' ? "Add a new user below." : "Edit user details here." }}
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
                            </FormField>
                            <FormField :validateOnBlur="false" v-slot="{ componentField }" name="email">
                                <FormItem>
                                    <FormLabel>Email</FormLabel>
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="Enter email" type="email" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField :validateOnBlur="false" v-slot="{ componentField }" name="password">
                                <FormItem>
                                    <FormLabel>Password</FormLabel>
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="Enter password" type="password" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
                            </FormField>
                            <FormField :validateOnBlur="false" v-slot="{ componentField }" name="password_confirmation">
                                <FormItem>
                                    <FormLabel>Confirm Password</FormLabel>
                                    <FormControl>
                                        <Input v-bind="componentField" placeholder="Confirm password" type="password" />
                                    </FormControl>
                                    <FormMessage />
                                </FormItem>
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