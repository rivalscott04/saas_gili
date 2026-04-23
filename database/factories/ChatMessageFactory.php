<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\ChatMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChatMessage>
 */
class ChatMessageFactory extends Factory
{
    private const ENGLISH_MESSAGES = [
        'Hi, I would like to confirm my booking details.',
        'Could you please share the exact meeting point?',
        'Thank you, that schedule works for us.',
        'We are on our way and should arrive on time.',
        'Can we request hotel pickup for this tour?',
        'Perfect, see you tomorrow morning.',
        'Please let me know if anything changes.',
        'I have completed the payment, thank you.',
    ];

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),
            'sender' => fake()->randomElement(['customer', 'operator']),
            'message' => fake()->randomElement(self::ENGLISH_MESSAGES),
            'source' => fake()->randomElement(['whatsapp', 'web']),
        ];
    }
}
