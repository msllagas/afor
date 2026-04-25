<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return \Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name'        => 'sometimes|string|max:65',
            'description' => 'sometimes|nullable|string|max:255',
            'logo'        => 'sometimes|nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ];
    }
}
