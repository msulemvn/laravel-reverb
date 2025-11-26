    <script setup lang="ts">
    import AppLayout from '@/layouts/AppLayout.vue';
    import { type BreadcrumbItem, type Tag } from '@/types';
    import { Head, Link } from '@inertiajs/vue3';
    import { ref, computed, watch } from 'vue';
    import { Button } from '@/components/ui/button';
    import { Card, CardFooter, CardHeader } from '@/components/ui/card';
    import DataTableDialog from '@/components/DataTableDialog.vue';
    import axios from 'axios';
    import { useToast } from '@/components/ui/toast/use-toast';
    import { useForm } from 'vee-validate';
    import { toTypedSchema } from '@vee-validate/zod';
    import * as z from 'zod';
    import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
    import { Input } from '@/components/ui/input';
    import { Textarea } from '@/components/ui/textarea';
    import { Plus, X, ChevronDown } from 'lucide-vue-next';
    import { Checkbox } from '@/components/ui/checkbox';
    import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
    import { route } from 'ziggy-js';
    import { Switch } from '@/components/ui/switch';
    import Header from '@/components/Header.vue';
    import CardTitle from '@/components/ui/card/CardTitle.vue';

    const { toast } = useToast();

    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Posts',
            href: '/posts',
        },
    ];
    const props = defineProps<{ posts: any, tags: any, auth: any }>();

    const tagOptions = computed(() => {
        if (props.tags && Array.isArray(props.tags.data)) {
            return props.tags.data as Tag[];
        }
        else if (Array.isArray(props.tags)) {
            return props.tags as Tag[];
        }
        return [] as Tag[];
    });

    const operation = ref<'Create' | 'Edit'>('Create');
    const isDialogOpen = ref(false);
    const posts = ref(props.posts);
    const post = ref<Partial<any>>({});
    const selectedTags = ref<Tag[]>([]);
    const tagSearchQuery = ref('');

    const filteredTags = computed(() => {
        if (!tagSearchQuery.value) return tagOptions.value;
        return tagOptions.value.filter(tag =>
            tag.title.toLowerCase().includes(tagSearchQuery.value.toLowerCase())
        );
    });

    const formSchema = toTypedSchema(z.object({
        title: z.string().min(1, 'Title is required').max(255, 'Title must be 255 characters or less'),
        description: z.string().min(1, 'Description is required').max(255, 'Description must be 255 characters or less'),
        content: z.string().min(1, 'Content is required'),
        feature_image: z.union([
            z.instanceof(File).refine((file) => file.size <= 5 * 1024 * 1024, { message: 'Image must be less than 5MB' })
                .refine((file) => ['image/jpeg', 'image/png'].includes(file.type), { message: 'Only JPEG and PNG images are allowed' }),
            z.string(),
            z.null(),
            z.undefined(),
        ]).optional(),
    }));

    const defaultPostValues = computed(() => ({
        title: '',
        description: '',
        content: '',
        feature_image: undefined,
    }));

    const form = useForm({
        validationSchema: formSchema,
        initialValues: post.value,
    });

    watch(
        () => post.value,
        (newPost) => {
            if (newPost) {
                form.setValues(newPost);
                selectedTags.value = Array.isArray(newPost.tags) ? [...newPost.tags] : [];
            }
        },
        { deep: false }
    );

    const handleCreateClick = () => {
        operation.value = 'Create';
        isDialogOpen.value = true;
        post.value = { ...defaultPostValues.value };
        selectedTags.value = [];
        tagSearchQuery.value = '';
    };

    const handleEdit = (data: any) => {
        operation.value = 'Edit';
        isDialogOpen.value = true;
        post.value = { ...data };
        selectedTags.value = Array.isArray(data.tags) ? [...data.tags] : [];
        tagSearchQuery.value = '';
    };

    const handleDelete = async (data: any) => {
        try {
            const index = posts.value.data.findIndex((p: any) => p.id === data.id);
            if (index !== -1) {
                posts.value.data.splice(index, 1);
            }

            const response = await axios.delete(`/posts/${data.slug}`);

            if (response.status === 200) {
                const index = posts.value.data.findIndex((p: any) => p.id === data.id);
                if (index !== -1) {
                    posts.value.data.splice(index, 1);
                }

                toast({ description: 'Post deleted successfully' });
            }
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || 'An error occurred while deleting the post';
            toast({ description: errorMessage, variant: 'destructive' });
        }
    };

    const processPostRequest = async (method: string, url: string, values: any) => {
        try {
            const headers = { 'Content-Type': 'multipart/form-data' };

            if (method === 'put') {
                values.append('_method', 'PUT');
            }

            const response = await axios.post(url, values, { headers });

            if (response.status === 201) {
                const newPost = {
                    ...response.data.data,
                    tags: selectedTags.value,
                };
                posts.value.data.unshift(newPost);
                toast({ description: 'Post created successfully' });
            } else {
                const index = posts.value.data.findIndex((p: any) => p.id === response.data.data.id);
                if (index !== -1) {
                    posts.value.data[index] = {
                        ...response.data.data,
                        tags: selectedTags.value,
                    };
                    toast({ description: 'Post updated successfully' });
                }
            }

            return true;
        } catch (error: any) {
            const errors = error.response?.data.errors;
            if (errors && 'validation' in errors) {
                form.setErrors(errors.validation);
            } else {
                toast({ description: 'An unexpected error occurred.', variant: 'destructive' });
            }
            return false;
        }
    };

    const onSubmit = form.handleSubmit(async (values) => {
        const method = operation.value === 'Create' ? 'post' : 'put';
        const url = `/posts${operation.value === 'Edit' ? '/' + post.value.slug : ''}`;

        const formData = new FormData();
        formData.append('title', values.title);
        formData.append('description', values.description);
        formData.append('content', values.content);

        if (values.feature_image instanceof File) {
            formData.append('feature_image', values.feature_image);
        }

        const tagData = selectedTags.value.map(tag => tag.id || tag.title);
        formData.append('tags', JSON.stringify(tagData));

        const success = await processPostRequest(method, url, formData);

        if (success) {
            isDialogOpen.value = false;
            form.resetForm();
            selectedTags.value = [];
            tagSearchQuery.value = '';
        }
    });

    const onDialogClose = () => {
        isDialogOpen.value = false;
        form.resetForm();
        selectedTags.value = [];
        tagSearchQuery.value = '';
    };

    const handleFileChange = (event: Event) => {
        const input = event.target as HTMLInputElement;
        const file = input.files?.[0] || null;
        form.setFieldValue('feature_image', file);
    };

    const hasNoPosts = computed(() => !posts.value.data.length);

    const toggleTag = (tag: Tag) => {
        const index = selectedTags.value.findIndex(t => t.id === tag.id);
        if (index === -1) {
            selectedTags.value.push(tag);
        } else {
            selectedTags.value.splice(index, 1);
        }
    };

    const removeTag = (tag: Tag) => {
        const index = selectedTags.value.findIndex(t => t.id === tag.id);
        if (index !== -1) {
            selectedTags.value.splice(index, 1);
        }
    };

    const isTagSelected = (tag: Tag) => {
        return selectedTags.value.some(t => t.id === tag.id);
    };

    const getTagKey = (tag: Tag) => tag.id ?? `tag-${tag.title}`;

    const toggleApproval = async (post, value) => {
        try {
            const status = value ? 'approved' : 'disapproved';
            const shouldPublish = status === 'disapproved' ? false : post.is_published;

            await axios.put(route('posts.update', post.slug), {
                status: status,
                is_published: shouldPublish
            });

            post.status = status;
            post.is_published = shouldPublish;

            toast({ description: `Post ${status === 'approved' ? 'approved' : 'disapproved'} successfully` });

        } catch (error) {
            console.error('Error updating approval status:', error);
            toast({ description: 'Failed to update approval status', variant: 'destructive' });

        }
    };

    const togglePublish = async (post, value) => {
        try {
            await axios.put(route('posts.update', post.slug), {
                is_published: value
            });

            post.is_published = value;
            toast({ description: `Post ${value ? 'published' : 'unpublished'} successfully` });
        } catch (error) {
            console.error('Error updating publish status:', error);
            toast({ description: 'Failed to update publish status', variant: 'destructive' });
        }
    };

