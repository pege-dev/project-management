<?php

use App\Services\AI\OpenAIService;
use App\Exceptions\AIServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

/**
 * Unit tests for OpenAIService
 * 
 * Tests cover:
 * - API key validation (valid/invalid formats)
 * - Error handling (network failures, rate limits, timeouts)
 * - Response parsing
 * - Prompt construction
 * 
 * Requirements: 2.3, 2.4, 7.2, 7.3, 7.4
 */

beforeEach(function () {
    // Set a valid API key for tests
    Config::set('services.openai.api_key', 'sk-test1234567890abcdefghijklmnopqrstuvwxyz');
    Config::set('services.openai.model', 'gpt-4o-mini');
    Config::set('services.openai.max_tokens', 2000);
});

describe('Constructor and API Key Validation', function () {

    test('constructor throws exception when API key is missing', function () {
        Config::set('services.openai.api_key', '');

        expect(fn() => new OpenAIService())
            ->toThrow(AIServiceException::class, 'OpenAI API key not configured');
    });

    test('constructor throws exception when API key is null', function () {
        Config::set('services.openai.api_key', null);

        expect(fn() => new OpenAIService())
            ->toThrow(AIServiceException::class, 'OpenAI API key not configured');
    });

    test('constructor throws exception for invalid API key format - no sk prefix', function () {
        Config::set('services.openai.api_key', 'invalid-key-format-1234567890');

        expect(fn() => new OpenAIService())
            ->toThrow(AIServiceException::class, 'API key tidak valid');
    });

    test('constructor throws exception for invalid API key format - too short', function () {
        Config::set('services.openai.api_key', 'sk-short');

        expect(fn() => new OpenAIService())
            ->toThrow(AIServiceException::class, 'API key tidak valid');
    });

    test('constructor succeeds with valid API key format', function () {
        Config::set('services.openai.api_key', 'sk-validkey1234567890abcdefghijklmnop');

        $service = new OpenAIService();

        expect($service)->toBeInstanceOf(OpenAIService::class);
    });
});

describe('Generate Tickets - Success Cases', function () {

    test('generates tickets successfully with all parameters', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Setup project structure',
                                        'description' => 'Initialize the project with proper folder structure',
                                        'acceptance_criteria' => ['Folders created', 'Config files added'],
                                    ],
                                    [
                                        'title' => 'Configure database',
                                        'description' => 'Setup database connection and migrations',
                                        'acceptance_criteria' => ['Database connected', 'Migrations run'],
                                    ],
                                    [
                                        'title' => 'Create authentication',
                                        'description' => 'Implement user authentication system',
                                        'acceptance_criteria' => ['Login works', 'Registration works'],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $tickets = $service->generateTickets(
            'E-commerce Platform',
            'A modern e-commerce platform with cart and checkout',
            'Focus on user experience and mobile responsiveness'
        );

        expect($tickets)->toBeArray()
            ->toHaveCount(3)
            ->and($tickets[0])->toHaveKeys(['title', 'description', 'acceptance_criteria'])
            ->and($tickets[0]['title'])->toBe('Setup project structure')
            ->and($tickets[0]['description'])->toBe('Initialize the project with proper folder structure');
    });

    test('generates tickets with only project name', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Initial setup',
                                        'description' => 'Setup the project',
                                        'acceptance_criteria' => ['Project initialized'],
                                    ],
                                    [
                                        'title' => 'Add features',
                                        'description' => 'Add core features',
                                        'acceptance_criteria' => ['Features added'],
                                    ],
                                    [
                                        'title' => 'Testing',
                                        'description' => 'Add tests',
                                        'acceptance_criteria' => ['Tests pass'],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $tickets = $service->generateTickets('Simple Project');

        expect($tickets)->toBeArray()->toHaveCount(3);
    });

    test('handles response with markdown code blocks', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => "```json\n" . json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Task 1',
                                        'description' => 'Description 1',
                                        'acceptance_criteria' => ['Criteria 1'],
                                    ],
                                    [
                                        'title' => 'Task 2',
                                        'description' => 'Description 2',
                                        'acceptance_criteria' => ['Criteria 2'],
                                    ],
                                    [
                                        'title' => 'Task 3',
                                        'description' => 'Description 3',
                                        'acceptance_criteria' => ['Criteria 3'],
                                    ],
                                ],
                            ]) . "\n```",
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $tickets = $service->generateTickets('Test Project');

        expect($tickets)->toBeArray()->toHaveCount(3);
    });

    test('skips tickets with missing required fields', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Valid ticket',
                                        'description' => 'This is valid',
                                        'acceptance_criteria' => ['Valid'],
                                    ],
                                    [
                                        'title' => 'Missing description',
                                        // Missing description field
                                    ],
                                    [
                                        // Missing title field
                                        'description' => 'Has description but no title',
                                    ],
                                    [
                                        'title' => 'Another valid ticket',
                                        'description' => 'This is also valid',
                                        'acceptance_criteria' => ['Also valid'],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $tickets = $service->generateTickets('Test Project');

        // Should only return the 2 valid tickets
        expect($tickets)->toBeArray()->toHaveCount(2);
    });

    test('skips tickets with empty title or description', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Valid ticket',
                                        'description' => 'This is valid',
                                    ],
                                    [
                                        'title' => '',
                                        'description' => 'Empty title',
                                    ],
                                    [
                                        'title' => 'Empty description',
                                        'description' => '   ',
                                    ],
                                    [
                                        'title' => 'Another valid',
                                        'description' => 'Also valid',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $tickets = $service->generateTickets('Test Project');

        expect($tickets)->toBeArray()->toHaveCount(2);
    });

    test('handles tickets without acceptance criteria', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Ticket without criteria',
                                        'description' => 'This ticket has no acceptance criteria',
                                    ],
                                    [
                                        'title' => 'Ticket with criteria',
                                        'description' => 'This ticket has criteria',
                                        'acceptance_criteria' => ['Criteria 1'],
                                    ],
                                    [
                                        'title' => 'Another without',
                                        'description' => 'No criteria here either',
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $tickets = $service->generateTickets('Test Project');

        expect($tickets)->toBeArray()->toHaveCount(3)
            ->and($tickets[0]['acceptance_criteria'])->toBeArray()->toBeEmpty()
            ->and($tickets[1]['acceptance_criteria'])->toBeArray()->toHaveCount(1)
            ->and($tickets[2]['acceptance_criteria'])->toBeArray()->toBeEmpty();
    });
});

