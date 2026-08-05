<?php

namespace Database\Factories;

use App\Enums\IdType;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = Str::title(fake()->unique()->words(2, true));

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'id_requirement' => IdType::None,
            'is_active' => true,
            'sort_order' => fake()->numberBetween(1, 20),
            'icon' => null,
        ];
    }

    /**
     * Kategori yang belum dibuka untuk publik (mis. Internasional).
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
