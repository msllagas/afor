<?php

namespace App\Http\Requests;

use App\Enums\BoardListColor;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Enum;

class UpdateBoardListRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): Response
    {
        return Gate::inspect('update', $this->route('board_list'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'is_archived' => 'sometimes|boolean',
            'color'       => ['sometimes', 'nullable', new Enum(BoardListColor::class)],
            'name'        => 'sometimes|string|max:255',
        ];
    }
}
