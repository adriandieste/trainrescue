<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinClubRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() &&
               auth()->user()->rol === 'entrenador' &&
               auth()->user()->club_id === null;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'club_id' => ['required', 'exists:clubs,id'],
            'message' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'club_id.required' => 'Debes seleccionar un club.',
            'club_id.exists' => 'El club seleccionado no existe.',
            'message.max' => 'El mensaje no puede exceder 500 caracteres.',
        ];
    }
}

