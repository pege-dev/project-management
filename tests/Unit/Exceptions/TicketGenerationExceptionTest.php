<?php

use App\Exceptions\TicketGenerationException;

describe('TicketGenerationException', function () {
    it('creates exception for malformed response', function () {
        $exception = TicketGenerationException::malformedResponse();

        expect($exception)
            ->toBeInstanceOf(TicketGenerationException::class)
            ->and($exception->getMessage())
            ->toBe('Response AI tidak valid');
    });

    it('creates exception for invalid ticket data with reason', function () {
        $reason = 'Title is empty';
        $exception = TicketGenerationException::invalidTicketData($reason);

        expect($exception)
            ->toBeInstanceOf(TicketGenerationException::class)
            ->and($exception->getMessage())
            ->toBe("Data tiket tidak valid: {$reason}");
    });

    it('creates exception with different reasons', function () {
        $reasons = [
            'Title is empty',
            'Description is missing',
            'Invalid format',
            'Required field not found',
        ];

        foreach ($reasons as $reason) {
            $exception = TicketGenerationException::invalidTicketData($reason);

            expect($exception->getMessage())
                ->toBe("Data tiket tidak valid: {$reason}");
        }
    });

    it('can be thrown and caught', function () {
        expect(fn() => throw TicketGenerationException::malformedResponse())
            ->toThrow(TicketGenerationException::class, 'Response AI tidak valid');
    });
});
