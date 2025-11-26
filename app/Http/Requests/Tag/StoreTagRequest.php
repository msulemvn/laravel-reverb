<?php

namespace App\Http\Requests\Tag;

use App\Http\Requests\BaseRequest;

class StoreTagRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'title' => ['required', 'unique:tags,title', 'string', 'max:255'],
        ];
    }

    public function messages()
    {
        return [
            'title.required' => 'The title is required.',
            'title.unique' => 'The title must be unique.',
        ];
    }
}
