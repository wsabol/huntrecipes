<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory {
    /**
     * The current password being used by the factory.
     */
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'firebase_uid' => 'test_' . fake()->unique()->uuid(),
            'password' => null,
            'account_status_id' => 1,
            'profile_picture' => '/assets/images/users/generic_avatar.jpg',
            'chef_application_id' => 0,
            'chef_id' => 0,
            'is_chef' => false,
            'is_developer' => false,
            'is_email_verified' => true,
            'date_created' => now(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static {
        return $this->state(fn(array $attributes) => [
            'is_email_verified' => false,
        ]);
    }
}
