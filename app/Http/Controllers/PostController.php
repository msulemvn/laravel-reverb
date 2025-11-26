<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Post;
use App\Models\Tag;
use App\Http\Resources\PostResource;
use App\Http\Resources\TagResource;
use Symfony\Component\HttpFoundation\Response as symfonyResponse;
use App\Http\Requests\Post\StorePostRequest;
use App\Http\Requests\Post\UpdatePostRequest;
use App\Contracts\PictureServiceInterface;
use Illuminate\Support\Facades\Auth;

class PostController extends Controller
{

    public function __construct(protected PictureServiceInterface $pictureService)
    {
        $this->pictureService = $pictureService;
    }

    public function index()
    {
        if (Auth::check() && request()->routeIs('posts.index')) {
            $query = Post::query()->with(['tags', 'comments.user', 'comments.replies']);

            if (!Auth::user()->hasRole('admin')) {
                $query->currentUserPost();
            }

            $posts = $query->latest()->paginate();

            return Inertia::render('Posts', [
                'posts' => PostResource::collection($posts),
                'tags' => TagResource::collection(Tag::all()),
            ]);
        } else {
            $posts = Post::query()->showablePost()->paginate();

            return Inertia::render('Posts', [
                'posts' => PostResource::collection($posts),
            ]);
        }
    }

    public function store(StorePostRequest $request)
    {
        $newFileName = '';
        if ($request->hasFile('feature_image')) {
            $newFileName = $this->pictureService->upload(
                $request->file('feature_image'),
                $request->file('feature_image')->getClientOriginalName()
            );
        }

        $data = $request->validated();
        $data['feature_image'] = $newFileName ?? "";
        $post = Post::create($data);
        if ($request->has('tags')) {
            $data['tags'] = json_decode($data['tags']);
            $post->tags()->sync($data['tags']);
        }
        $post->refresh();

        logActivity(request: $request, description: "User created a new post", showable: true);
        return apiResponse(message: "Post added successfully", data: PostResource::make($post), statusCode: symfonyResponse::HTTP_CREATED);
    }

    public function show(Post $post): Response
    {
        if (Auth::check() && request()->routeIs('posts.show')) {

            return Inertia::render("posts/show", [
                "post" => PostResource::make($post->load(['tags', 'comments.user', 'comments.replies', 'author:id,name'])),
            ]);
        } else {
            if (!$post->is_published || $post->status !== 'approved') {
                abort(404);
            }

            return Inertia::render('posts/show', [
                'post' => PostResource::make($post->load(['tags', 'comments.user', 'comments.replies', 'author:id,name'])),
            ]);
        }
    }

    public function update(UpdatePostRequest $request, Post $post)
    {
        $post->fill($request->validated());
        $newFileName = '';

        if ($request->hasFile('feature_image')) {
            $newFileName = $this->pictureService->upload(
                $request->file('feature_image'),
                $request->file('feature_image')->getClientOriginalName()
            );
            $post->feature_image = $newFileName;
        }

        if ($request->has('tags')) {
            $request['tags'] = json_decode($request['tags']);
        }

        if ($request->has('status')) {
            $post->status = $request['status'];
        }

        if ($request->has('is_published')) {
            $post->is_published = $request['is_published'];
        }

        $post->save();
        $post->tags()->sync($request->tags);

        logActivity(request: $request, description: "User updated a post", showable: true);
        return apiResponse(message: "Post updated successfully", data: PostResource::make($post));
    }

    public function destroy(Request $request, Post $post)
    {
        $post->delete();
        $post->tags()->sync([]);

        logActivity(request: $request, description: "User deleted a post", showable: true);
        return apiResponse(message: "Post deleted successfully", statusCode: symfonyResponse::HTTP_NO_CONTENT);
    }
}
