<?php

namespace App\Contracts;

use Illuminate\Http\UploadedFile;

interface PictureServiceInterface
{
    public function upload(UploadedFile $file, ?string $oldFile = null): string;
    public function delete(string $fileName): bool;
    public function exists(string $fileName): bool;
}
