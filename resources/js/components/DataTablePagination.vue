<script setup lang="ts">
import { onMounted, computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-vue-next';
import type { PaginationMeta } from '@/types';

interface DataTablePaginationProps {
    table: any;
    meta: PaginationMeta;
}

const props = defineProps<DataTablePaginationProps>();
const table = ref(props.table);
const emit = defineEmits(['table-instance']);

const currentPage = computed(() => props.meta?.current_page || 1);
const pageCount = computed(() => props.meta?.last_page || 1);
const canPreviousPage = computed(() => currentPage.value > 1);
const canNextPage = computed(() => currentPage.value < pageCount.value);
const links = computed(() => props.meta.links);

onMounted(() => {
    emit('table-instance', table);
});

const handleClickFirstPage = () => {
    if (props.meta.current_page > 1) {
        const firstPageLink = links.value.find((page) => page.label === '1');
        if (firstPageLink?.url) {
            router.get(firstPageLink.url, {}, { preserveScroll: true });
        }
    }
};

const handleClickLastPage = () => {
    if (props.meta.current_page < props.meta.last_page) {
        const lastPageLink = links.value.find((page) => page.label === props.meta.last_page.toString());
        if (lastPageLink?.url) {
            router.get(lastPageLink.url, {}, { preserveScroll: true });
        }
    }
};

const handleClickPrevPage = () => {
    if (props.meta.current_page > 1) {
        const prevPageLink = links.value.find((page) => page.label === '&laquo; Previous');
        if (prevPageLink?.url) {
            router.get(prevPageLink.url, {}, { preserveScroll: true });
        }
    }
};

const handleClickNextPage = () => {
    if (props.meta.current_page < props.meta.last_page) {
        const nextPageLink = links.value.find((page) => page.label === 'Next &raquo;');
        if (nextPageLink?.url) {
            router.get(nextPageLink.url, {}, { preserveScroll: true });
        }
    }
};

</script>

<template>
    <div v-if="props.table"
        class="flex w-full flex-col-reverse items-center justify-end gap-4 overflow-auto p-1 sm:flex-row sm:gap-8">
        <div class="flex flex-col-reverse items-center gap-4 sm:flex-row sm:gap-6 lg:gap-8">
            <div class="flex items-center justify-center text-sm font-medium">
                Page {{ currentPage }} of {{ pageCount }}
            </div>
            <div class="flex items-center space-x-2">
                <Button aria-label="Go to first page" variant="outline" class="hidden size-8 p-0 lg:flex"
                    @click="handleClickFirstPage" :disabled="!canPreviousPage">
                    <ChevronsLeft class="size-4" aria-hidden="true" />
                </Button>
                <Button aria-label="Go to previous page" variant="outline" size="icon" class="size-8"
                    @click="handleClickPrevPage" :disabled="!canPreviousPage">
                    <ChevronLeft class="size-4" aria-hidden="true" />
                </Button>
                <Button aria-label="Go to next page" variant="outline" size="icon" class="size-8"
                    @click="handleClickNextPage" :disabled="!canNextPage">
                    <ChevronRight class="size-4" aria-hidden="true" />
                </Button>
                <Button aria-label="Go to last page" variant="outline" size="icon" class="hidden size-8 lg:flex"
                    @click="handleClickLastPage" :disabled="!canNextPage">
                    <ChevronsRight class="size-4" aria-hidden="true" />
                </Button>
            </div>
        </div>
    </div>
</template>
