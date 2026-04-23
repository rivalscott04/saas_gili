<?php

namespace Tests\Feature\Api;

class ChatTemplateApiTest extends AuthenticatedApiTestCase
{
    public function test_it_returns_frontend_default_templates_when_empty(): void
    {
        $response = $this->getJson('/api/v1/chat-templates');
        $names = collect($response->json('data'))->pluck('name')->all();

        $response->assertOk()
            ->assertJsonCount(3, 'data');

        $this->assertContains('Booking Reminder', $names);
        $this->assertContains('Thank You', $names);
        $this->assertContains('Payment Request', $names);
    }

    public function test_it_runs_chat_template_crud_flow(): void
    {
        $create = $this->postJson('/api/v1/chat-templates', [
            'name' => 'Greeting',
            'content' => 'Halo {{customer_name}}',
        ]);

        $create->assertCreated()->assertJsonPath('data.name', 'Greeting');
        $templateId = $create->json('data.id');

        $this->getJson('/api/v1/chat-templates')
            ->assertOk()
            ->assertJsonPath('data.0.id', $templateId);

        $this->putJson("/api/v1/chat-templates/{$templateId}", [
            'name' => 'Updated Greeting',
            'content' => 'Halo Kak {{customer_name}}',
        ])->assertOk()->assertJsonPath('data.name', 'Updated Greeting');

        $this->deleteJson("/api/v1/chat-templates/{$templateId}")
            ->assertOk();

        $this->assertDatabaseMissing('chat_templates', ['id' => $templateId]);
    }
}
