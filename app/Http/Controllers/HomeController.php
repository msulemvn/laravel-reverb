<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Post;
use App\Http\Resources\PostResource;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request)
    {
        $posts = Post::query()->showablePost();
        if (request()->routeIs('search') || request()->routeIs('home.search')) {
            $q = $request->q;
            $posts = $posts->whereLike('title', '%' . $q . '%');
        }
        $posts = PostResource::collection($posts->paginate());

        return request()->routeIs('search') || request()->routeIs('home.search') ? response()->json($posts) : Inertia::render('Home', compact('posts'));
    }
}
