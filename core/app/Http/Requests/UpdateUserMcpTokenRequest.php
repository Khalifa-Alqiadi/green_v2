<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserMcpTokenRequest extends FormRequest
{
    protected $errorBag = 'mcpTokenEdit';

    public function authorize(): bool
    {
        return $this->user()?->can('manageMcp', $this->route('user')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['name' => trim((string) $this->input('name'))]);
    }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:100']];
    }
}
