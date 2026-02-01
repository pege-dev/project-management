<?php

use App\Repositories\TicketRepository;
use App\Models\Ticket;
use App\Models\Project;
use App\Models\TicketStatus;
use App\Models\TicketPriority;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Unit tests for TicketRepository
 * 
 * Tests cover:
 * - Ticket creation with default status and priority
 * - Ticket creation with explicit status and priority
 * - Finding tickets by project
 * 
 * Requirements: 5.1, 6.1, 6.2, 6.3
 */

beforeEach(function () {
    $this->repository = new TicketRepository();

    // Create a test project
    $this->project = Project::factory()->create([
        'name' => 'Test Project',
        'description' => 'Test project description',
    ]);

    // Create default ticket status for the project
    $this->backlogStatus = TicketStatus::create([
        'project_id' => $this->project->id,
        'name' => 'backlog',
        'sort_order' => 1,
        'color' => '#gray',
        'is_completed' => false,
    ]);

    // Create or find default ticket priority
    $this->mediumPriority = TicketPriority::where('name', 'Medium')->first();
});

describe('Ticket Creation', function () {


    test('creates ticket with all required fields', function () {
        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket)->toBeInstanceOf(Ticket::class)
            ->and($ticket->name)->toBe('Test Ticket')
            ->and($ticket->description)->toBe('Test ticket description')
            ->and($ticket->project_id)->toBe($this->project->id)
            ->and($ticket->exists)->toBeTrue();
    });

    test('creates ticket with default backlog status when not provided', function () {
        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->ticket_status_id)->toBe($this->backlogStatus->id)
            ->and($ticket->status->name)->toBe('backlog');
    });

    test('creates ticket with default medium priority when not provided', function () {
        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->priority_id)->toBe($this->mediumPriority->id)
            ->and($ticket->priority->name)->toBe('Medium');
    });

    test('creates ticket with explicit status when provided', function () {

        $customStatus = TicketStatus::create([
            'project_id' => $this->project->id,
            'name' => 'in_progress',
            'sort_order' => 2,
            'color' => '#blue',
            'is_completed' => false,
        ]);

        $ticketData = [
            'project_id' => $this->project->id,
            'ticket_status_id' => $customStatus->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->ticket_status_id)->toBe($customStatus->id)
            ->and($ticket->status->name)->toBe('in_progress');
    });

    test('creates ticket with explicit priority when provided', function () {
        $highPriority = TicketPriority::firstOrCreate(
            ['name' => 'High'],
            ['color' => '#red']
        );

        $ticketData = [
            'project_id' => $this->project->id,
            'priority_id' => $highPriority->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->priority_id)->toBe($highPriority->id)
            ->and($ticket->priority->name)->toBe('High');
    });


    test('creates ticket with null epic_id by default', function () {
        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->epic_id)->toBeNull();
    });

    test('creates ticket with null dates by default', function () {
        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->start_date)->toBeNull()
            ->and($ticket->due_date)->toBeNull();
    });

    test('throws exception when creating ticket without backlog status when it does not exist', function () {
        // Delete the backlog status
        $this->backlogStatus->delete();

        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        // Should throw exception because ticket_status_id is required
        expect(fn() => $this->repository->create($ticketData))
            ->toThrow(\Illuminate\Database\QueryException::class);
    });


    test('creates ticket without medium priority when it does not exist', function () {
        // Delete the medium priority
        $this->mediumPriority->delete();

        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
        ];

        $ticket = $this->repository->create($ticketData);

        // Should still create the ticket, but without priority
        expect($ticket)->toBeInstanceOf(Ticket::class)
            ->and($ticket->priority_id)->toBeNull();
    });

    test('creates ticket with additional optional fields', function () {
        $ticketData = [
            'project_id' => $this->project->id,
            'name' => 'Test Ticket',
            'description' => 'Test ticket description',
            'start_date' => '2024-01-01',
            'due_date' => '2024-01-31',
        ];

        $ticket = $this->repository->create($ticketData);

        expect($ticket->start_date)->not->toBeNull()
            ->and($ticket->due_date)->not->toBeNull();
    });
});

describe('Find Tickets by Project', function () {

    test('returns empty collection when project has no tickets', function () {
        $tickets = $this->repository->findByProject($this->project->id);

        expect($tickets)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
            ->and($tickets)->toHaveCount(0);
    });

    test('returns all tickets for a specific project', function () {
        // Create multiple tickets for the project
        Ticket::factory()->count(3)->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->backlogStatus->id,
            'priority_id' => $this->mediumPriority->id,
        ]);

        $tickets = $this->repository->findByProject($this->project->id);

        expect($tickets)->toHaveCount(3)
            ->and($tickets->first())->toBeInstanceOf(Ticket::class);
    });

    test('returns only tickets for the specified project', function () {
        // Create another project with tickets
        $otherProject = Project::factory()->create();
        $otherStatus = TicketStatus::create([
            'project_id' => $otherProject->id,
            'name' => 'backlog',
            'sort_order' => 1,
            'color' => '#gray',
            'is_completed' => false,
        ]);

        // Create tickets for both projects
        Ticket::factory()->count(2)->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->backlogStatus->id,
            'priority_id' => $this->mediumPriority->id,
        ]);

        Ticket::factory()->count(3)->create([
            'project_id' => $otherProject->id,
            'ticket_status_id' => $otherStatus->id,
            'priority_id' => $this->mediumPriority->id,
        ]);

        $tickets = $this->repository->findByProject($this->project->id);

        expect($tickets)->toHaveCount(2)
            ->and($tickets->every(fn($ticket) => $ticket->project_id === $this->project->id))->toBeTrue();
    });

    test('returns tickets with all relationships loaded', function () {
        Ticket::factory()->create([
            'project_id' => $this->project->id,
            'ticket_status_id' => $this->backlogStatus->id,
            'priority_id' => $this->mediumPriority->id,
        ]);

        $tickets = $this->repository->findByProject($this->project->id);

        expect($tickets->first()->project)->toBeInstanceOf(Project::class)
            ->and($tickets->first()->status)->toBeInstanceOf(TicketStatus::class)
            ->and($tickets->first()->priority)->toBeInstanceOf(TicketPriority::class);
    });
});
