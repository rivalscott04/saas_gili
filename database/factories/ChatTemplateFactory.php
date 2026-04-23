<?php

namespace Database\Factories;

use App\Models\ChatTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatTemplate>
 */
class ChatTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => fake()->words(3, true),
            'content' => fake()->paragraph(),
        ];
    }
}