describe('Error Handling', function () {

    test('throws exception for rate limit error (429)', function () {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'Rate limit exceeded'], 429),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class, 'Rate limit tercapai');
    });

    test('throws exception for invalid API key (401)', function () {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'Invalid API key'], 401),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class, 'API key tidak valid');
    });

    test('throws exception for network error (500)', function () {
        Http::fake([
            'api.openai.com/*' => Http::response(['error' => 'Internal server error'], 500),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class, 'Koneksi ke AI service gagal');
    });

    test('throws exception for malformed JSON response', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => 'This is not valid JSON {invalid}',
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class, 'Response AI tidak valid');
    });

    test('throws exception when response missing tickets array', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'data' => 'wrong structure',
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class, 'missing tickets array');
    });

    test('throws exception when all tickets are invalid', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    ['title' => ''], // Empty title
                                    ['description' => 'No title'], // Missing title
                                    ['title' => 'No description'], // Missing description
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class, 'tidak menghasilkan tiket yang valid');
    });

    test('throws exception when response structure is unexpected', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'unexpected' => 'structure',
            ], 200),
        ]);

        $service = new OpenAIService();

        expect(fn() => $service->generateTickets('Test Project'))
            ->toThrow(AIServiceException::class);
    });
});

describe('HTTP Request Configuration', function () {

    test('sends correct headers to OpenAI API', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    [
                                        'title' => 'Test',
                                        'description' => 'Test',
                                        'acceptance_criteria' => [],
                                    ],
                                    [
                                        'title' => 'Test 2',
                                        'description' => 'Test 2',
                                        'acceptance_criteria' => [],
                                    ],
                                    [
                                        'title' => 'Test 3',
                                        'description' => 'Test 3',
                                        'acceptance_criteria' => [],
                                    ],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $service->generateTickets('Test Project');

        Http::assertSent(function ($request) {
            return $request->hasHeader('Authorization', 'Bearer sk-test1234567890abcdefghijklmnopqrstuvwxyz')
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->url() === 'https://api.openai.com/v1/chat/completions';
        });
    });

    test('sends correct request body to OpenAI API', function () {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'tickets' => [
                                    ['title' => 'T1', 'description' => 'D1'],
                                    ['title' => 'T2', 'description' => 'D2'],
                                    ['title' => 'T3', 'description' => 'D3'],
                                ],
                            ]),
                        ],
                    ],
                ],
            ], 200),
        ]);

        $service = new OpenAIService();
        $service->generateTickets('Test Project', 'Test Description', 'Test Prompt');

        Http::assertSent(function ($request) {
            $body = $request->data();
            return $body['model'] === 'gpt-4o-mini'
                && $body['max_tokens'] === 2000
                && isset($body['messages'][0]['content'])
                && str_contains($body['messages'][0]['content'], 'Test Project')
                && str_contains($body['messages'][0]['content'], 'Test Description')
                && str_contains($body['messages'][0]['content'], 'Test Prompt');
        });
    });
});
