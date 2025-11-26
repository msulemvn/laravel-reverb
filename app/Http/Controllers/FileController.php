<?php

namespace App\Http\Controllers;

use App\Contracts\PictureServiceInterface;
use App\Events\FileEvent;
use App\Jobs\CombineFileChunks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class FileController extends Controller
{
    public function __construct(
        protected PictureServiceInterface $pictureService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Files');
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $file = $request->file('file');

        $newFileName = $this->pictureService->upload(
            $file,
            pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)
                .'_'.time().'.'.$file->getClientOriginalExtension()
        );

        $fileUrl = Storage::url('pictures/'.$newFileName);

        // Broadcast file upload completion immediately
        broadcast(new FileEvent(
            $newFileName,
            $fileUrl,
            $request->user()->id,
            'Your file has been uploaded successfully!'
        ));

        return apiResponse(
            data: [
                'filename' => $newFileName,
                'url' => $fileUrl,
            ],
            message: 'File uploaded successfully',
            statusCode: SymfonyResponse::HTTP_CREATED
        );
    }

    public function storeChunk(Request $request): JsonResponse
    {
        $request->validate([
            'chunk' => ['required', 'file', 'max:1536'], // 1.5MB = 1536KB max per chunk
            'chunkIndex' => ['required', 'integer', 'min:0'],
            'totalChunks' => ['required', 'integer', 'min:1'],
            'fileIdentifier' => ['required', 'string'],
            'originalFileName' => ['required', 'string'],
        ]);

        $chunk = $request->file('chunk');

        // Check if chunk was uploaded successfully
        if (! $chunk || ! $chunk->isValid()) {
            Log::error('Chunk upload failed', [
                'has_chunk' => $request->hasFile('chunk'),
                'chunk_error' => $chunk ? $chunk->getErrorMessage() : 'No file',
                'post_max_size' => ini_get('post_max_size'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
            ]);

            return apiResponse(
                data: [],
                message: 'Chunk upload failed: '.($chunk ? $chunk->getErrorMessage() : 'No file received'),
                statusCode: 400
            );
        }

        // Manual size check - enforce 1MB chunks to work with default PHP limits
        if ($chunk->getSize() > 1.5 * 1024 * 1024) {
            return apiResponse(
                data: [],
                message: 'Chunk size exceeds 1.5MB limit',
                statusCode: 413
            );
        }
        $chunkIndex = $request->input('chunkIndex');
        $totalChunks = $request->input('totalChunks');
        $fileIdentifier = $request->input('fileIdentifier');
        $originalFileName = $request->input('originalFileName');
        $userId = $request->user()->id;

        // Create temp directory for chunks
        $tempDir = storage_path("app/temp/chunks/{$fileIdentifier}");
        if (! file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        // Store chunk
        $chunk->move($tempDir, "chunk_{$chunkIndex}");

        // Check if all chunks are uploaded
        $uploadedChunks = glob("{$tempDir}/chunk_*");
        $isComplete = count($uploadedChunks) === (int) $totalChunks;

        if ($isComplete) {
            // Dispatch job to combine all chunks
            CombineFileChunks::dispatch(
                $fileIdentifier,
                $originalFileName,
                $totalChunks,
                $userId
            );
        }

        return apiResponse(
            data: [
                'chunkIndex' => $chunkIndex,
                'totalChunks' => $totalChunks,
                'isComplete' => $isComplete,
            ],
            message: $isComplete ? 'All chunks uploaded, processing...' : 'Chunk uploaded successfully',
            statusCode: SymfonyResponse::HTTP_CREATED
        );
    }
}
