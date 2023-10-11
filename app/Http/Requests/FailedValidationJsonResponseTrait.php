<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

trait FailedValidationJsonResponseTrait
{
    protected function failedValidation(Validator $validator)
    {
        $messages = $validator->errors()->all();
        $response = [
            'message' => array_shift($messages),
            'errors' => $validator->errors(),
        ];

        throw new HttpResponseException(response()->json($response, 422));
    }
}
