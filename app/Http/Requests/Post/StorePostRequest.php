<?php

namespace App\Http\Requests\Post;

use App\Http\Requests\BaseRequest;

class StorePostRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'feature_image' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'tags' => 'nullable',
            'tags.*' => 'nullable|integer|exists:tags,id',
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title is required.',
            'description.required' => 'The description is required.',
            'content.required' => 'The content is required.',
            'feature_image.image' => 'The file must be an image.',
            'feature_image.mimes' => 'The image must be in jpeg, png, jpg, or gif format.',
            'feature_image.max' => 'The image must not be larger than 2MB.',
        ];
    }
}
