<script setup lang="ts">
import Header from '@/components/Header.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card';
import { Link, router } from '@inertiajs/vue3';
import { AspectRatio } from '@/components/ui/aspect-ratio'
import { Button } from '@/components/ui/button'
import {
    Command,
    CommandInput
} from '@/components/ui/command'
import { ref, computed } from 'vue'
import axios from 'axios'
import Pagination from '@/components/Pagination.vue';
const props = defineProps<{
    auth: any;
    posts: any;
}>();
import { route } from 'ziggy-js';

const query = ref('')
const posts = computed(() => props.posts.data);
const meta = ref(props.posts.meta);
const pagination = ref();

const handleSearch = async () => {
    try {
        const queryParams = new URLSearchParams({ q: query.value }).toString();
        const url = (route().current('home.blog') ? '/blog' : '') + `/search?${queryParams}`;
        const response = await axios({
            method: 'get',
            url,
        });

        if (response.status === 200) {
            props.posts.data = response.data;
        }

    } catch (error: any) {
    }
}
</script>

<template>

    <Head title="Home" />

    <header class="flex flex-col items-center bg-[#FDFDFC] p-4 text-[#1b1b18] dark:bg-[#0a0a0a] lg:justify-start">
        <Header :showNav="true" :authenticated="props.auth.user" />
    </header>
    <!-- Hero Section -->
    <section class="flex items-center justify-center h-80 bg-gray-100 text-black">
        <div class="text-center">
            <h1 class="text-5xl font-bold sm:text-6xl">Bloggers</h1>
            <p class="mt-4 text-gray-600">All latest blogs</p>
            <div class="flex items-center justify-end items-center p-4">
                <Command class="rounded-lg border shadow-md max-w-sm mr-4 flex flex-row">
                    <CommandInput placeholder="Search" class="w-full text-md" v-model="query" />
                </Command>
                <Button
                    class="h-10 rounded-lg border border-transparent bg-red-600 leading-none text-white transition duration-300 ease-in-out hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-red-600/50"
                    @click="handleSearch">Search</Button>
            </div>

        </div>
    </section>

    <main class="flex-grow py-12 bg-white dark:bg-gray-950">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <Link v-for="card in posts" :key="card.id" :href="card.slug">
                <Card
                    class="group overflow-hidden rounded-lg shadow-md transition-all duration-300 hover:shadow-xl hover:translate-y-[-4px]">
                    <CardHeader class="!aspect-[2/1] w-full p-0 relative aspect-video overflow-hidden">
                        <AspectRatio :ratio="16 / 9">
                            <!-- <img :src="card.feature_image" class="rounded-md object-cover w-full h-full"> -->
                            <PlaceholderPattern class=" w-full h-full" />
                        </AspectRatio>
                    </CardHeader>
                    <CardContent class="p-4">
                        <CardTitle class="text-xl font-bold transition group-hover:text-red-600 sm:text-2xl">
                            {{ card.title }}
                        </CardTitle>
                        <CardDescription class="mt-3 max-w-2xl text-gray-600 leading-none truncate">
                            {{ card.description }}
                        </CardDescription>
                    </CardContent>
                </Card>
                </Link>
            </div>
            <Pagination :meta="props.posts.meta" ref="pagination">
                <div class="mt-20">
                    <nav role="navigation" aria-label="Pagination Navigation" class="flex justify-between">
                        <Button
                            class="h-10 rounded-lg border border-transparent bg-red-600 leading-none text-white transition duration-300 ease-in-out hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-red-600/50"
                            @click="pagination?.handleClickPrevPage"
                            :disabled="!pagination?.canPreviousPage"><span>←</span>Previous</Button>
                        <Button
                            class="h-10 rounded-lg border border-transparent bg-red-600 leading-none text-white transition duration-300 ease-in-out hover:bg-red-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-600/80 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:bg-red-600/50"
                            @click="pagination?.handleClickNextPage"
                            :disabled="!pagination?.canNextPage">Next<span>→</span></Button>
                    </nav>
                </div>
            </Pagination>
        </div>
    </main>

    <!-- Call to Action Section -->
    <section class="flex items-center justify-center h-80 bg-gray-100 text-black">
        <div class="text-center">
            <h1 class="text-4xl font-bold">Ready to get started?</h1>
            <p class="mt-4 text-lg text-gray-600">Join us today and explore the possibilities!</p>
            <Button variant="secondary" class="mt-6">Get Started Now</Button>
        </div>
    </section>

    <footer class="py-8 px-4 bg-white dark:bg-gray-900">
        <div class="container mx-auto text-center">
            <p class="text-gray-700 dark:text-gray-300">&copy; 2025 YourCompany. All rights reserved.</p>
            <div class="flex justify-center space-x-6 mt-4">
                <a href="#"
                    class="text-sm text-gray-600 hover:text-[#fdfdfe]-500 dark:text-gray-400 dark:hover:text-[#fdfdfe]-400">
                    Privacy Policy
                </a>
                <a href="#"
                    class="text-sm text-gray-600 hover:text-[#fdfdfe]-500 dark:text-gray-400 dark:hover:text-[#fdfdfe]-400">
                    Terms of Service
                </a>
            </div>
        </div>
    </footer>
</template>