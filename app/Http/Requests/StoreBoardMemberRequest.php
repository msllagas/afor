<?php

namespace App\Http\Requests;

use App\Models\Board;
use Illuminate\Auth\Access\Response;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class StoreBoardMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * Only the workspace owner can add people to its boards; board members get a 403 and outsiders a 404.
     */
    public function authorize(): Response
    {
        return Gate::inspect('manageMembers', $this->route('board'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        /** @var Board $board */
        $board = $this->route('board');

        return [
            'user_id' => [
                'bail',
                'required',
                'string',
                Rule::notIn([$board->workspace->owner_id]),
                Rule::exists('workspace_user', 'user_id')->where('workspace_id', $board->workspace_id),
                Rule::unique('board_user', 'user_id')->where('board_id', $board->id),
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => 'Choose someone to add to the board.',
            'user_id.not_in'   => 'The workspace owner is already on every board.',
            'user_id.exists'   => 'Only members of this workspace can be added to its boards.',
            'user_id.unique'   => 'This person is already on the board.',
        ];
    }
}
