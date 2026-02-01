<?php

namespace App\Services\AI;

use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;

/**
 * OpenAI Service Implementation
 * 
 * This service handles communication with the OpenAI API to generate
 * ticket content based on project context. It constructs structured prompts,
 * makes HTTP requests, and parses responses into usable ticket data.
 * 
 * Requirements: 2.3, 2.4, 3.1, 3.2, 3.3, 3.5, 4.1, 4.2, 7.1, 7.2, 7.3, 7.4, 10.1, 10.2, 10.3, 10.5
 */
class OpenAIService implements AIServiceInterface
{
    private string $apiKey;
    private string $model;
    private int $maxTokens;
    private int $timeout = 30; // 30 seconds timeout (Requirement 8.4)
    private const API_ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const MAX_CONTEXT_CHARS = 8000; // ~2000 tokens * 4 chars/token (Requirement 3.5)

    /**
     * Constructor - Initialize service with API configuration
     * 
     * Validates that the API key is configured and has the correct format.
     * 
     * @throws AIServiceException If API key is missing or invalid
     * Requirements: 2.3, 2.4
     */
    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model', 'gpt-4o-mini');
        $this->maxTokens = config('services.openai.max_tokens', 2000);

        // Requirement 2.3: Validate API key is configured
        if (empty($apiKey)) {
            throw AIServiceException::missingApiKey();
        }

        $this->apiKey = $apiKey;

