<?php

namespace Tests\Feature;

use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RefreshTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_issues_access_and_refresh_tokens(): void
    {
        $user = $this->createUser();

        $response = $this->postJson('/api/v1/login', [
            'username' => $user->user_id,
            'password' => 'password',
            'user_role' => 'student',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'access_token',
                'refresh_token',
                'token_type',
                'expires_in',
            ]);

        $plainTextToken = $response->json('refresh_token');
        $this->assertDatabaseHas('refresh_tokens', [
            'user_id' => $user->user_id,
            'token_hash' => hash('sha256', $plainTextToken),
        ]);
        $this->assertDatabaseMissing('refresh_tokens', [
            'token_hash' => $plainTextToken,
        ]);
    }

    public function test_refresh_token_is_rotated_and_cannot_be_reused(): void
    {
        $user = $this->createUser();
        $login = $this->postJson('/api/v1/login', [
            'username' => $user->user_id,
            'password' => 'password',
            'user_role' => 'student',
        ])->assertOk();
        $oldRefreshToken = $login->json('refresh_token');

        $refresh = $this->postJson('/api/v1/refresh', [
            'refresh_token' => $oldRefreshToken,
        ])->assertOk();

        $this->assertNotSame($oldRefreshToken, $refresh->json('refresh_token'));
        $this->assertNotNull(
            RefreshToken::where('token_hash', hash('sha256', $oldRefreshToken))->value('revoked_at')
        );

        $this->postJson('/api/v1/refresh', [
            'refresh_token' => $oldRefreshToken,
        ])->assertUnauthorized();
    }

    public function test_logout_revokes_refresh_token(): void
    {
        $user = $this->createUser();
        $login = $this->postJson('/api/v1/login', [
            'username' => $user->user_id,
            'password' => 'password',
            'user_role' => 'student',
        ])->assertOk();

        $this->withToken($login->json('access_token'))
            ->postJson('/api/v1/logout', [
                'refresh_token' => $login->json('refresh_token'),
            ])
            ->assertOk();

        $this->postJson('/api/v1/refresh', [
            'refresh_token' => $login->json('refresh_token'),
        ])->assertUnauthorized();
    }

    private function createUser(): User
    {
        return User::query()->create([
            'user_id' => '23211TT1111',
            'name' => 'Test Student',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
        ]);
    }
}
