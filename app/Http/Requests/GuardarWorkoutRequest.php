<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
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
        $isTemplate = $this->boolean('is_template');
        return [
            'is_template'    => ['sometimes', 'boolean'],
            'title'          => ['required', 'string', 'max:255'],
            'workout_date'   => $isTemplate ? ['nullable', 'date'] : ['required', 'date'],
            'target_scope'   => ['required', 'in:personal,club'],
            'exercises'      => ['required', 'array', 'min:1'],
            'exercises.*.source'        => ['required', 'in:predefined,custom'],
            'exercises.*.exercise_id'   => ['required', 'integer', 'min:1'],
            'exercises.*.sets'          => ['required', 'integer', 'min:1', 'max:100'],
            'exercises.*.meters'        => ['nullable', 'integer', 'min:1', 'max:50000'],
            'exercises.*.rest_seconds'  => ['nullable', 'integer', 'min:0', 'max:3600'],
        ];
    }
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('target_scope') === 'club' && ! $this->user()?->club_id) {
                $validator->errors()->add('target_scope', 'No puedes guardar sesiones de club sin pertenecer a uno.');
            }
        });
    }
    public function messages(): array
    {
        return [
            'title.required'       => 'El titulo del entrenamiento es obligatorio.',
            'workout_date.required' => 'La fecha del entrenamiento es obligatoria.',
            'exercises.required'   => 'Debes agregar al menos un ejercicio al entrenamiento.',
            'exercises.min'        => 'Debes agregar al menos un ejercicio al entrenamiento.',
        ];
    }
}