        // Requirement 2.4: Validate API key format
        if (!$this->isValidApiKeyFormat($this->apiKey)) {
            throw AIServiceException::invalidApiKey();
        }
    }

    /**
     * Generate tickets based on project context
     * 
     * This is the main public method that orchestrates the ticket generation process.
     * It builds a prompt, calls the OpenAI API, and parses the response.
     * 
     * @param string $projectName The name of the project
     * @param string|null $projectDescription Optional description of the project
     * @param string|null $userPrompt Optional additional context from the user
     * @return array Array of ticket data
     * @throws AIServiceException When API call fails
     * Requirements: 3.1, 3.2, 3.3, 4.1, 4.2
     */
    public function generateTickets(
        string $projectName,
        ?string $projectDescription = null,
        ?string $userPrompt = null
    ): array {
        Log::info('Starting ticket generation', [
            'project_name' => $projectName,
            'has_description' => !empty($projectDescription),
            'has_user_prompt' => !empty($userPrompt),
        ]);

        try {
            // Requirement 3.1, 3.2: Build structured prompt with context
            $prompt = $this->buildPrompt($projectName, $projectDescription, $userPrompt);

            // Requirement 3.3: Send context to AI service
            $response = $this->callOpenAI($prompt);

            // Requirement 4.1, 4.2: Parse response into ticket data
            $tickets = $this->parseResponse($response);

            Log::info('Ticket generation successful', [
                'ticket_count' => count($tickets),
            ]);

            return $tickets;
        } catch (AIServiceException $e) {
            // Re-throw AI service exceptions
            Log::error('AI service error during ticket generation', [
                'error' => $e->getMessage(),
                'project_name' => $projectName,
            ]);
            throw $e;
        } catch (\Exception $e) {
            // Catch any other unexpected errors
            Log::error('Unexpected error during ticket generation', [
                'error' => $e->getMessage(),
                'project_name' => $projectName,
            ]);
            throw AIServiceException::networkError();
        }
    }

    /**
     * Build structured prompt for OpenAI
     * 
     * Constructs a detailed prompt that includes:
     * - Project context (name and description)
     * - User's additional prompt (if provided)
     * - Instructions for JSON format
     * - Examples of good ticket structure
     * - Acceptance criteria format guidelines
     * 
     * @param string $projectName
     * @param string|null $projectDescription
     * @param string|null $userPrompt
     * @return string The constructed prompt
     * Requirements: 3.1, 3.2, 3.5, 10.1, 10.2, 10.3, 10.5
     */
    private function buildPrompt(
        string $projectName,
        ?string $projectDescription,
        ?string $userPrompt
    ): string {
        // Requirement 3.1, 3.2: Include project context
        $context = "Project Name: {$projectName}\n";

        if (!empty($projectDescription)) {
            // Requirement 3.5: Truncate description if too long
            $truncatedDescription = $this->truncateText($projectDescription, 1000);
            $context .= "Project Description: {$truncatedDescription}\n";
        }

        if (!empty($userPrompt)) {
            // Requirement 3.5: Truncate user prompt if too long
            $truncatedPrompt = $this->truncateText($userPrompt, 500);
            $context .= "Additional Context: {$truncatedPrompt}\n";
        }

        // Requirement 10.1, 10.2, 10.3, 10.5: Structured prompt with format instructions
        $prompt = <<<PROMPT
You are a project management assistant helping to generate initial tickets for a new project.

{$context}

Please generate 3-5 related tickets that would be good starting points for this project. Each ticket should be actionable and well-defined.

Requirements:
1. Generate between 3 and 5 tickets
2. Each ticket must have a clear, concise title
3. Each ticket must have a detailed description explaining what needs to be done
4. Each ticket must include acceptance criteria as an array of specific, testable conditions

Format your response as a JSON object with this exact structure:
{
    "tickets": [
        {
            "title": "Clear, actionable ticket title",
            "description": "Detailed description of what needs to be done, why it's important, and any relevant context",
            "acceptance_criteria": [
                "Specific, testable condition 1",
                "Specific, testable condition 2",
                "Specific, testable condition 3"
            ]
        }
    ]
}

Example of a good ticket:
{
    "title": "Setup project database schema",
    "description": "Create the initial database schema for the project including all necessary tables, relationships, and indexes. This forms the foundation for data storage and retrieval.",
    "acceptance_criteria": [
        "All required tables are created with proper column types",
        "Foreign key relationships are established correctly",
        "Indexes are added for frequently queried columns",
        "Migration files are created and tested"
    ]
}

Important guidelines:
- Titles should be concise (5-10 words) and start with an action verb
- Descriptions should be clear and provide enough context for someone to understand the task
- Acceptance criteria should be specific, measurable, and testable
- Focus on foundational tasks that make sense for a new project
- Ensure tickets are related and build upon each other logically

Return ONLY the JSON object, no additional text or explanation.
PROMPT;

        // Requirement 3.5: Ensure total prompt doesn't exceed max context size
        if (strlen($prompt) > self::MAX_CONTEXT_CHARS) {
            Log::warning('Prompt exceeds max context size, truncating', [
                'original_length' => strlen($prompt),
                'max_length' => self::MAX_CONTEXT_CHARS,
            ]);
            $prompt = substr($prompt, 0, self::MAX_CONTEXT_CHARS);
        }

        return $prompt;
    }

    /**
     * Make HTTP request to OpenAI API
     * 
     * Sends the constructed prompt to OpenAI's chat completions endpoint
     * and handles various error scenarios including network failures,
     * rate limits, and timeouts.
     * 
     * @param string $prompt The prompt to send
     * @return string The raw response content from OpenAI
     * @throws AIServiceException On API errors
     * Requirements: 7.1, 7.2, 7.3, 7.4
     */
    private function callOpenAI(string $prompt): string
    {
        Log::info('Calling OpenAI API', [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'prompt_length' => strlen($prompt),
        ]);

        try {
            // Requirement 7.4: 30-second timeout
            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::API_ENDPOINT, [
                    'model' => $this->model,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt,
                        ],
                    ],
                    'max_tokens' => $this->maxTokens,
                    'temperature' => 0.7,
                ]);

            // Requirement 7.3: Handle rate limit errors
            if ($response->status() === 429) {
                Log::warning('OpenAI rate limit exceeded');
                throw AIServiceException::rateLimitExceeded();
            }

            // Requirement 7.2: Handle invalid API key
            if ($response->status() === 401) {
                Log::error('OpenAI API key is invalid');
                throw AIServiceException::invalidApiKey();
            }

            // Handle other HTTP errors
            if (!$response->successful()) {
                Log::error('OpenAI API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw AIServiceException::networkError();
            }

            $data = $response->json();

            // Extract the content from the response
            if (!isset($data['choices'][0]['message']['content'])) {
                Log::error('Unexpected OpenAI response structure', [
                    'response' => $data,
                ]);
                throw AIServiceException::networkError();
            }

            $content = $data['choices'][0]['message']['content'];

            Log::info('OpenAI API call successful', [
                'response_length' => strlen($content),
            ]);

            return $content;
        } catch (ConnectionException $e) {
            // Requirement 7.4: Handle network connection failures
            Log::error('Network connection error calling OpenAI', [
                'error' => $e->getMessage(),
            ]);
            throw AIServiceException::networkError();
        } catch (RequestException $e) {
            // Requirement 7.4: Handle request timeout
            if (str_contains($e->getMessage(), 'timeout')) {
                Log::error('OpenAI API request timeout');
                throw AIServiceException::timeout();
            }

            Log::error('OpenAI API request exception', [
                'error' => $e->getMessage(),
            ]);
            throw AIServiceException::networkError();
        }
    }

    /**
     * Parse OpenAI response into ticket data array
     * 
     * Extracts ticket information from the JSON response and validates
     * that each ticket has the required fields.
     * 
     * @param string $response Raw response from OpenAI
     * @return array Array of ticket data
     * @throws AIServiceException If response is malformed
     * Requirements: 4.1, 4.2
     */
    private function parseResponse(string $response): array
    {
        Log::info('Parsing OpenAI response');

        // Clean up the response - sometimes AI adds markdown code blocks
        $response = trim($response);
        $response = preg_replace('/^```json\s*/m', '', $response);
        $response = preg_replace('/\s*```$/m', '', $response);
        $response = trim($response);

        // Requirement 4.1: Parse JSON response
        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Failed to parse OpenAI response as JSON', [
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500),
            ]);
            throw new AIServiceException('Response AI tidak valid: ' . json_last_error_msg());
        }

        // Validate response structure
        if (!isset($data['tickets']) || !is_array($data['tickets'])) {
            Log::error('OpenAI response missing tickets array', [
                'data' => $data,
            ]);
            throw new AIServiceException('Response AI tidak valid: missing tickets array');
        }

        $tickets = $data['tickets'];

        // Requirement 3.4: Validate ticket count (should be 3-5)
        if (count($tickets) < 3 || count($tickets) > 5) {
            Log::warning('OpenAI returned unexpected number of tickets', [
                'count' => count($tickets),
            ]);
        }

        // Requirement 4.2: Validate each ticket has required fields
        $validatedTickets = [];
        foreach ($tickets as $index => $ticket) {
            if (!isset($ticket['title']) || !isset($ticket['description'])) {
                Log::warning('Ticket missing required fields', [
                    'index' => $index,
                    'ticket' => $ticket,
                ]);
                continue; // Skip invalid tickets
            }

            // Requirement 4.3, 4.4: Validate non-empty title and description
            if (empty(trim($ticket['title'])) || empty(trim($ticket['description']))) {
                Log::warning('Ticket has empty title or description', [
                    'index' => $index,
                ]);
                continue; // Skip tickets with empty fields
            }

            // Acceptance criteria is optional but should be an array if present
            if (isset($ticket['acceptance_criteria']) && !is_array($ticket['acceptance_criteria'])) {
                $ticket['acceptance_criteria'] = [];
            }

            $validatedTickets[] = [
                'title' => trim($ticket['title']),
                'description' => trim($ticket['description']),
                'acceptance_criteria' => $ticket['acceptance_criteria'] ?? [],
            ];
        }

        if (empty($validatedTickets)) {
            Log::error('No valid tickets found in OpenAI response');
            throw new AIServiceException('Response AI tidak menghasilkan tiket yang valid');
        }

        Log::info('Successfully parsed tickets', [
            'count' => count($validatedTickets),
        ]);

        return $validatedTickets;
    }

    /**
     * Validate API key format
     * 
     * OpenAI API keys should start with 'sk-'
     * 
     * @param string $apiKey
     * @return bool
     * Requirement: 2.4
     */
    private function isValidApiKeyFormat(string $apiKey): bool
    {
        return str_starts_with($apiKey, 'sk-') && strlen($apiKey) > 20;
    }

    /**
     * Truncate text to specified length while preserving word boundaries
     * 
     * @param string $text
     * @param int $maxLength
     * @return string
     * Requirement: 3.5
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (strlen($text) <= $maxLength) {
            return $text;
        }

        $truncated = substr($text, 0, $maxLength);
        $lastSpace = strrpos($truncated, ' ');

        if ($lastSpace !== false) {
            $truncated = substr($truncated, 0, $lastSpace);
        }

        return $truncated . '...';
    }
}
