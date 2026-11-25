<?php

namespace App\Models;

use App\Domain\PaymentMethod;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Money received for a reservation, with one method. A reservation can have
 * several (e.g. the deposit by transfer and the rest in cash), and each one
 * is invoiced on its own.
 *
 * @property int $id
 * @property int $reservation_id
 * @property string $amount
 * @property PaymentMethod|null $method
 * @property Carbon|null $invoiced_at
 * @property int|null $invoiced_by_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['amount', 'method'])]
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'method' => PaymentMethod::class,
            'invoiced_at' => 'datetime',
        ];
    }

    /**
     * Facturada: whether this payment was invoiced.
     */
    public function isInvoiced(): bool
    {
        return $this->invoiced_at !== null;
    }

    /**
     * Also when the reservation was cancelled: the money was received anyway.
     *
     * @return BelongsTo<Reservation, $this>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class)->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function invoicedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invoiced_by_id');
    }
}
