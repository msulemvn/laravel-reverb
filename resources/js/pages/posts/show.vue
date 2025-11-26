<script setup lang="ts">
import { type BreadcrumbItem, type Post } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Card, CardContent, CardHeader, CardFooter } from '@/components/ui/card';
import { ref, onMounted, computed } from 'vue';
import Quill from 'quill';
import { QuillyEditor } from 'vue-quilly';
import 'quill/dist/quill.core.css';
import 'quill/dist/quill.snow.css';
import 'quill/dist/quill.bubble.css';
import PostHeader from '@/components/Header.vue';
import axios from 'axios';
import { useToast } from '@/components/ui/toast/use-toast';
import { Avatar, AvatarImage, AvatarFallback } from "@/components/ui/avatar"
import { Link } from "@inertiajs/vue3";

const { toast } = useToast();

interface Post {
    id?: number;
    title?: string;
}

const props = defineProps<{
    post: Post;
    auth: any;
    author: any;
}>();
const author = computed(() => props.post.data.author.name);
// Helper function to get initials for avatar fallback
const getInitials = (name: string): string => {
    return name
        .split(' ')
        .map(part => part.charAt(0))
        .join('')
        .toUpperCase();
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Posts', href: '/posts' },
    { title: props.post.data.slug, href: '' }
];

const options = {
    theme: 'snow',
    modules: {
        toolbar: true,
    },
    placeholder: 'Compose an epic...',
    readOnly: false,
};

const commentsRef = ref(props.post.data.comments);
const comments = computed(() => {
    // Filter top-level comments (no parent_comment_id)
    const topLevelComments = commentsRef.value
        .filter(comment => !comment.parent_comment_id)
        .map(comment => ({
            id: comment.id,
            author: comment.user.name,
            content: comment.body,
            date: new Date(comment.created_at).toLocaleDateString(),
            userId: comment.user.id,
            replies: []
        }));

    // Add replies to their parent comments
    commentsRef.value
        .filter(comment => comment.parent_comment_id)
        .forEach(reply => {
            const parentComment = topLevelComments.find(c => c.id === reply.parent_comment_id);
            if (parentComment) {
                parentComment.replies.push({
                    id: reply.id,
                    author: reply.user.name,
                    content: reply.body,
                    date: new Date(reply.created_at).toLocaleDateString(),
                    userId: reply.user.id,
                    parentId: reply.parent_comment_id
                });
            }
        });

    return topLevelComments;
});

const totalComments = computed(() => {
    return props.post.data.comments.length;
});

const newComment = ref('');
const replyingTo = ref(null);
const newReply = ref('');
const editingComment = ref(null);

const postComment = async () => {
    if (!newComment.value.trim()) return;
    try {
        const response = await axios.post(`/comments`, {
            commentable_id: props.post.data.id,
            commentable_type: 'post',
            body: newComment.value
        });

        toast({ description: 'Comment posted successfully' });

        newComment.value = '';
        if (response.status === 201) {
            const newComment = {
                ...response.data.data,
            };
            commentsRef.value.push(newComment);
            toast({ description: 'Post created successfully' });
        }
    } catch (error) {
        toast({ description: 'Failed to post comment', variant: 'destructive' });
        console.error('Error posting comment:', error);
    }
};

const startReply = (commentId) => {
    replyingTo.value = commentId;
    newReply.value = '';
};

const cancelReply = () => {
    replyingTo.value = null;
    newReply.value = '';
};

const submitReply = async (parentId) => {
    if (!newReply.value.trim()) return;

    try {
        const response = await axios.post(`/comments`, {
            body: newReply.value,
            commentable_id: props.post.data.id,
            commentable_type: 'post',
            parent_comment_id: parentId
        });

        if (response.status === 201) {
            const newComment = {
                ...response.data.data,
            };
            commentsRef.value.push(newComment);
            toast({ description: 'Reply added successfully' });
        }

        newReply.value = '';
        replyingTo.value = null;
    } catch (error) {
        toast({ description: 'Failed to post reply', variant: 'destructive' });
        console.error('Error posting reply:', error);
    }
};

const editComment = (comment) => {
    editingComment.value = { id: comment.id, content: comment.content };
};

const cancelEdit = () => {
    editingComment.value = null;
};

const updateComment = async () => {
    if (!editingComment.value || !editingComment.value.content.trim()) return;

    try {
        const response = await axios.put(`/comments/${editingComment.value.id}`, {
            body: editingComment.value.content
        });

        commentsRef.value = commentsRef.value.map(c => c.id === editingComment.value.id ? { ...c, ...response.data.data } : c);
        editingComment.value = null;

        toast({ description: 'Comment updated successfully' });

    } catch (error) {
        toast({ description: 'Failed to update comment', variant: 'destructive' });
        console.error('Error updating comment:', error);
    }
};

