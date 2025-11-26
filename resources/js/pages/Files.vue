<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";
import { useToast } from "@/components/ui/toast/use-toast";
import { Button } from "@/components/ui/button";

const { toast } = useToast();
const breadcrumbs = [{ title: "Files", href: "/files" }];

const selectedFile = ref<File | null>(null);

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  selectedFile.value = target.files?.[0] || null;
};

const uploadFile = async () => {
  if (!selectedFile.value) {
    toast({ description: "Please select a file first", variant: "destructive" });
    return;
  }

  const formData = new FormData();
  formData.append("file", selectedFile.value);

  try {
    await axios.post("/files", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });

    toast({ description: "File uploaded successfully" });
    selectedFile.value = null;
  } catch (error) {
    toast({ description: "Upload failed", variant: "destructive" });
  }
};
</script>

<template>
  <Head title="Files" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto py-10 space-y-6">
      <h1 class="text-2xl font-bold">Select file</h1>

      <input type="file" @change="handleFileChange" />

      <Button @click="uploadFile">Upload</Button>
    </div>
  </AppLayout>
</template>
