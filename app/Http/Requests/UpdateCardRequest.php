<?php

namespace App\Http\Requests;

use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class UpdateCardRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('card'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name'          => 'sometimes|string|max:255',
            'description'   => 'sometimes|string|max:65535',
            // Cards only move between lists of their own board, never into another workspace.
            'board_list_id' => [
                'sometimes',
                Rule::exists('board_lists', 'id')->where('board_id', $this->route('board_list')->board_id),
            ],
            'order'         => 'sometimes|integer',
        ];
    }
}
