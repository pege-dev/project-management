<?php

namespace App\Services;

use App\Models\Project;
use App\Services\AI\AIServiceInterface;
use App\Repositories\TicketRepository;
use App\Exceptions\TicketGenerationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class TicketGeneratorService
{
    public function __construct(
        private AIServiceInterface $aiService,
        private TicketRepository $ticketRepository
    ) {}

    /**
     * Generate and create tickets for a project
     *
     * @param Project $project
     * @param string|null $prompt
     * @return int Number of tickets created
     * @throws TicketGenerationException
     */
    public function generateForProject(Project $project, ?string $prompt = null): int
    {
        Log::info('Starting ticket generation service for project', [
            'project_id' => $project->id,
            'project_name' => $project->name
        ]);

        try {
            // 1. Call AI service
            $ticketsData = $this->aiService->generateTickets(
                projectName: $project->name,
                projectDescription: $project->description,
                userPrompt: $prompt
            );

            // 2. Validate ticket data
            $validatedTickets = $this->validateTickets($ticketsData);

            // 3. Create tickets in transaction
            return DB::transaction(function () use ($project, $validatedTickets) {
                $count = 0;
                foreach ($validatedTickets as $ticketData) {
                    $description = $ticketData['description'];
                    if (!empty($ticketData['acceptance_criteria']) && is_array($ticketData['acceptance_criteria'])) {
                        $description .= "\n\n**Acceptance Criteria:**\n";
                        foreach ($ticketData['acceptance_criteria'] as $criteria) {
                            $description .= "- {$criteria}\n";
                        }
                    }

                    $this->ticketRepository->create([
                        'project_id' => $project->id,
                        'name' => $ticketData['title'],
                        'description' => $description,
                        'status' => 'backlog',
                        'priority' => 'medium',
                    ]);
                    $count++;
                }
                
                Log::info('Successfully created tickets for project', [
                    'project_id' => $project->id,
                    'count' => $count
                ]);
                
                return $count;
            });
            
        } catch (Exception $e) {
            Log::error('Ticket generation failed', [
                'project_id' => $project->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Validate generated ticket data
     *
     * @param array $ticketsData
     * @return array
     * @throws TicketGenerationException
     */
    private function validateTickets(array $ticketsData): array
    {
        $validated = [];
        
        foreach ($ticketsData as $index => $ticket) {
            if (empty($ticket['title'])) {
                throw TicketGenerationException::invalidTicketData("Ticket #{$index} is missing a title");
            }
            
            if (empty($ticket['description'])) {
                throw TicketGenerationException::invalidTicketData("Ticket #{$index} is missing a description");
            }
            
            $validated[] = $ticket;
        }
        
        if (empty($validated)) {
            throw TicketGenerationException::malformedResponse();
        }
        
        return $validated;
    }
}
