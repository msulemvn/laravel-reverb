<?php

namespace App\Http\Controllers;

use App\Contracts\PictureServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        return apiResponse(
            data: [
                'filename' => $newFileName,
                'url' => Storage::url('pictures/'.$newFileName),
            ],
            message: 'File uploaded successfully',
            statusCode: SymfonyResponse::HTTP_CREATED
        );
    }
}
