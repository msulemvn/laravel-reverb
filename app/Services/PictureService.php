<?php

namespace App\Services;

use App\Contracts\PictureServiceInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PictureService implements PictureServiceInterface
{
    protected $directory = 'pictures';

    public function upload(UploadedFile $file, ?string $oldFile = null): string
    {
        if ($oldFile && $this->exists($oldFile)) {
            $this->delete($oldFile);
        }

        $timestamp = now()->format('YmdHs');
        $originalFileName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $newFileName = Str::slug($originalFileName) . '-' . $timestamp . '.' . $extension;
        $file->storeAs('pictures', $newFileName, 'public');

        return $newFileName;
    }

    public function delete(string $fileName): bool
    {
        $filePath = storage_path("app/public/{$this->directory}/{$fileName}");
        if (File::exists($filePath)) {
            return File::delete($filePath);
        }

        return false;
    }

    public function exists(string $fileName): bool
    {
        $filePath = storage_path("app/public/{$this->directory}/{$fileName}");
        return File::exists($filePath);
    }
}
