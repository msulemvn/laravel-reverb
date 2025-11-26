<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import type { PaginationMeta } from '@/types';

const props = defineProps<{
    meta: PaginationMeta
}>();
const currentPage = computed(() => props.meta?.current_page || 1);
const pageCount = computed(() => props.meta?.last_page || 1);
const canPreviousPage = computed(() => currentPage.value > 1);
const canNextPage = computed(() => currentPage.value < pageCount.value);
const links = computed(() => props.meta?.links);

const handleClickFirstPage = () => {
    if (props.meta?.current_page > 1) {
        const firstPageLink = links.value.find((page) => page.label === '1');
        if (firstPageLink?.url) {
            router.get(firstPageLink.url, {}, { preserveScroll: true });
        }
    }
};

const handleClickLastPage = () => {
    if (props.meta?.current_page < props.meta?.last_page) {
        const lastPageLink = links.value.find((page) => page.label === props.meta?.last_page.toString());
        if (lastPageLink?.url) {
            router.get(lastPageLink.url, {}, { preserveScroll: true });
        }
    }
};

const handleClickPrevPage = () => {
    if (props.meta?.current_page > 1) {
        const prevPageLink = links.value.find((page) => page.label === '&laquo; Previous');
        if (prevPageLink?.url) {
            router.get(prevPageLink.url, {}, { preserveScroll: true });
        }
    }
};

const handleClickNextPage = () => {
    if (props.meta?.current_page < props.meta?.last_page) {
        const nextPageLink = links.value.find((page) => page.label === 'Next &raquo;');
        if (nextPageLink?.url) {
            router.get(nextPageLink.url, {}, { preserveScroll: true });
        }
    }
};

defineExpose({
    handleClickPrevPage,
    handleClickNextPage,
    canNextPage,
    canPreviousPage
});
</script>

<template>
    <slot />
</template>
