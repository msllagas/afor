<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only the workspace owner can change its settings; outsiders get a 404.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('workspace'));
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
