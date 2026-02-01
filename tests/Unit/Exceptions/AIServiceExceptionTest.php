<?php

use App\Exceptions\AIServiceException;

describe('AIServiceException', function () {
    it('creates exception for missing API key', function () {
        $exception = AIServiceException::missingApiKey();

        expect($exception)
            ->toBeInstanceOf(AIServiceException::class)
            ->and($exception->getMessage())
            ->toBe('OpenAI API key not configured');
    });

    it('creates exception for invalid API key', function () {
        $exception = AIServiceException::invalidApiKey();

        expect($exception)
            ->toBeInstanceOf(AIServiceException::class)
            ->and($exception->getMessage())
            ->toBe('API key tidak valid, silakan periksa konfigurasi');
    });

    it('creates exception for rate limit exceeded', function () {
        $exception = AIServiceException::rateLimitExceeded();

        expect($exception)
            ->toBeInstanceOf(AIServiceException::class)
            ->and($exception->getMessage())
            ->toBe('Rate limit tercapai, silakan coba lagi nanti');
    });

    it('creates exception for network error', function () {
        $exception = AIServiceException::networkError();

        expect($exception)
            ->toBeInstanceOf(AIServiceException::class)
            ->and($exception->getMessage())
            ->toBe('Koneksi ke AI service gagal, periksa koneksi internet');
    });

    it('creates exception for timeout', function () {
        $exception = AIServiceException::timeout();

        expect($exception)
            ->toBeInstanceOf(AIServiceException::class)
            ->and($exception->getMessage())
            ->toBe('Request timeout, silakan coba lagi');
    });

    it('can be thrown and caught', function () {
        expect(fn() => throw AIServiceException::missingApiKey())
            ->toThrow(AIServiceException::class, 'OpenAI API key not configured');
    });
});
