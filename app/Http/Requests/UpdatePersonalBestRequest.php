<?php

namespace App\Http\Requests;

use App\Support\ChronoTime;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePersonalBestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'time' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail) {
                    if (!is_string($value) || !ChronoTime::isValid($value)) {
                        $fail('El tiempo debe seguir el formato MM:SS.mm.');
                    }
                },
            ],
        ];
    }
}

