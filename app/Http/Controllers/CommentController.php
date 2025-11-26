<?php

namespace App\Http\Controllers;

use Symfony\Component\HttpFoundation\Response as symfonyResponse;
use App\Models\Comment;
use App\Models\Post;
use App\Http\Requests\CommentRequest;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(CommentRequest $request, Post $post)
    {
        $data = $request->validated();
        $comment = Comment::create([
            'user_id' => Auth::id(),
            'commentable_id' => $data['commentable_id'],
            'commentable_type' => $data['commentable_type'],
            'parent_comment_id' => $data['parent_comment_id'] ?? null,
            'body' => $data['body'],
        ]);
        $comment->load('user');

        logActivity(request: $request, description: "User added a new comment", showable: true);
        return apiResponse(message: "Comment added successfully", data: CommentResource::make($comment), statusCode: symfonyResponse::HTTP_CREATED);
    }

    public function update(Request $request, Comment $comment)
    {
        $comment->update([
            'user_id' => Auth::id(),
            'body' => $request["body"],
        ]);

        $comment->load('user');

        logActivity(request: $request, description: "User updated a comment", showable: true);
        return apiResponse(message: "Comment updated successfully", data: CommentResource::make($comment));
    }

    public function destroy(Request $request, Comment $comment)
    {
        $comment->delete();

        logActivity(request: $request, description: "User deleted a comment", showable: true);
        return apiResponse(message: "Comment deleted successfully", statusCode: symfonyResponse::HTTP_NO_CONTENT);
    }
}
