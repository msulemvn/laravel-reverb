<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    showNav: Boolean,
    authenticated: Boolean
});
</script>

<template>
    <header v-if="showNav" class="my-4 w-full max-w-[335px] text-sm lg:max-w-4xl">
        <nav class="flex items-center justify-between gap-4">
            <Link :href="'/'"
                class="flex items-center no-underline text-brand inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
            <img src="https://laravel.com/img/logomark.min.svg" class="w-10" alt="Laravel Logo Mark">
            <img class="text-xl ml-3 w-24" src="https://laravel.com/img/logotype.min.svg" alt="Laravel Logo Text">
            </Link>
            <div class="flex items-center gap-4">
                <Link :href="route(authenticated ? 'dashboard' : 'login')"
                    class="inline-block rounded-sm border border-transparent px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#19140035] dark:text-[#EDEDEC] dark:hover:border-[#3E3E3A]">
                {{ authenticated ? 'Dashboard' : 'Log in' }}
                </Link>
                <Link :href="route('register')" v-if="!authenticated"
                    class="inline-block rounded-sm border border-[#19140035] px-5 py-1.5 text-sm leading-normal text-[#1b1b18] hover:border-[#1915014a] dark:border-[#3E3E3A] dark:text-[#EDEDEC] dark:hover:border-[#62605b]">
                Register
                </Link>
            </div>
        </nav>
    </header>
</template>