<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource {
    public function toArray(Request $request): array {
        return [
            'id' => $this->id, 'name' => $this->name, 'email' => $this->email,
            'profile_picture' => $this->profile_picture, 'is_chef' => (bool)$this->is_chef,
            'is_developer' => (bool)$this->is_developer, 'is_email_verified' => (bool)$this->is_email_verified,
            'date_created' => $this->date_created,
        ];
    }
}
