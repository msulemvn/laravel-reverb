<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'user_id' => $this->user_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'parent_comment_id' => $this->parent_comment_id,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'replies' => CommentResource::collection($this->whenLoaded('replies')),
        ];
    }
}
