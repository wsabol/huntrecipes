<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class ReviewChefApplicationRequest extends FormRequest {
    protected function prepareForValidation(): void {
        if (in_array($this->input('chef_id'), [0, '0'], true)) {
            $this->merge(['chef_id' => null]);
        }
    }

    public function authorize(): bool {
        return (bool)$this->user()?->is_developer;
    }

    public function rules(): array {
        return [
            'approved' => ['required', 'boolean'],
            'chef_id' => ['nullable', 'integer', 'min:0', 'exists:Chef,id'],
        ];
    }
}
