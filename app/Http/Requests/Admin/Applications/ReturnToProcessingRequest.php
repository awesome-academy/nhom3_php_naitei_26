<?php

namespace App\Http\Requests\Admin\Applications;

use Illuminate\Foundation\Http\FormRequest;

class ReturnToProcessingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'note' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'note.required' => 'Vui lòng nhập lý do trả hồ sơ về xử lý lại.',
        ];
    }
}
