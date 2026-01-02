<?php

namespace Modules\Grade\tests\Unit;

use Tests\TestCase;
use Modules\Grade\Services\ReportCardService;
use Modules\Grade\Repositories\GradeRepository;
use Modules\Grade\Entities\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;

class ReportCardServiceTest extends TestCase
{
    public function test_rank_assignment_logic()
    {
        // Fix: Pass Grade model dependency
        $service = new ReportCardService(new GradeRepository(new Grade()));
        
        $method = new \ReflectionMethod(ReportCardService::class, 'assignRanks');
        $method->setAccessible(true);
        
        // Data: 3 students with scores: 15, 15, 12, 10
        // Expected ranks: 1, 1, 3, 4
        $input = collect([
            ['student_id' => 1, 'general_average' => 15],
            ['student_id' => 2, 'general_average' => 15],
            ['student_id' => 3, 'general_average' => 12],
            ['student_id' => 4, 'general_average' => 10],
        ]);
        
        $result = $method->invoke($service, $input);
        
        $this->assertEquals(1, $result[0]['rank']);
        $this->assertEquals(1, $result[1]['rank']); 
        $this->assertEquals(3, $result[2]['rank']); 
        $this->assertEquals(4, $result[3]['rank']);
    }
}