</script>

    <template>
        <div class="flex flex-col items-center bg-[#FDFDFC] p-4 text-[#1b1b18] dark:bg-[#0a0a0a] lg:justify-start">
            <Header :showNav="!route().current('posts.index')" :authenticated="props.auth.user" />
        </div>

        <Head title="Posts" />
        <component :is="props.auth.user && route().current('posts.index') ? AppLayout : 'div'"
            :breadcrumbs="props.auth.user ? breadcrumbs : undefined">
            <div class="container mx-auto py-6">
                <div class="flex items-center justify-end py-4 space-x-2"
                    v-if="route().current('posts.index') && can('create:posts')">
                    <Button size="sm" @click="handleCreateClick">
                        <Plus class="w-5 h-5" />
                        Create Post
                    </Button>
                </div>
                <template v-if="!hasNoPosts">
                    <Card
                        class="select-none shadow-sm shadow-black/10 transition-shadow hover:shadow-md hover:shadow-black/20 mb-4"
                        v-for="post in posts.data" :key="post.id"
                        v-if="route().current('posts.index') && props.auth.user">
                        <CardHeader class="!p-0">
                            <Link :href="'posts/' + post.slug">
                            <CardTitle v-if="!route().current('posts.index')">{{ post.title }}</CardTitle>
                            <CardDescription v-if="!route().current('posts.index')">{{ post.description
                                }}
                            </CardDescription>
                            </Link>
                            <CardFooter class="flex flex-wrap items-center justify-between p-4 gap-4">
                                <Link :href="'posts/' + post.slug">
                                <h3 v-if="route().current('posts.index')"
                                    class="text-2xl font-semibold tracking-tight max-w-3xl truncate">{{
                                        post.title }}</h3>
                                </Link>
                                <div class="flex items-center gap-4">
                                    <Button size="sm" @click="handleEdit(post)" v-if="is('user')">Edit</Button>
                                    <Button size="sm" variant="destructive" @click="handleDelete(post)">Delete</Button>
                                    <div class="flex items-center gap-2" v-if="can('approve:posts')">
                                        <Label for="approve-switch" class="font-medium text-sm">Approve</Label>
                                        <Switch id="approve-switch" :model-value="post.status === 'approved'"
                                            @update:model-value="(val) => toggleApproval(post, val)"
                                            aria-label="Approve post">
                                            <template #thumb>
                                                <Icon :icon="post.status === 'approved' ? 'lucide:check' : 'lucide:x'"
                                                    class="size-3" aria-hidden="true" />
                                            </template>
                                        </Switch>
                                    </div>

                                    <div class="flex items-center gap-2" v-if="can('publish:posts')">
                                        <Label for="publish-switch" class="font-medium text-sm">Publish</Label>
                                        <Switch id="publish-switch" :model-value="post.is_published"
                                            @update:model-value="(val) => togglePublish(post, val)"
                                            aria-label="Publish post"
                                            :disabled="(post.status == 'disapproved' && !can('approve:posts'))">
                                            <template #thumb>
                                                <Icon :icon="post.is_published ? 'lucide:eye' : 'lucide:eye-off'"
                                                    class="size-3" aria-hidden="true" />
                                            </template>
                                        </Switch>
                                    </div>
                                </div>
                            </CardFooter>
                        </CardHeader>
                    </Card>
                    <div v-else class="container mx-auto lg:max-w-screen-sm mt-20">
                        <div class="flex flex-wrap items-center justify-between gap-x-8 gap-y-3">
                            <h2 class="text-3xl font-bold sm:text-4xl lg:text-[40px]">The latest</h2>
                        </div>

                        <div class="mt-12 grid gap-x-8 gap-y-12 lg:grid-cols-3">
                            <div v-for="article in posts.data" :key="article.slug" class="group relative">
                                <div
                                    class="aspect-[2/1] w-full rounded-lg bg-gray-100 shadow-card transition group-hover:opacity-80">
                                    <img :src="article.feature_image" :alt="article.title + ' image'"
                                        class="h-full w-full rounded-lg object-cover" loading="lazy" />
                                </div>

                                <h3 class="mt-4 text-xl font-bold transition group-hover:text-red-600 sm:text-2xl">
                                    {{ article.title }}
                                </h3>
                                <p class="mt-4 text-lg transition group-hover:text-red-600 sm:text-xl">
                                    {{ article.description }}
                                </p>

                                <Link
                                    class="inline-flex rounded-sm transition duration-300 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 absolute inset-0 !block h-full w-full !rounded-lg"
                                    :href="'posts/' + article.slug">
                                </Link>

                                <a class="inline-flex rounded-sm transition duration-300 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 absolute inset-0 !block h-full w-full !rounded-lg"
                                    :href="'posts/' + article.slug" rel="noopener">
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
                <div v-else class="text-center py-8 text-gray-500">
                    No posts available.
                </div>
            </div>

            <DataTableDialog :isDialogOpen="isDialogOpen" :operation="operation" @onDialogClose="onDialogClose">
                <template #dialog-content>
                    <form @submit.prevent="onSubmit" class="space-y-4 p-4">
                        <FormField :validateOnBlur="false" v-slot="{ componentField }" name="title">
                            <FormItem>
                                <FormLabel>Title</FormLabel>
                                <FormControl>
                                    <Input v-bind="componentField" placeholder="Enter post title" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField :validateOnBlur="false" v-slot="{ componentField }" name="description">
                            <FormItem>
                                <FormLabel>Description</FormLabel>
                                <FormControl>
                                    <Input v-bind="componentField" placeholder="Enter post description" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField :validateOnBlur="false" v-slot="{ componentField }" name="content">
                            <FormItem>
                                <FormLabel>Content</FormLabel>
                                <FormControl>
                                    <Textarea v-bind="componentField" placeholder="Enter post content" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField :validateOnBlur="false" name="feature_image">
                            <FormItem>
                                <FormLabel>Image (optional)</FormLabel>
                                <FormControl>
                                    <Input type="file" @change="handleFileChange" accept="image/*"
                                        class="mt-1 block w-full" ref="fileInput" />
                                </FormControl>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <FormField :validateOnBlur="false" name="tags">
                            <FormItem>
                                <FormLabel>Tags</FormLabel>
                                <FormControl>
                                    <Popover>
                                        <PopoverTrigger as-child>
                                            <Button variant="outline" class="w-full justify-between">
                                                {{ selectedTags.length ?
                                                    `${selectedTags.length} tag(s) selected` : 'Select tags' }}
                                                <ChevronDown class="ml-2 h-4 w-4" />
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-[var(--radix-popover-trigger-width)] max-h-60 p-0"
                                            align="start">
                                            <div class="p-2 space-y-2">
                                                <Input v-model="tagSearchQuery" placeholder="Search tags..."
                                                    class="w-full" @click.stop />
                                                <div v-if="filteredTags.length" class="max-h-48 overflow-y-auto">
                                                    <div v-for="tag in filteredTags" :key="getTagKey(tag)"
                                                        class="flex items-center space-x-2 py-1">
                                                        <Checkbox :id="'tag-' + getTagKey(tag)"
                                                            :checked="isTagSelected(tag)"
                                                            @update:checked="toggleTag(tag)" />
                                                        <label :for="'tag-' + getTagKey(tag)" class="text-sm">{{
                                                            tag.title
                                                            }}</label>
                                                    </div>
                                                </div>
                                                <div v-else class="text-sm text-gray-500 text-center py-2">
                                                    No tags found
                                                </div>
                                            </div>
                                        </PopoverContent>
                                    </Popover>
                                </FormControl>
                                <div v-if="selectedTags.length" class="mt-2 flex flex-wrap gap-2">
                                    <span v-for="tag in selectedTags" :key="getTagKey(tag)"
                                        class="inline-flex items-center px-2 py-1 bg-gray-200 text-gray-700 text-sm rounded">
                                        {{ tag.title }}
                                        <Button variant="ghost" size="sm" class="ml-1 p-0 h-4 w-4"
                                            @click="removeTag(tag)">
                                            <X class="h-3 w-3" />
                                        </Button>
                                    </span>
                                </div>
                                <FormMessage />
                            </FormItem>
                        </FormField>

                        <div class="flex justify-end space-x-2">
                            <Button variant="outline" @click="onDialogClose">Cancel</Button>
                            <Button type="submit">Save</Button>
                        </div>
                    </form>
                </template>
            </DataTableDialog>
        </component>
    </template>