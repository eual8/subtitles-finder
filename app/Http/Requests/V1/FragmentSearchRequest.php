<?php

namespace App\Http\Requests\V1;

use App\Http\Requests\FailedValidationJsonResponseTrait;
use Illuminate\Foundation\Http\FormRequest;

class FragmentSearchRequest extends FormRequest
{
    use FailedValidationJsonResponseTrait;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2'],
            'videoId' => ['nullable', 'integer'],
            'playlistId' => ['nullable', 'integer'],
            'page' => ['nullable', 'integer'],
            'perPage' => ['nullable', 'integer'],
        ];
    }
}
