<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Testing\File;
use Illuminate\Support\Str;

/**
 * @method User create($attributes = [], ?Model $parent = null)
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'phone' => $this->faker->phoneNumber,
            'email_verified_at' => now(),
            'password' => bcrypt('123456'),
            'remember_token' => Str::random(10),
        ];
    }

    public function asAdmin(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => User::ROLE_ID_ADMIN,
            ];
        });
    }

    public function asOwner(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => User::ROLE_ID_OWNER,
            ];
        });
    }

    public function asClient(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'role_id' => User::ROLE_ID_CLIENT,
            ];
        });
    }

    public function unverified(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
            ];
        });
    }

    public function withPhone(string $phone): self
    {
        return $this->state(function (array $attributes) use ($phone) {
            return [
                'phone' => $phone,
            ];
        });
    }

    public function configure(): self
    {
        return $this->afterCreating(function (User $user) {
            $directory = storage_path('seeds/avatars');
            $files = \Illuminate\Support\Facades\File::files($directory);

            $user->addMedia($files[rand(0,2)])->preservingOriginal()->toMediaCollection('avatars');
        });
    }
}
