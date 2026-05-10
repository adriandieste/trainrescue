<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GuardarWorkoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->rol === 'entrenador';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'is_template' => ['required', 'boolean'],
            'workout_date' => [
                'nullable',
                'date',
                Rule::requiredIf(fn (): bool => ! $this->boolean('is_template')),
            ],
            'target_scope' => ['required', 'in:personal,club,grupo'],
            'assigned_user_ids' => [
                'nullable',
                'array',
                Rule::requiredIf(fn (): bool => $this->input('target_scope') === 'grupo'),
            ],
            'assigned_user_ids.*' => ['integer', 'min:1'],
            'exercises' => ['required', 'array', 'min:1'],
            'exercises.*.source' => ['required', 'in:predefined,custom'],
            'exercises.*.exercise_id' => ['required', 'integer', 'min:1'],
            'exercises.*.sets' => ['required', 'integer', 'min:1', 'max:100'],
            'exercises.*.meters' => ['nullable', 'integer', 'min:1', 'max:50000'],
            'exercises.*.rest_seconds' => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $scope = $this->input('target_scope');

            if (in_array($scope, ['club', 'grupo'], true) && ! $this->user()?->club_id) {
                $validator->errors()->add('target_scope', 'No puedes guardar sesiones de club sin pertenecer a uno.');
                return;
            }

            if ($scope === 'grupo') {
                $userIds = array_map('intval', $this->input('assigned_user_ids', []));
                if (empty($userIds)) {
                    $validator->errors()->add('assigned_user_ids', 'Debes seleccionar al menos un atleta para el grupo.');
                    return;
                }

                $validCount = User::where('club_id', $this->user()?->club_id)
                    ->whereIn('id', $userIds)
                    ->count();

                if ($validCount !== count($userIds)) {
                    $validator->errors()->add('assigned_user_ids', 'Algunos atletas seleccionados no pertenecen a tu club.');
                }
            }
        });
    }

    protected function prepareForValidation(): void
    {
        if (! $this->has('is_template')) {
            $this->merge(['is_template' => false]);
        }
        if (! $this->has('assigned_user_ids')) {
            $this->merge(['assigned_user_ids' => []]);
        }
    }

    public function messages(): array
    {
        return [
            'title.required' => 'El titulo del entrenamiento es obligatorio.',
            'workout_date.required' => 'La fecha del entrenamiento es obligatoria.',
            'exercises.required' => 'Debes agregar al menos un ejercicio al entrenamiento.',
            'exercises.min' => 'Debes agregar al menos un ejercicio al entrenamiento.',
            'assigned_user_ids.required' => 'Debes seleccionar al menos un atleta para el grupo.',
        ];
    }
}

