<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Http\Resources\TagResource;
use Symfony\Component\HttpFoundation\Response as symfonyResponse;
use App\Http\Requests\Tag\StoreTagRequest;
use App\Http\Requests\Tag\UpdateTagRequest;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(): Response
    {
        $tags = Tag::latest()->paginate();
        $tags = TagResource::collection($tags);
        return Inertia::render("Tags", compact("tags"));
    }

    public function store(StoreTagRequest $request)
    {
        $tag = Tag::create($request->validated());

        logActivity(request: $request, description: "User created a new tag", showable: true);
        return apiResponse(message: "Tag added successfully", data: TagResource::make($tag), statusCode: symfonyResponse::HTTP_CREATED);
    }

    public function update(UpdateTagRequest $request, Tag $tag)
    {
        $tag->fill($request->validated());
        $tag->save();

        logActivity(request: $request, description: "User updated a tag", showable: true);
        return apiResponse(message: "Tag updated successfully", data: TagResource::make($tag));
    }

    public function destroy(Request $request, Tag $tag)
    {
        if (!$tag) {
            return apiResponse(errors: ["id" => ["Tag not found"]], statusCode: symfonyResponse::HTTP_NOT_FOUND);
        }
        $tag->delete();

        logActivity(request: $request, description: "User deleted a tag", showable: true);
        return apiResponse(message: "Tag deleted successfully", statusCode: symfonyResponse::HTTP_NO_CONTENT);
    }
}
