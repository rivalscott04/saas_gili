<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\ChatMessage;

class ChatApiTest extends AuthenticatedApiTestCase
{
    public function test_it_returns_chat_threads_and_sends_message(): void
    {
        $booking = Booking::factory()->create(['customer_name' => 'Alice']);
        ChatMessage::factory()->create([
            'booking_id' => $booking->id,
            'message' => 'Halo operator',
        ]);

        $this->getJson('/api/v1/chats')
            ->assertOk()
            ->assertJsonPath('data.0.customer_name', 'Alice');

        $response = $this->postJson("/api/v1/chats/{$booking->id}/messages", [
            'message' => 'Siap, kami bantu.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.sender', 'operator')
            ->assertJsonPath('data.source', 'whatsapp');
    }
}
