<?php

namespace App\Exceptions;

use Exception;

/**
 * Exception thrown when AI service operations fail
 * 
 * This exception handles various AI service-related errors including:
 * - Missing or invalid API keys
 * - Network connectivity issues
 * - Rate limiting
 * - Request timeouts
 */
class AIServiceException extends Exception
{
    /**
     * Create exception for missing API key configuration
     * 
     * @return self
     */
    public static function missingApiKey(): self
    {
        return new self('OpenAI API key not configured');
    }

    /**
     * Create exception for invalid API key format
     * 
     * @return self
     */
    public static function invalidApiKey(): self
    {
        return new self('API key tidak valid, silakan periksa konfigurasi');
    }

    /**
     * Create exception for rate limit exceeded
     * 
     * @return self
     */
    public static function rateLimitExceeded(): self
    {
        return new self('Rate limit tercapai, silakan coba lagi nanti');
    }

    /**
     * Create exception for network connectivity errors
     * 
     * @return self
     */
    public static function networkError(): self
    {
        return new self('Koneksi ke AI service gagal, periksa koneksi internet');
    }

    /**
     * Create exception for request timeout
     * 
     * @return self
     */
    public static function timeout(): self
    {
        return new self('Request timeout, silakan coba lagi');
    }
}
