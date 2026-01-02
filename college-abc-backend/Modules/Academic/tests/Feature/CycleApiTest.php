<?php

namespace Modules\Academic\Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Entities\Cycle;
use Modules\Academic\Entities\Level;
use Modules\Core\Entities\User;
use Laravel\Sanctum\Sanctum;

class CycleApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    /** @test */
    public function it_can_list_cycles()
    {
        Cycle::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/cycles');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => ['id', 'name', 'slug', 'order', 'is_active']
                    ]
                ]);
    }

    /** @test */
    public function it_can_list_cycles_with_levels()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->count(2)->create(['cycle_id' => $cycle->id]);

        $response = $this->getJson('/api/v1/cycles?with_levels=true');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'levels' => [
                                '*' => ['id', 'name', 'code']
                            ]
                        ]
                    ]
                ]);
    }

    /** @test */
    public function it_can_create_a_cycle()
    {
        $data = [
            'name' => 'Lycée',
            'description' => 'Enseignement secondaire second cycle',
            'is_active' => true,
        ];

        $response = $this->postJson('/api/v1/cycles', $data);

        $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => ['id', 'name', 'slug', 'order']
                ])
                ->assertJson([
                    'message' => 'Cycle créé avec succès',
                    'data' => [
                        'name' => 'Lycée',
                    ]
                ]);

        $this->assertDatabaseHas('cycles', ['name' => 'Lycée']);
    }

    /** @test */
    public function it_validates_required_fields_when_creating_cycle()
    {
        $response = $this->postJson('/api/v1/cycles', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_validates_unique_name_when_creating_cycle()
    {
        Cycle::factory()->create(['name' => 'Collège']);

        $response = $this->postJson('/api/v1/cycles', [
            'name' => 'Collège',
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    /** @test */
    public function it_can_show_a_cycle()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->count(2)->create(['cycle_id' => $cycle->id]);

        $response = $this->getJson("/api/v1/cycles/{$cycle->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'levels' => [
                            '*' => ['id', 'name']
                        ]
                    ]
                ])
                ->assertJson([
                    'data' => [
                        'id' => $cycle->id,
                        'name' => $cycle->name,
                    ]
                ]);
    }

    /** @test */
    public function it_can_update_a_cycle()
    {
        $cycle = Cycle::factory()->create(['name' => 'Ancien nom']);

        $response = $this->putJson("/api/v1/cycles/{$cycle->id}", [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Cycle mis à jour avec succès',
                    'data' => [
                        'name' => 'Nouveau nom',
                    ]
                ]);

        $this->assertDatabaseHas('cycles', [
            'id' => $cycle->id,
            'name' => 'Nouveau nom',
        ]);
    }

    /** @test */
    public function it_can_delete_a_cycle()
    {
        $cycle = Cycle::factory()->create();

        $response = $this->deleteJson("/api/v1/cycles/{$cycle->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Cycle supprimé avec succès',
                ]);

        $this->assertSoftDeleted('cycles', ['id' => $cycle->id]);
    }

    /** @test */
    public function it_cannot_delete_cycle_with_levels()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->create(['cycle_id' => $cycle->id]);

        $response = $this->deleteJson("/api/v1/cycles/{$cycle->id}");

        $response->assertStatus(409)
                ->assertJson([
                    'message' => 'Erreur lors de la suppression du cycle',
                ]);

        $this->assertDatabaseHas('cycles', ['id' => $cycle->id, 'deleted_at' => null]);
    }

    /** @test */
    public function it_can_activate_a_cycle()
    {
        $cycle = Cycle::factory()->create(['is_active' => false]);

        $response = $this->postJson("/api/v1/cycles/{$cycle->id}/activate");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Cycle activé avec succès',
                ]);

        $this->assertDatabaseHas('cycles', [
            'id' => $cycle->id,
            'is_active' => true,
        ]);
    }

    /** @test */
    public function it_can_deactivate_a_cycle()
    {
        $cycle = Cycle::factory()->create(['is_active' => true]);

        $response = $this->postJson("/api/v1/cycles/{$cycle->id}/deactivate");

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Cycle désactivé avec succès',
                ]);

        $this->assertDatabaseHas('cycles', [
            'id' => $cycle->id,
            'is_active' => false,
        ]);
    }

    /** @test */
    public function it_can_reorder_cycles()
    {
        $cycle1 = Cycle::factory()->create(['order' => 1]);
        $cycle2 = Cycle::factory()->create(['order' => 2]);

        $response = $this->postJson('/api/v1/cycles/reorder', [
            'orders' => [
                $cycle1->id => 2,
                $cycle2->id => 1,
            ]
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'message' => 'Cycles réorganisés avec succès',
                ]);

        $this->assertDatabaseHas('cycles', ['id' => $cycle1->id, 'order' => 2]);
        $this->assertDatabaseHas('cycles', ['id' => $cycle2->id, 'order' => 1]);
    }

    /** @test */
    public function it_can_get_cycle_statistics()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->count(3)->create(['cycle_id' => $cycle->id]);

        $response = $this->getJson("/api/v1/cycles/{$cycle->id}/statistics");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'levels_count',
                        'class_rooms_count',
                        'fee_types_count',
                        'is_active',
                    ]
                ]);
    }

    /** @test */
    public function it_requires_authentication()
    {
        Sanctum::actingAs(null);

        $response = $this->getJson('/api/v1/cycles');

        $response->assertStatus(401);
    }
}
