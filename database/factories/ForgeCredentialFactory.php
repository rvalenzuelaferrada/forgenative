<?php

namespace Database\Factories;

use App\Models\ForgeCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ForgeCredential>
 */
class ForgeCredentialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $token = fake()->sha256();

        return [
            'name' => fake()->unique()->email(),
            'encrypted_token' => 'test:'.base64_encode($token),
            'token_fingerprint' => hash('sha256', $token),
            'forge_user_id' => fake()->numberBetween(1, 100000),
            'forge_email' => fake()->safeEmail(),
            'last_verified_at' => now(),
        ];
    }
}
