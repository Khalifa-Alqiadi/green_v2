<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserAccountRequest extends FormRequest
{
    protected $errorBag = 'account';

    public function authorize(): bool
    {
        return $this->user()?->can('updateAccount', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions_id' => ['required', 'integer', 'exists:permissions,id'],
            'status' => ['required', 'boolean'],
        ];
    }
}
