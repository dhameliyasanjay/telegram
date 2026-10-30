<?php

namespace Tests\Feature;

use App\Models\AdminActivityLog;
use App\Models\GroupSubmission;
use App\Models\TelegramGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_submit_telegram_group_link(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->postJson(route('user.group-submissions.store'), [
            'invite_link' => 'https://t.me/surat_wholesale_hub',
            'notes'       => 'Wholesale textiles & garments in Surat',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Telegram group link submitted successfully! Admin will review and connect it to Price Monitor.',
            ]);

        $this->assertDatabaseHas('group_submissions', [
            'user_id'     => $user->id,
            'invite_link' => 'https://t.me/surat_wholesale_hub',
            'notes'       => 'Wholesale textiles & garments in Surat',
            'status'      => 'pending',
        ]);

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'group_submission_created',
        ]);
    }

    public function test_unauthenticated_user_cannot_submit_group_link(): void
    {
        $response = $this->postJson(route('user.group-submissions.store'), [
            'invite_link' => 'https://t.me/test_hub',
        ]);

        $response->assertStatus(302)->assertRedirect(route('login'));
    }

    public function test_user_cannot_submit_duplicate_pending_link(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        GroupSubmission::create([
            'user_id'     => $user->id,
            'invite_link' => 'https://t.me/already_submitted',
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($user)->postJson(route('user.group-submissions.store'), [
            'invite_link' => 'https://t.me/already_submitted',
        ]);

        $response->assertOk()
            ->assertJson([
                'success'           => true,
                'already_submitted' => true,
            ]);

        $this->assertSame(1, GroupSubmission::where('user_id', $user->id)->count());
    }

    public function test_user_can_view_their_submitted_group_links(): void
    {
        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);

        GroupSubmission::create([
            'user_id'     => $user1->id,
            'invite_link' => 'https://t.me/user1_group',
            'status'      => 'pending',
        ]);

        GroupSubmission::create([
            'user_id'     => $user2->id,
            'invite_link' => 'https://t.me/user2_group',
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($user1)->getJson(route('user.group-submissions.index'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $submissions = $response->json('submissions');
        $this->assertCount(1, $submissions);
        $this->assertSame('https://t.me/user1_group', $submissions[0]['invite_link']);
    }

    public function test_admin_can_view_sent_links_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user  = User::factory()->create(['role' => 'user']);

        GroupSubmission::create([
            'user_id'     => $user->id,
            'invite_link' => 'https://t.me/admin_view_group',
            'status'      => 'pending',
            'notes'       => 'Electronic gadgets wholesale',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.group-submissions.index'));
        $response->assertOk();
        $response->assertSee('Sent Group Links');
        $response->assertSee('https://t.me/admin_view_group');
    }

    public function test_non_admin_cannot_access_admin_sent_links(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->get(route('admin.group-submissions.index'));
        $response->assertRedirect(route('admin.login'));

        $jsonResponse = $this->actingAs($user)->getJson(route('admin.group-submissions.index'));
        $jsonResponse->assertForbidden();
    }

    public function test_admin_can_accept_and_auto_connect_submitted_group(): void
    {
        \Illuminate\Support\Facades\Queue::fake();

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user  = User::factory()->create(['role' => 'user']);

        $submission = GroupSubmission::create([
            'user_id'     => $user->id,
            'invite_link' => 'https://t.me/awesome_wholesale_group',
            'group_title' => 'Awesome Wholesalers',
            'status'      => 'pending',
        ]);

        $this->mock(\App\Services\TelegramClientService::class, function ($mock) {
            $mock->shouldReceive('connectAndFetchInfo')->andReturn([
                'telegram_id'  => 9988776655,
                'title'        => 'Awesome Wholesalers',
                'invite_link'  => 'https://t.me/awesome_wholesale_group',
                'member_count' => 1500,
                'photo_url'    => null,
            ]);
        });

        $response = $this->actingAs($admin)->postJson(route('admin.group-submissions.accept', $submission->id));

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $submission->refresh();
        $this->assertSame('approved', $submission->status);
        $this->assertNotNull($submission->telegram_group_id);
        $this->assertSame($admin->id, $submission->reviewed_by);

        // Telegram group must be created and active
        $this->assertDatabaseHas('telegram_groups', [
            'id'          => $submission->telegram_group_id,
            'invite_link' => 'https://t.me/awesome_wholesale_group',
            'is_active'   => true,
            'telegram_id' => 9988776655,
        ]);

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'group_submission_approved',
        ]);
    }

    public function test_admin_can_reject_group_submission_with_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user  = User::factory()->create(['role' => 'user']);

        $submission = GroupSubmission::create([
            'user_id'     => $user->id,
            'invite_link' => 'https://t.me/crypto_scam_link',
            'status'      => 'pending',
        ]);

        $response = $this->actingAs($admin)->postJson(route('admin.group-submissions.reject', $submission->id), [
            'admin_notes' => 'Not wholesale product related. Spam channel.',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $submission->refresh();
        $this->assertSame('rejected', $submission->status);
        $this->assertSame('Not wholesale product related. Spam channel.', $submission->admin_notes);
        $this->assertSame($admin->id, $submission->reviewed_by);

        $this->assertDatabaseHas('admin_activity_logs', [
            'action' => 'group_submission_rejected',
        ]);
    }

    public function test_admin_can_delete_group_submission(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $submission = GroupSubmission::create([
            'invite_link' => 'https://t.me/delete_me_link',
            'status'      => 'rejected',
        ]);

        $response = $this->actingAs($admin)->deleteJson(route('admin.group-submissions.destroy', $submission->id));

        $response->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('group_submissions', [
            'id' => $submission->id,
        ]);
    }
}
