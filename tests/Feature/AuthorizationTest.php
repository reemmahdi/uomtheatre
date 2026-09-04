<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Status;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private array $roles = [];
    private array $statuses = [];

    protected function setUp(): void
    {
        parent::setUp();
        foreach (['super_admin', 'theater_manager', 'event_manager', 'receptionist', 'university_office', 'user'] as $name) {
            $this->roles[$name] = Role::create(['name' => $name, 'display_name' => $name])->id;
        }
        foreach (['draft', 'added', 'active', 'published', 'closed', 'end', 'cancelled'] as $name) {
            $this->statuses[$name] = Status::create(['name' => $name, 'display_name' => $name])->id;
        }
    }

    private function user(string $role): User
    {
        return User::create([
            'name'      => $role,
            'email'     => $role . '@example.test',
            'password'  => Str::random(20),
            'role_id'   => $this->roles[$role],
            'is_active' => true,
        ]);
    }

    private function token(User $user, array $abilities): string
    {
        return $user->createToken('test', $abilities)->plainTextToken;
    }

    private function event(string $status, User $creator): Event
    {
        return Event::create([
            'uuid'           => (string) Str::uuid(),
            'title'          => 'Test ' . $status,
            'start_datetime' => now()->addDays(3),
            'end_datetime'   => now()->addDays(3)->addHours(2),
            'status_id'      => $this->statuses[$status],
            'created_by'     => $creator->id,
        ]);
    }

    public function test_admin_endpoints_require_a_token(): void
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
        $this->postJson('/api/admin/check-in', ['qr_code' => 'x'])->assertStatus(401);
        $this->getJson('/api/me')->assertStatus(401);
    }

    public function test_receptionist_cannot_manage_users_or_escalate(): void
    {
        $receptionist = $this->user('receptionist');
        $token = $this->token($receptionist, ['staff']);

        $this->withToken($token)->getJson('/api/admin/users')->assertStatus(403);
        $this->withToken($token)
            ->putJson('/api/admin/users/' . $receptionist->id, ['role_id' => $this->roles['event_manager']])
            ->assertStatus(403);
    }

    public function test_mobile_ability_cannot_reach_admin_api(): void
    {
        $admin = $this->user('super_admin');
        $this->withToken($this->token($admin, ['mobile']))->getJson('/api/admin/users')->assertStatus(403);
        $this->app['auth']->forgetGuards();
        $this->withToken($this->token($admin, ['staff']))->getJson('/api/admin/users')->assertStatus(200);
    }

    public function test_event_manager_cannot_skip_approval_through_status_api(): void
    {
        $manager = $this->user('event_manager');
        $event = $this->event('draft', $manager);
        $token = $this->token($manager, ['staff']);

        $this->withToken($token)
            ->patchJson('/api/admin/events/' . $event->id . '/status', ['status' => 'active'])
            ->assertStatus(403);
        $this->withToken($token)
            ->patchJson('/api/admin/events/' . $event->id . '/status', ['status' => 'end'])
            ->assertStatus(422);
    }

    public function test_anonymous_visitors_only_see_public_events(): void
    {
        $admin = $this->user('super_admin');
        $draft = $this->event('draft', $admin);
        $published = $this->event('published', $admin);

        $this->getJson('/api/events/' . $draft->id)->assertStatus(404);
        $this->getJson('/api/seats/' . $draft->id)->assertStatus(404);

        $response = $this->getJson('/api/events/' . $published->id)->assertStatus(200);
        $this->assertArrayNotHasKey('created_by', $response->json('event'));
        $this->assertArrayNotHasKey('cancellation_reason', $response->json('event'));
    }

    public function test_seat_map_is_only_served_for_published_events(): void
    {
        $admin = $this->user('super_admin');
        $member = $this->user('user');
        $draft = $this->event('draft', $admin);
        $token = $this->token($member, ['mobile']);

        $this->withToken($token)->getJson('/api/events/' . $draft->id . '/seat-map')->assertStatus(404);
    }

    public function test_removed_routes_stay_removed(): void
    {
        $this->post('/api/register', [])->assertStatus(404);
        $this->get('/logout')->assertStatus(405);
        $this->patchJson('/api/reservations/1/cancel')->assertStatus(404);
    }

    public function test_deactivated_account_loses_its_tokens(): void
    {
        $member = $this->user('user');
        $token = $this->token($member, ['mobile']);

        $this->withToken($token)->getJson('/api/me')->assertStatus(200);
        $member->update(['is_active' => false]);
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_forged_google_token_is_rejected(): void
    {
        $this->postJson('/api/auth/google', ['id_token' => 'eyJhbGciOiJub25lIn0.eyJzdWIiOiIxIn0.'])
            ->assertStatus(401);
    }

    public function test_cors_does_not_reflect_unknown_origins(): void
    {
        $this->getJson('/api/events', ['Origin' => 'https://evil.example'])
            ->assertHeaderMissing('Access-Control-Allow-Origin');
    }
}
