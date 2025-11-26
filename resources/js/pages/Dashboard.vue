<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type CardItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import DashboardItem from '@/components/DashboardItem.vue';
import { Users, FileText, Tag } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
const props = defineProps<{ count: any }>();

const cardItems: CardItem[] = [
    {
        title: "Users",
        description: "Active users in the system",
        icon: Users,
        count: props.count.users,
        route: "/users",
        access: "admin"
    },
    {
        title: "Posts",
        description: "Published content items",
        icon: FileText,
        count: props.count.posts,
        route: "/posts",
        access: "admin | user"
    },
    {
        title: "Tags",
        description: "Content categorization tags",
        icon: Tag,
        count: props.count.tags,
        route: "/tags",
        access: "user"
    }
];

</script>

<template>

    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-1 flex-col gap-4 rounded-xl p-6">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <DashboardItem :card-items="cardItems" />
            </div>
        </div>
    </AppLayout>
</template>
