<?php

namespace App\Domain;

/**
 * A non-blocking warning: the system warns, it doesn't block. The employee
 * confirms it with "Guardar igual".
 */
final readonly class Warning
{
    public const CAPACITY_EXCEEDED = 'capacity_exceeded';

    public const NOT_MOVABLE = 'not_movable';

    public const MISSING_AMOUNT = 'missing_amount';

    public const SIMILAR_GUEST_NAME = 'similar_guest_name';

    public const COLOR_IN_USE = 'color_in_use';

    public function __construct(
        public string $code,
        public string $message,
    ) {}
}
