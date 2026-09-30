<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RevokeUserMcpTokenRequest extends FormRequest
{
    protected $errorBag = 'mcp';

    public function authorize(): bool
    {
        return $this->user()?->can('manageMcp', $this->route('user')) ?? false;
    }

    public function rules(): array
    {
        return [];
    }
}
