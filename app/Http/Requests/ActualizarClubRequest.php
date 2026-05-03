<?php

namespace App\Http\Requests;

use App\Models\Club;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarClubRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        $club = $this->route('club');

        return $user
            && $club instanceof Club
            && $user->rol === 'entrenador'
            && $user->club_id === $club->id
            && $club->admin_user_id === $user->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Club $club */
        $club = $this->route('club');

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('clubs', 'name')->ignore($club->id)],
            'description' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre del club es obligatorio.',
            'name.unique' => 'Este nombre de club ya existe en el sistema.',
            'name.max' => 'El nombre del club no puede exceder 255 caracteres.',
            'description.max' => 'La descripción no puede exceder 1000 caracteres.',
            'logo.image' => 'El archivo debe ser una imagen válida.',
            'logo.mimes' => 'El logo debe ser un archivo JPG o PNG.',
            'logo.max' => 'El logo no puede exceder 2 MB.',
        ];
    }
}
