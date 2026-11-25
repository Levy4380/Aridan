<?php

namespace App\Domain;

/**
 * How a group fits an apartment: exact, bigger (spare places) or smaller
 * (needs extra mattresses). A smaller apartment is never blocked.
 */
final readonly class CapacityFit
{
    public const EXACT = 'exact';

    public const BIGGER = 'bigger';

    public const SMALLER = 'smaller';

    public string $kind;

    /** Spare places (bigger) or missing places (smaller); 0 when exact. */
    public int $difference;

    public function __construct(public int $capacity, public int $people)
    {
        $this->difference = abs($capacity - $people);
        $this->kind = match (true) {
            $capacity === $people => self::EXACT,
            $capacity > $people => self::BIGGER,
            default => self::SMALLER,
        };
    }

    /**
     * Lower is better: exact first, then bigger (less spare first), then
     * smaller (fewer missing first).
     *
     * @return array{int, int}
     */
    public function rank(): array
    {
        return [
            match ($this->kind) {
                self::EXACT => 0,
                self::BIGGER => 1,
                self::SMALLER => 2,
            },
            $this->difference,
        ];
    }
}
