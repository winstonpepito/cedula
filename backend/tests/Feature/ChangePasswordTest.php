<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ChangePasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_change_own_password(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->putJson('/api/password', [
                'current_password' => 'old-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Password updated.');

        $this->assertTrue(Hash::check('new-password123', $user->fresh()->password));
    }

    public function test_change_password_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create([
            'role' => 'delivery',
            'password' => 'old-password',
        ]);

        $this->actingAs($user)
            ->putJson('/api/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password123',
                'password_confirmation' => 'new-password123',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['current_password']);

        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));
    }

    public function test_guest_cannot_change_password(): void
    {
        $this->putJson('/api/password', [
            'current_password' => 'old-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertUnauthorized();
    }
}