const deleteComment = async (id) => {
    try {
        await axios.delete(`/comments/${id}`);
        commentsRef.value = commentsRef.value.filter(comment => comment.id !== id);

        toast({ description: 'Comment deleted successfully' });
    } catch (error) {
        toast({ description: 'Failed to delete comment', variant: 'destructive' });
        console.error('Error deleting comment:', error);
    }
};

const editor = ref<InstanceType<typeof QuillyEditor>>();
const model = ref<string>(props.post.data.content);

let quill: Quill | null = null;
onMounted(() => {
    quill = editor.value?.initialize(Quill)!
})
</script>

<template>
    <div class="flex flex-col items-center bg-[#FDFDFC] p-4 text-[#1b1b18] dark:bg-[#0a0a0a] lg:justify-start">
        <PostHeader :showNav="true" :authenticated="props.auth.user" />
    </div>

    <Head :title="props.post.data.title" />

    <div :breadcrumbs="props.auth.user ? breadcrumbs : undefined" class="container mx-auto lg:max-w-screen-sm mt-20">
        <div class="flex flex-1 flex-col gap-6 items-left">

            <Card class="w-full overflow-hidden border-0">
                <CardHeader v-if="props.post.data.feature_image" class="my-6 p-0">
                    <CardTitle class="text-4xl font-bold">{{ props.post.data.title }}</CardTitle>
                    <CardDescription class="text-l mb-4">{{ props.post.data.description }}</CardDescription>
                    <img :src="props.post.data.feature_image" alt="Feature Image"
                        class="w-full h-96 object-fit !rounded-sm" />
                </CardHeader>

                <CardContent class="!p-0 mt-6">
                    <!-- <QuillyEditor class="min-h-[10vh] border !rounded-sm" ref="editor" v-model="model"
                        :options="options" v-if="props.auth.user && props.auth.user.id == props.post.data.user_id"
                        @update:modelValue="(value) => console.log('HTML model updated:', value)"
                        @text-change="({ delta, oldContent, source }) => console.log('text-change', delta, oldContent, source)"
                        @selection-change="({ range, oldRange, source }) => console.log('selection-change', range, oldRange, source)"
                        @editor-change="(eventName) => console.log('editor-change', `eventName: ${eventName}`)"
                        @focus="(quill) => console.log('focus', quill)" @blur="(quill) => console.log('blur', quill)"
                        @ready="(quill) => console.log('ready', quill)" />
                    <div v-else v-html="props.post.data.content" class="prose max-w-none"></div> -->
                    <div v-html="props.post.data.content" class="prose max-w-none"></div>
                </CardContent>

                <CardFooter class="!p-6">
                    <div v-if="props.post.data.tags && props.post.data.tags.length" class="flex flex-wrap gap-2">
                        <span v-for="tag in props.post.data.tags" :key="tag.id"
                            class="px-3 py-1 bg-muted text-foreground text-sm rounded">
                            {{ tag.title }}
                        </span>
                    </div>
                </CardFooter>
            </Card>
            <Card class="pb-6 border-0">
                <div class="flex flex-col items-start space-y-4 sm:flex-row sm:space-x-6 sm:space-y-0">
                    <Avatar class="h-20 w-20 !rounded-sm">
                        <AvatarImage style="object-fit: contain" class="p-[0.3rem] pb-0" src="/user.svg"
                            :alt="`${author} photo`" />
                        <AvatarFallback>{{ getInitials(author) }}</AvatarFallback>
                    </Avatar>
                    <div>
                        <p class="font-display text-2xl font-bold leading-none text-black" itemprop="author">
                            <Link
                                class="inline-flex rounded-sm transition duration-300 leading-none focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80"
                                :href="'/@' + author.toLowerCase()" rel="author">
                            {{ author }}
                            </Link>
                        </p>
                        <div
                            class="prose prose-sm mt-2 text-gray-600 prose-a:text-red-600 prose-a:transition prose-a:hover:text-red-700">
                            <p>Author @Bloggers</p>
                        </div>
                        <div class="flex gap-2">
                            <Link v-if="author.twitter"
                                class="inline-flex h-5 w-5 items-center justify-start !rounded-full transition duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 hover:opacity-80"
                                :href="'https://twitter.com/' + author.twitter" target="_blank"
                                rel="noopener noreferrer">
                            <img class="h-4 w-4" src="https://picperf.io/https://laravel-news.com/images/x.svg"
                                loading="lazy" alt="X" />
                            <span class="sr-only">X</span>
                            </Link>
                            <Link v-if="author.github"
                                class="inline-flex h-5 w-5 items-center justify-center !rounded-full transition duration-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 hover:opacity-80"
                                :href="'https://github.com/' + author.github" target="_blank" rel="noopener noreferrer">
                            <img class="h-4 w-4" src="https://picperf.io/https://laravel-news.com/images/github.svg"
                                loading="lazy" alt="GitHub" />
                            <span class="sr-only">GitHub</span>
                            </Link>
                        </div>
                    </div>
                </div>
            </Card>
        </div>

        <Card v-if="props.auth.user" class="w-full mt-6">
            <CardHeader>
                <h2 class="text-xl font-semibold">Comments ({{ totalComments }})</h2>
            </CardHeader>
            <CardContent>
                <div class="mb-6">
                    <textarea v-model="newComment"
                        class="w-full p-3 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary" rows="3"
                        placeholder="Write a comment..."></textarea>
                    <button @click="postComment"
                        class="mt-2 px-4 py-2 bg-primary text-primary-foreground rounded-md">Post Comment</button>
                </div>

                <div v-for="comment in comments" :key="comment.id" class="border-b pb-4 mb-4 last:border-b-0 last:pb-0">
                    <div class="bg-muted/30 p-3 rounded-md">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">{{ comment.author }}</h3>
                            <span class="text-sm text-muted-foreground">{{ comment.date }}</span>
                        </div>

                        <div v-if="editingComment && editingComment.id === comment.id">
                            <textarea v-model="editingComment.content"
                                class="w-full p-2 border rounded-md mt-2"></textarea>
                            <div class="flex gap-2 mt-2">
                                <button @click="updateComment"
                                    class="px-3 py-1 bg-primary text-primary-foreground rounded-md text-sm">Save</button>
                                <button @click="cancelEdit"
                                    class="px-3 py-1 bg-muted text-muted-foreground rounded-md text-sm">Cancel</button>
                            </div>
                        </div>

                        <div v-else>
                            <p class="mt-1">{{ comment.content }}</p>
                            <div class="flex gap-2 mt-2">
                                <button @click="startReply(comment.id)"
                                    class="text-sm text-primary hover:underline">Reply</button>
                                <template v-if="props.auth.user && props.auth.user.id === comment.userId">
                                    <button @click="editComment(comment)"
                                        class="text-sm text-primary hover:underline">Edit</button>
                                    <button @click="deleteComment(comment.id)"
                                        class="text-sm text-destructive hover:underline">Delete</button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div v-if="replyingTo === comment.id" class="mt-2 pl-6">
                        <textarea v-model="newReply"
                            class="w-full p-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-primary"
                            rows="2" placeholder="Write a reply..."></textarea>
                        <div class="flex gap-2 mt-2">
                            <button @click="submitReply(comment.id)"
                                class="px-3 py-1 bg-primary text-primary-foreground rounded-md text-sm">Reply</button>
                            <button @click="cancelReply"
                                class="px-3 py-1 bg-muted text-muted-foreground rounded-md text-sm">Cancel</button>
                        </div>
                    </div>

                    <div v-if="comment.replies && comment.replies.length > 0" class="mt-3 pl-6">
                        <div v-for="reply in comment.replies" :key="reply.id" class="mb-2 bg-muted/20 p-3 rounded-md">
                            <div class="flex items-center justify-between">
                                <h4 class="font-medium">{{ reply.author }}</h4>
                                <span class="text-sm text-muted-foreground">{{ reply.date }}</span>
                            </div>

                            <div v-if="editingComment && editingComment.id === reply.id">
                                <textarea v-model="editingComment.content"
                                    class="w-full p-2 border rounded-md mt-2"></textarea>
                                <div class="flex gap-2 mt-2">
                                    <button @click="updateComment"
                                        class="px-3 py-1 bg-primary text-primary-foreground rounded-md text-sm">Save</button>
                                    <button @click="cancelEdit"
                                        class="px-3 py-1 bg-muted text-muted-foreground rounded-md text-sm">Cancel</button>
                                </div>
                            </div>

                            <div v-else>
                                <p class="mt-1">{{ reply.content }}</p>
                                <div v-if="props.auth.user && props.auth.user.id === reply.userId"
                                    class="flex gap-2 mt-2">
                                    <button @click="editComment(reply)"
                                        class="text-sm text-primary hover:underline">Edit</button>
                                    <button @click="deleteComment(reply.id)"
                                        class="text-sm text-destructive hover:underline">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>

        <div v-else-if="!props.auth.user"
            class="mt-6 p-6 text-center border border-dashed border-muted rounded-lg bg-muted/50">
            <p class="text-md text-muted-foreground">
                Please
                <a href="/login" class="text-primary font-medium hover:underline">log in</a>
                to leave a comment.
            </p>
        </div>
    </div>
</template>