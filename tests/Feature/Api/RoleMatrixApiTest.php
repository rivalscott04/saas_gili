<?php

namespace Tests\Feature\Api;

use App\Models\Booking;
use App\Models\ChatTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleMatrixApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_booking_owned_by_operator(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $admin = User::factory()->create(['role' => 'admin']);
        $booking = Booking::factory()->create(['user_id' => $operator->id, 'status' => 'pending']);

        Sanctum::actingAs($admin);

        $this->getJson("/api/v1/bookings/{$booking->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $booking->id);

        $this->patchJson("/api/v1/bookings/{$booking->id}/status", [
            'status' => 'confirmed',
        ])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed');
    }

    public function test_operator_cannot_modify_system_chat_template(): void
    {
        $template = ChatTemplate::factory()->create(['user_id' => null]);

        Sanctum::actingAs(User::factory()->create(['role' => 'operator']));

        $this->putJson("/api/v1/chat-templates/{$template->id}", [
            'name' => 'Hacked',
            'content' => 'x',
        ])->assertForbidden();

        $this->deleteJson("/api/v1/chat-templates/{$template->id}")
            ->assertForbidden();
    }

    public function test_admin_can_modify_system_chat_template(): void
    {
        $template = ChatTemplate::factory()->create([
            'user_id' => null,
            'name' => 'Sys',
            'content' => 'c',
        ]);

        Sanctum::actingAs(User::factory()->create(['role' => 'admin']));

        $this->putJson("/api/v1/chat-templates/{$template->id}", [
            'name' => 'Sys Updated',
            'content' => 'c2',
        ])->assertOk()->assertJsonPath('data.name', 'Sys Updated');
    }

    public function test_operator_cannot_modify_peer_chat_template(): void
    {
        $peer = User::factory()->create(['role' => 'operator']);
        $template = ChatTemplate::factory()->create(['user_id' => $peer->id]);

        Sanctum::actingAs(User::factory()->create(['role' => 'operator']));

        $this->putJson("/api/v1/chat-templates/{$template->id}", [
            'name' => 'Hacked',
            'content' => 'x',
        ])->assertForbidden();
    }
}
