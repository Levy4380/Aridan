<?php

namespace App\Models;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Domain\StayRange;
use Carbon\CarbonImmutable;
use Database\Factories\ReservationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * The central entity: a continuous date range on one apartment. Cancelled
 * reservations are soft deleted: they stay for control but no longer occupy
 * the apartment.
 *
 * @property int $id
 * @property int $apartment_id
 * @property int $guest_id
 * @property int $employee_id
 * @property CarbonImmutable $check_in_date
 * @property CarbonImmutable $check_out_date
 * @property bool $early_check_in
 * @property bool $late_check_out
 * @property int $start_slot
 * @property int $end_slot
 * @property int $people_count
 * @property PaymentStatus $payment_status
 * @property string|null $amount
 * @property PaymentMethod|null $payment_method
 * @property int $extra_mattresses
 * @property bool $is_movable
 * @property string|null $notes
 * @property int|null $cancelled_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
#[Fillable([
    'apartment_id', 'guest_id', 'employee_id',
    'check_in_date', 'check_out_date', 'early_check_in', 'late_check_out',
    'people_count', 'payment_status', 'amount', 'payment_method',
    'extra_mattresses', 'is_movable', 'notes',
])]
class Reservation extends Model
{
    /** @use HasFactory<ReservationFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'check_in_date' => 'immutable_date',
            'check_out_date' => 'immutable_date',
            'early_check_in' => 'boolean',
            'late_check_out' => 'boolean',
            'start_slot' => 'integer',
            'end_slot' => 'integer',
            'people_count' => 'integer',
            'payment_status' => PaymentStatus::class,
            'amount' => 'decimal:2',
            'payment_method' => PaymentMethod::class,
            'extra_mattresses' => 'integer',
            'is_movable' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        // The slots are always derived from the dates and flags.
        static::saving(function (Reservation $reservation) {
            $stay = $reservation->stay();
            $reservation->start_slot = $stay->startSlot;
            $reservation->end_slot = $stay->endSlot;
        });
    }

    public function stay(): StayRange
    {
        return StayRange::make(
            $this->check_in_date,
            $this->check_out_date,
            $this->early_check_in,
            $this->late_check_out,
        );
    }

    /**
     * Where the reservation stands today: upcoming, current, past or cancelled.
     */
    public function timing(): string
    {
        if ($this->trashed()) {
            return 'cancelled';
        }

        $now = StayRange::slot(CarbonImmutable::today(), now()->hour < 12 ? StayRange::MORNING : StayRange::AFTERNOON);

        return match (true) {
            $this->start_slot > $now => 'upcoming',
            $this->end_slot > $now => 'current',
            default => 'past',
        };
    }

    /**
     * Reservations that touch the slot window [$fromSlot, $toSlot).
     *
     * @param  Builder<Reservation>  $query
     */
    #[Scope]
    protected function overlappingSlots(Builder $query, int $fromSlot, int $toSlot): void
    {
        $query->where('start_slot', '<', $toSlot)->where('end_slot', '>', $fromSlot);
    }

    /**
     * @return BelongsTo<Apartment, $this>
     */
    public function apartment(): BelongsTo
    {
        return $this->belongsTo(Apartment::class);
    }

    /**
     * @return BelongsTo<Guest, $this>
     */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }

    /**
     * The responsible employee.
     *
     * @return BelongsTo<User, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by_id');
    }

    /**
     * @return BelongsToMany<Extra, $this>
     */
    public function extras(): BelongsToMany
    {
        return $this->belongsToMany(Extra::class);
    }
}
