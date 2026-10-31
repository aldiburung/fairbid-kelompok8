<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'password_hash' => static::$password ??= Hash::make('password'),
            'no_hp' => fake()->unique()->numerify('08##########'),
            'tipe_akun' => fake()->randomElement(['PERSONAL', 'BISNIS']),
            'peran' => fake()->randomElement(['USER', 'ADMIN']),
            'status_verifikasi' => fake()->randomElement(['BELUM', 'MENUNGGU', 'DISETUJUI', 'DITOLAK']),
            'status_akun' => fake()->randomElement(['AKTIF', 'DITANGGUHKAN', 'DIBLOKIR']),
            'created_at' => now(),
        ];
    }
}
