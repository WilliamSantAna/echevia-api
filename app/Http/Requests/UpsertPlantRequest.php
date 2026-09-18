<?php

namespace App\Http\Requests;

use App\Models\Plant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertPlantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $plant = $this->route('plant');
        $ignoreId = $plant instanceof Plant ? $plant->id : null;

        return [
            'id' => ['nullable', 'string', 'max:36'],
            'name' => ['required', 'string', 'max:255'],
            'species' => ['nullable', 'string', 'max:255'],
            'botanicalFamily' => ['nullable', 'string', 'max:255'],
            'identification' => [
                'required',
                'string',
                'max:64',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('plants', 'identification')->ignore($ignoreId),
            ],
            'notes' => ['nullable', 'string'],
            'favorite' => ['sometimes', 'boolean'],
            'createdAt' => ['nullable', 'date'],
            'updatedAt' => ['nullable', 'date'],
            'photos' => ['present', 'array', 'max:6'],
            'photos.*.id' => ['nullable', 'string', 'max:36'],
            'photos.*.url' => ['required', 'string', 'max:2048'],
            'photos.*.isMain' => ['sometimes', 'boolean'],
            'photos.*.key' => ['nullable', 'string', 'max:512'],
            'videos' => ['present', 'array', 'max:1'],
            'videos.*.id' => ['nullable', 'string', 'max:36'],
            'videos.*.url' => ['required', 'string', 'max:2048'],
            'videos.*.posterUrl' => ['nullable', 'string', 'max:2048'],
            'videos.*.durationSeconds' => ['nullable', 'integer', 'min:0', 'max:30'],
            'videos.*.key' => ['nullable', 'string', 'max:512'],
            'videos.*.posterKey' => ['nullable', 'string', 'max:512'],
        ];
    }
}
