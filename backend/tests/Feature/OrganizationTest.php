<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_create_organization(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/v1/organizations', [
                'name' => 'Test Organization',
                'description' => 'Test Description',
                'plan' => 'pro',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'plan',
                    'is_active',
                ]
            ]);

        $this->assertDatabaseHas('organizations', [
            'name' => 'Test Organization',
            'slug' => 'test-organization',
        ]);
    }

    public function test_can_list_user_organizations(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/v1/organizations');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_update_organization(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user->id, ['role' => 'admin']);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/organizations/{$org->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('organizations', [
            'id' => $org->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_cannot_update_organization_without_admin_role(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user->id, ['role' => 'member']);

        $response = $this->actingAs($this->user)
            ->putJson("/api/v1/organizations/{$org->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_can_delete_organization_as_owner(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user->id, ['role' => 'owner']);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/organizations/{$org->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('organizations', ['id' => $org->id]);
    }

    public function test_cannot_delete_organization_as_admin(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user->id, ['role' => 'admin']);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/v1/organizations/{$org->id}");

        $response->assertStatus(403);
    }

    public function test_can_add_user_to_organization(): void
    {
        $org = Organization::factory()->create();
        $org->users()->attach($this->user->id, ['role' => 'admin']);
        $newUser = User::factory()->create();

        $response = $this->actingAs($this->user)
            ->postJson("/api/v1/organizations/{$org->id}/users", [
                'user_id' => $newUser->id,
                'role' => 'member',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('organization_user', [
            'organization_id' => $org->id,
            'user_id' => $newUser->id,
            'role' => 'member',
        ]);
    }

    public function test_slug_is_generated_automatically(): void
    {
        $org = Organization::create([
            'name' => 'My Test Company',
            'plan' => 'free',
        ]);

        $this->assertEquals('my-test-company', $org->slug);
    }
}
