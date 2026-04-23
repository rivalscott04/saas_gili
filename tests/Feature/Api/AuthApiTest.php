<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_token_and_user_payload(): void
    {
        $user = User::factory()->create([
            'email' => 'guide@test.local',
            'password' => 'secret123',
            'role' => 'operator',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'guide@test.local',
            'password' => 'secret123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'guide@test.local')
            ->assertJsonPath('data.user.role', 'operator')
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'guide@test.local',
            'password' => 'correct',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'guide@test.local',
            'password' => 'wrong-password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_me_requires_authentication(): void
    {
        $this->getJson('/api/v1/auth/me')->assertUnauthorized();
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = User::factory()->create(['role' => 'admin']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.role', 'admin');
    }

    public function test_me_returns_active_tenant_categories_only(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'role' => 'operator',
        ]);
        $activeCategory = Category::query()->firstWhere('code', 'tour_operator');
        if (! $activeCategory) {
            $activeCategory = Category::query()->create([
                'code' => 'tour_operator',
                'name' => 'Tour Operator',
                'is_active' => true,
            ]);
        }
        $inactiveCategory = Category::query()->create([
            'code' => 'legacy_category',
            'name' => 'Legacy Category',
            'is_active' => false,
        ]);
        $tenant->categories()->sync([
            $activeCategory->id,
            $inactiveCategory->id,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonPath('data.user.tenant_categories.0', 'tour_operator')
            ->assertJsonCount(1, 'data.user.tenant_categories');
    }

    public function test_logout_revokes_current_token(): void
    {
        $user = User::factory()->create();
        $newAccess = $user->createToken('test');
        $plain = $newAccess->plainTextToken;
        $tokenRowId = $newAccess->accessToken->getKey();

        $this->withToken($plain)
            ->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('data.message', 'Logged out');

        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $tokenRowId]);

        // PHPUnit reuses the app; RequestGuard may cache the prior user until guards reset.
        Auth::forgetGuards();

        $this->withToken($plain)
            ->getJson('/api/v1/auth/me')
            ->assertUnauthorized();
    }
}
