<?php

namespace App\Http\Requests\Tag;

use App\Http\Requests\BaseRequest;

class UpdateTagRequest extends BaseRequest
{
    public function rules()
    {
        return [
            'title' => ['required', 'unique:tags,title,' . $this->user->id,  'string', 'max:255'],
        ];
    }
}
