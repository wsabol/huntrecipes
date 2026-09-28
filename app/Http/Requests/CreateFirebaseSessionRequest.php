<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFirebaseSessionRequest extends FormRequest {
    public function authorize(): bool {
        return true;
    }

    public function rules(): array {
        return ['id_token' => ['required', 'string', 'max:8192'], 'remember' => ['sometimes', 'boolean']];
    }
}
