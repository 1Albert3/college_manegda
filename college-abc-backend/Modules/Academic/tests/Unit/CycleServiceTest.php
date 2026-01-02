<?php

namespace Modules\Academic\Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Academic\Services\CycleService;
use Modules\Academic\Entities\Cycle;
use Modules\Academic\Entities\Level;

class CycleServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CycleService $cycleService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cycleService = app(CycleService::class);
    }

    /** @test */
    public function it_can_create_a_cycle()
    {
        $data = [
            'name' => 'Collège',
            'description' => 'Enseignement secondaire premier cycle',
            'is_active' => true,
        ];

        $cycle = $this->cycleService->createCycle($data);

        $this->assertInstanceOf(Cycle::class, $cycle);
        $this->assertEquals('Collège', $cycle->name);
        $this->assertTrue($cycle->is_active);
        $this->assertNotNull($cycle->slug);
        $this->assertDatabaseHas('cycles', ['id' => $cycle->id]);
    }

    /** @test */
    public function it_generates_slug_automatically()
    {
        $cycle = $this->cycleService->createCycle([
            'name' => 'Enseignement Primaire',
        ]);

        $this->assertEquals('enseignement-primaire', $cycle->slug);
    }

    /** @test */
    public function it_generates_order_automatically()
    {
        $cycle1 = $this->cycleService->createCycle(['name' => 'Primaire']);
        $cycle2 = $this->cycleService->createCycle(['name' => 'Collège']);

        $this->assertEquals(1, $cycle1->order);
        $this->assertEquals(2, $cycle2->order);
    }

    /** @test */
    public function it_can_update_cycle()
    {
        $cycle = Cycle::factory()->create(['name' => 'Ancien nom']);

        $updated = $this->cycleService->updateCycle($cycle, [
            'name' => 'Nouveau nom',
            'description' => 'Nouvelle description',
        ]);

        $this->assertEquals('Nouveau nom', $updated->name);
        $this->assertEquals('Nouvelle description', $updated->description);
    }

    /** @test */
    public function it_can_activate_and_deactivate_cycle()
    {
        $cycle = Cycle::factory()->create(['is_active' => false]);

        $activated = $this->cycleService->activateCycle($cycle);
        $this->assertTrue($activated->is_active);

        $deactivated = $this->cycleService->deactivateCycle($activated);
        $this->assertFalse($deactivated->is_active);
    }

    /** @test */
    public function it_can_reorder_cycles()
    {
        $cycle1 = Cycle::factory()->create(['order' => 1]);
        $cycle2 = Cycle::factory()->create(['order' => 2]);
        $cycle3 = Cycle::factory()->create(['order' => 3]);

        $orders = [
            $cycle1->id => 3,
            $cycle2->id => 1,
            $cycle3->id => 2,
        ];

        $result = $this->cycleService->reorderCycles($orders);

        $this->assertTrue($result);
        
        $cycle1->refresh();
        $cycle2->refresh();
        $cycle3->refresh();

        $this->assertEquals(3, $cycle1->order);
        $this->assertEquals(1, $cycle2->order);
        $this->assertEquals(2, $cycle3->order);
    }

    /** @test */
    public function it_can_get_all_cycles_with_levels()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->count(3)->create(['cycle_id' => $cycle->id]);

        $cycles = $this->cycleService->getAllCyclesWithLevels();

        $this->assertCount(1, $cycles);
        $this->assertCount(3, $cycles->first()->levels);
    }

    /** @test */
    public function it_can_get_cycle_statistics()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->count(5)->create(['cycle_id' => $cycle->id]);

        $stats = $this->cycleService->getCycleStatistics($cycle);

        $this->assertArrayHasKey('levels_count', $stats);
        $this->assertEquals(5, $stats['levels_count']);
    }

    /** @test */
    public function it_cannot_delete_cycle_with_levels()
    {
        $cycle = Cycle::factory()->create();
        Level::factory()->create(['cycle_id' => $cycle->id]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Impossible de supprimer un cycle qui contient des niveaux');

        $this->cycleService->deleteCycle($cycle);
    }

    /** @test */
    public function it_can_delete_empty_cycle()
    {
        $cycle = Cycle::factory()->create();

        $result = $this->cycleService->deleteCycle($cycle);

        $this->assertTrue($result);
        $this->assertSoftDeleted('cycles', ['id' => $cycle->id]);
    }
}
