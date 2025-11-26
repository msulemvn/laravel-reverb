<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { Head, usePage } from "@inertiajs/vue3";
import { ref, onMounted, onUnmounted } from "vue";
import axios from "axios";
import { useToast } from "@/components/ui/toast/use-toast";
import { Button } from "@/components/ui/button";
import { Progress } from "@/components/ui/progress";
import type { SharedData } from "@/types";

const { toast } = useToast();
const page = usePage<SharedData>();
const breadcrumbs = [{ title: "Files", href: "/files" }];

const selectedFile = ref<File | null>(null);
const isUploading = ref(false);
const uploadProgress = ref(0);
const uploadedFiles = ref<Array<{ filename: string; url: string; message: string }>>([]);
const processingMessage = ref<string>("");

const handleFileChange = (e: Event) => {
  const target = e.target as HTMLInputElement;
  selectedFile.value = target.files?.[0] || null;
  uploadProgress.value = 0;
};

const uploadFile = async () => {
  if (!selectedFile.value) {
    toast({ description: "Please select a file first", variant: "destructive" });
    return;
  }

  const CHUNK_SIZE = 1 * 1024 * 1024; // 1MB chunks (fits default PHP 2M upload limit)
  const file = selectedFile.value;
  const totalChunks = Math.ceil(file.size / CHUNK_SIZE);

  // Generate unique file identifier
  const fileIdentifier = `${Date.now()}_${Math.random().toString(36).substring(7)}`;

  isUploading.value = true;
  uploadProgress.value = 0;
  processingMessage.value = "Preparing upload...";

  try {
    // Upload each chunk
    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
      const start = chunkIndex * CHUNK_SIZE;
      const end = Math.min(start + CHUNK_SIZE, file.size);
      const chunk = file.slice(start, end);

      const formData = new FormData();
      formData.append("chunk", chunk);
      formData.append("chunkIndex", chunkIndex.toString());
      formData.append("totalChunks", totalChunks.toString());
      formData.append("fileIdentifier", fileIdentifier);
      formData.append("originalFileName", file.name);

      processingMessage.value = `Uploading chunk ${chunkIndex + 1}/${totalChunks}...`;

      await axios.post("/files/chunk", formData, {
        headers: { "Content-Type": "multipart/form-data" },
      });

      // Update progress based on uploaded chunks (0-50% for upload)
      uploadProgress.value = Math.round(((chunkIndex + 1) / totalChunks) * 50);
    }

    processingMessage.value = "All chunks uploaded! Processing in background...";
    uploadProgress.value = 50;

    toast({
      description: "File uploaded successfully! Processing chunks now...",
    });
  } catch (error: any) {
    console.error("Upload failed:", error);
    const errorMessage = error.response?.data?.message || "Upload failed";
    toast({ description: errorMessage, variant: "destructive" });
    isUploading.value = false;
    uploadProgress.value = 0;
    processingMessage.value = "";
  }
};

onMounted(() => {
  const userId = page.props.auth.user.id;

  window.Echo.private(`user.${userId}`)
    .listen(
      ".file-processing-progress",
      (event: { progress: number; status: string; message: string }) => {
        console.log("Processing progress:", event);

        // Map backend progress (0-100%) to frontend progress (50-100%)
        // This ensures smooth transition from upload phase (0-50%) to processing phase (50-100%)
        uploadProgress.value = 50 + Math.round(event.progress / 2);
        processingMessage.value = event.message;

        if (event.status === "completed") {
          uploadProgress.value = 100;
          setTimeout(() => {
            isUploading.value = false;
            selectedFile.value = null;
            uploadProgress.value = 0;
            processingMessage.value = "";
          }, 2000);
        } else if (event.status === "failed") {
          isUploading.value = false;
          uploadProgress.value = 0;
          processingMessage.value = "";
          toast({
            title: "Processing Failed",
            description: event.message,
            variant: "destructive",
          });
        }
      }
    )
    .listen(
      ".file-uploaded",
      (event: { filename: string; url: string; message: string }) => {
        console.log("File upload completed:", event);
        uploadedFiles.value.unshift(event);
        toast({
          title: "Upload Complete!",
          description: event.message,
        });
      }
    );
});

onUnmounted(() => {
  const userId = page.props.auth.user.id;
  window.Echo.leave(`user.${userId}`);
});
</script>

<template>
  <Head title="Files" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="container mx-auto py-10 space-y-6">
      <h1 class="text-2xl font-bold">File Upload</h1>

      <div class="space-y-4">
        <input
          type="file"
          @change="handleFileChange"
          :disabled="isUploading"
          accept="image/*"
        />

        <div v-if="selectedFile" class="text-sm text-gray-600">
          Selected: {{ selectedFile.name }} ({{
            (selectedFile.size / 1024 / 1024).toFixed(2)
          }}
          MB)
        </div>

        <div v-if="isUploading" class="space-y-2">
          <div class="flex items-center justify-between text-sm">
            <span>{{ processingMessage }}</span>
            <span>{{ uploadProgress }}%</span>
          </div>
          <Progress :model-value="uploadProgress" />
        </div>

        <Button @click="uploadFile" :disabled="!selectedFile || isUploading">
          {{ isUploading ? "Uploading..." : "Upload" }}
        </Button>
      </div>

      <div v-if="uploadedFiles.length > 0" class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Uploaded Files</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <div
            v-for="file in uploadedFiles"
            :key="file.filename"
            class="border rounded-lg p-4 space-y-2"
          >
            <img
              :src="file.url"
              :alt="file.filename"
              class="w-full h-48 object-cover rounded"
            />
            <p class="text-sm text-gray-600 truncate">{{ file.filename }}</p>
            <p class="text-xs text-gray-500">{{ file.message }}</p>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
