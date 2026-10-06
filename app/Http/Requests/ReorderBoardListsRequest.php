<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

class ReorderBoardListsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('board'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'boardLists'         => ['required', 'array'],
            'boardLists.*.id'    => ['required', 'uuid', 'distinct'],
            'boardLists.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
