<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use \App\Models\User;
use \App\Models\Post;
use \App\Models\Tag;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $count = [
            "users" => User::count(),
            "posts" => Auth::user()->hasRole('admin')
                ? Post::count()
                : Post::currentUserPost()->count(),
            "tags" => Tag::count(),
        ];

        return Inertia::render('Dashboard', compact('count'));
    }
}
