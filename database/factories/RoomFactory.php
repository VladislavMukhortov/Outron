<?php

namespace Database\Factories;

use App\Models\Hotel;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\File;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->slug(1)),
            'hotel_id' => Hotel::factory(),
            'group_id' => null,
            'description' => $this->faker->text,
            'guest_count' => $this->faker->numberBetween(1, 10),
            'meals_id' => $this->faker->numberBetween(1, 5),
            'price' => $this->faker->randomNumber(5),
            'price_weekend' => $this->faker->randomNumber(5),
        ];
    }

    public function withMeals(int $meals_id): self
    {
        return $this->state(function (array $attributes) use ($meals_id) {
            return [
                'meals_id' => $meals_id,
            ];
        });
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Room $room) {
            $room->group_id = $room->group_id ?? $room->getKey();
            $room->save();

            $directory = storage_path('seeds/rooms');
            $files = File::files($directory);

            $room->addMedia($files[rand(0,9)])->preservingOriginal()->toMediaCollection('preview');
        });
    }
}
