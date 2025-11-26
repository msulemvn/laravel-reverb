    <script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem, type TagsResponse, type Tag, type ErrorMessages, type PaginationMeta } from '@/types';
    import { getColumns } from '@/components/tags';
    import { Head } from '@inertiajs/vue3';
    import { ref, watch } from 'vue';
    import DataTable from '@/components/DataTable.vue';
    import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar'
    import { Table } from '@tanstack/vue-table';
    import DataTablePagination from '@/components/DataTablePagination.vue';
    import { Button } from '@/components/ui/button';
    import { Plus } from 'lucide-vue-next';
    import DataTableDialog from '@/components/DataTableDialog.vue';
    import axios from 'axios';
    import { useToast } from '@/components/ui/toast/use-toast';
    import { useForm } from 'vee-validate';
    import { toTypedSchema } from '@vee-validate/zod';
    import * as z from 'zod';
    import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
    import { Input } from '@/components/ui/input';

    const { toast } = useToast();

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tags', href: '/tags' },
    ];

    const props = defineProps<{
        tags: TagsResponse;
    }>();

    const operation = ref<'Create' | 'Edit'>('Create');
    const isDialogOpen = ref(false);
    const tags = ref<TagsResponse>(props.tags);
    const tag = ref<Partial<Tag>>({});
    const tableInstance = ref<Table<Tag> | null>(null);
    const meta: PaginationMeta = props.tags.meta;


    const setTableInstance = (instance: Table<Tag>) => {
        tableInstance.value = instance;
    };

    const formSchema = toTypedSchema(z.object({
        title: z.string().min(1, 'Tag title is required').max(255, 'Tag title must be 255 characters or less'),
    }));

    const form = useForm({
        validationSchema: formSchema,
        initialValues: tag.value,
    });

    watch(tag, (newTag) => {
        form.setValues(newTag || {});
    });

    const handleCreateClick = () => {
        operation.value = 'Create';
        isDialogOpen.value = true;
        tag.value = { title: '' };
    };

    const handleEdit = (data: Tag) => {
        operation.value = 'Edit';
        isDialogOpen.value = true;
        tag.value = { ...data };
    };

    const handleDelete = async (data: Tag) => {
        try {
            await axios.delete(`/tags/${data.id}`);
            tags.value.data = tags.value.data.filter((t) => t.id !== data.id);
            toast({ description: 'Tag deleted successfully' });
        } catch {
            toast({ description: 'Error deleting tag', variant: 'destructive' });
        }
    };

    const onCreate = (data: Tag) => {
        tags.value.data.unshift(data);
        toast({ description: 'Tag created successfully' });
        isDialogOpen.value = false;
        form.resetForm();
    };

    const onEdit = (data: Tag) => {
        const index = tags.value.data.findIndex((t) => t.id === data.id);
        if (index !== -1) {
            tags.value.data[index] = data;
            toast({ description: 'Tag updated successfully' });
        }
        isDialogOpen.value = false;
        form.resetForm();
    };

    const onDialogClose = () => {
        isDialogOpen.value = false;
        form.resetForm();
    };

    const onSubmit = form.handleSubmit(async (values) => {
        try {
            const method = operation.value === "Create" ? "post" : "put";
            const url = `/tags${operation.value === "Create" ? "" : "/" + tag.value.id}`;

            const response = await axios({
                method,
                data: values,
                url,
                responseType: "json",
            });

            if (response.status === 201) {
                onCreate(response.data.data);
            } else {
                onEdit(response.data.data);
            }

            isDialogOpen.value = false;
            form.resetForm();

            toast({
                description: response.data.message || `${operation.value}d successfully`,
            });
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

        <Head title="Tags" />
        <AppLayout :breadcrumbs="breadcrumbs">
            <div class="container mx-auto py-6">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold">Tags</h1>
                    <div class="flex items-center justify-end py-4 space-x-2">
                        <Button size="sm" @click="handleCreateClick" v-if="can('create:tags')">
                            <Plus class="w-5 h-5 mr-2" />
                            New Tag
                        </Button>
                    </div>
                </div>
                <DataTable :columns="getColumns(handleEdit, handleDelete)" :data="tags.data" :meta="meta"
                    @table-instance="setTableInstance" />
                <DataTablePagination :table="tableInstance" :meta="meta" />
                <DataTableDialog :isDialogOpen="isDialogOpen" :operation="operation" @onDialogClose="onDialogClose">
                    <template #dialog-content="{ operation }">
                        <div class="space-y-6">
                            <div>
                                <h3 class="text-lg font-medium">{{ operation }} Tag</h3>
                                <p class="text-sm text-muted-foreground">
                                    {{ operation === 'Create' ? 'Add a new tag below.' : 'Edit tag details here.' }}
                                </p>
                            </div>
                            <form @submit.prevent="onSubmit" class="space-y-4">
                                <FormField :validateOnBlur="false" v-slot="{ componentField }" name="title">
                                    <FormItem>
                                        <FormLabel>Title</FormLabel>
                                        <FormControl>
                                            <Input v-bind="componentField" placeholder="Enter tag title" />
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
