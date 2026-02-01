<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when ticket generation process fails
 * 
 * This exception handles errors related to:
 * - Malformed AI responses
 * - Invalid ticket data
 * - Validation failures
 */
class TicketGenerationException extends Exception
{
    /**
     * Create exception for malformed AI response
     * 
     * @return self
     */
    public static function malformedResponse(): self
    {
        return new self('Response AI tidak valid');
    }

    /**
     * Create exception for invalid ticket data
     * 
     * @param string $reason The specific reason why the ticket data is invalid
     * @return self
     */
    public static function invalidTicketData(string $reason): self
    {
        return new self("Data tiket tidak valid: {$reason}");
    }
}
