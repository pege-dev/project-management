<?php

namespace App\Services\AI;

use App\Exceptions\AIServiceException;

interface AIServiceInterface
{
    /**
     * Generate tickets based on project context
     *
     * This method takes project information and an optional user prompt,
     * sends a request to the AI service, and returns an array of ticket data
     * that can be used to create Ticket records.
     *
     * @param string $projectName The name of the project
     * @param string|null $projectDescription Optional description of the project
     * @param string|null $userPrompt Optional additional context from the user
     * @return array Array of ticket data, each containing 'title', 'description', and 'acceptance_criteria'
     * @throws AIServiceException When API key is missing, invalid, or API call fails
     */
    public function generateTickets(
        string $projectName,
        ?string $projectDescription = null,
        ?string $userPrompt = null
    ): array;
}
