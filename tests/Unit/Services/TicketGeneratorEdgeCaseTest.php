<?php

namespace Tests\Unit\Services;

use App\Models\Project;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Repositories\TicketRepository;
use App\Services\AI\AIServiceInterface;
use App\Services\TicketGeneratorService;
use App\Exceptions\TicketGenerationException;
use Mockery;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class TicketGeneratorEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        TicketPriority::firstOrCreate(
            ['name' => 'Medium'],
            ['color' => '#F59E0B']
        );
    }
    
    // Helper to create project with valid status
    private function createProjectWithStatus($attributes = []): Project
    {
        $project = Project::factory()->create($attributes);
        
        $project->ticketStatuses()->create([
            'name' => 'backlog',
            'color' => 'gray', 
            'sort_order' => 0
        ]);
        
        return $project;
    }

    public function test_generates_tickets_with_empty_project_description_and_prompt()
    {
        $project = $this->createProjectWithStatus([
            'name' => 'Simple Project',
            'description' => null
        ]);

        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateTickets')
            ->once()
            ->with('Simple Project', null, null)
            ->andReturn([
                ['title' => 'T1', 'description' => 'D1']
            ]);

        $service = new TicketGeneratorService($mockAIService, new TicketRepository());
        
        $count = $service->generateForProject($project, null);
        
        $this->assertEquals(1, $count);
    }

    public function test_handles_very_long_project_description()
    {
        $longDescription = Str::repeat('A', 5000);
        $project = $this->createProjectWithStatus([
            'name' => 'Long Project',
            'description' => $longDescription
        ]);
        
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateTickets')
            ->once()
            ->with('Long Project', $longDescription, null)
            ->andReturn([
                ['title' => 'T1', 'description' => 'D1']
            ]);

        $service = new TicketGeneratorService($mockAIService, new TicketRepository());
        
        $service->generateForProject($project);
        
        $this->assertTrue(true); // Just asserting no exception was thrown
    }

    public function test_handles_special_characters_in_project_name()
    {
        $specialName = 'Project! @#$%^&*()_+<>?';
        $project = $this->createProjectWithStatus([
            'name' => $specialName,
        ]);
        
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateTickets')
            ->once()
            ->with($specialName, Mockery::any(), Mockery::any())
            ->andReturn([
                ['title' => 'T1', 'description' => 'D1']
            ]);

        $service = new TicketGeneratorService($mockAIService, new TicketRepository());
        
        $service->generateForProject($project);
        
        $this->assertTrue(true);
    }

    public function test_throws_exception_when_ai_returns_malformed_structure()
    {
        $project = $this->createProjectWithStatus();
        
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateTickets')
            ->andReturn([]); 

        $service = new TicketGeneratorService($mockAIService, new TicketRepository());

        $this->expectException(TicketGenerationException::class);
        $this->expectExceptionMessage('Response AI tidak valid');
        
        $service->generateForProject($project);
    }

    public function test_throws_exception_when_ticket_missing_title()
    {
        $project = $this->createProjectWithStatus();
        
        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateTickets')
            ->andReturn([
                ['description' => 'Description only'] // Missing title
            ]);

        $service = new TicketGeneratorService($mockAIService, new TicketRepository());

        $this->expectException(TicketGenerationException::class);
        $this->expectExceptionMessage('Ticket #0 is missing a title');
        
        $service->generateForProject($project);
    }

    public function test_handles_tickets_missing_acceptance_criteria()
    {
        $project = $this->createProjectWithStatus();

        $mockAIService = Mockery::mock(AIServiceInterface::class);
        $mockAIService->shouldReceive('generateTickets')
            ->andReturn([
                ['title' => 'T1', 'description' => 'D1'] // No acceptance criteria
            ]);

        $service = new TicketGeneratorService($mockAIService, new TicketRepository());
        
        $service->generateForProject($project);
        
        $ticket = Ticket::where('project_id', $project->id)->first();
        $this->assertStringContainsString('D1', $ticket->description);
        $this->assertStringNotContainsString('Acceptance Criteria', $ticket->description);
    }
}
