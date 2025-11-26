<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\BaseRequest;

class UpdatePostRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'feature_image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'status' => ['nullable', 'in:approved,disapproved'],
            'is_published' => ['nullable', 'boolean'],
        ];
    }

    public function messages()
    {
        return [
            'feature_image.image' => 'The file must be an image.',
            'feature_image.mimes' => 'The image must be in jpeg, png, jpg, or gif format.',
            'feature_image.max' => 'The image must not be larger than 2MB.',
        ];
    }
}
