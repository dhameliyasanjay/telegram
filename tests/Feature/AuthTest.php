<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_is_redirected_to_login_from_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_api_request_returns_401(): void
    {
        $response = $this->getJson('/api/board');
        $response->assertStatus(401);
    }

    public function test_login_page_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Sign In');
    }

    public function test_register_page_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Create Account');
    }

    public function test_user_can_register_with_valid_details(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test Trader',
            'email'                 => 'trader@example.com',
            'password'              => 'secure-password-123',
            'password_confirmation' => 'secure-password-123',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'trader@example.com',
            'name'  => 'Test Trader',
        ]);
    }

    public function test_user_cannot_register_with_invalid_email_or_short_password(): void
    {
        $response = $this->post('/register', [
            'name'                  => 'Test Trader',
            'email'                 => 'not-an-email',
            'password'              => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post('/register', [
            'name'                  => 'Second User',
            'email'                 => 'existing@example.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('secret-pass-88'),
        ]);

        $response = $this->post('/login', [
            'email'    => 'alice@example.com',
            'password' => 'secret-pass-88',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email'    => 'alice@example.com',
            'password' => Hash::make('secret-pass-88'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'email'    => 'alice@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_is_throttled_after_too_many_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email'    => 'bad-actor@example.com',
                'password' => 'wrong-pass',
            ]);
        }

        // 6th attempt should be throttled
        $response = $this->post('/login', [
            'email'    => 'bad-actor@example.com',
            'password' => 'wrong-pass',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertTrue(
            str_contains(
                session('errors')->first('email'),
                'Too many login attempts'
            )
        );
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_can_view_profile_and_update_information(): void
    {
        $user = User::factory()->create([
            'name'  => 'Old Name',
            'email' => 'old@example.com',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'name'  => 'New Name',
            'email' => 'new@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('new@example.com', $user->email);
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password-123'),
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'name'                  => $user->name,
            'email'                 => $user->email,
            'current_password'      => 'old-password-123',
            'new_password'          => 'brand-new-password-123',
            'new_password_confirmation' => 'brand-new-password-123',
        ]);

        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('brand-new-password-123', $user->password));
    }

    public function test_authenticated_user_can_access_dashboard_and_api(): void
    {
        $user = User::factory()->create();

        $dashboardResponse = $this->actingAs($user)->get('/');
        $dashboardResponse->assertStatus(200);

        $apiResponse = $this->actingAs($user)->getJson('/api/board/stats');
        $apiResponse->assertStatus(200);
        $apiResponse->assertJsonStructure([
            'total_groups',
            'total_products',
            'updated_today',
            'with_price',
        ]);
    }
}

