<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        $name = fake()->name();

        return [
            'uuid' => (string) Str::uuid(),

            'name' => $name,

            // ✅ slug based on final name (works even if seeder overrides name)
            'slug' => function (array $attributes) {
                $base = Str::slug($attributes['name'] ?? 'user');
                return $base . '-' . Str::lower(Str::random(6));
            },

            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
