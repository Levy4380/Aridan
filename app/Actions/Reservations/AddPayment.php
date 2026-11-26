<?php

namespace App\Actions\Reservations;

use App\Domain\PaymentMethod;
use App\Domain\PaymentStatus;
use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Adds one payment from the reservation detail, without going through the
 * whole edit (e.g. the rest paid in cash at check-in). The status is the
 * one the employee chose: señado or pagado, never derived from the money.
 * The total is kept only while señada.
 */
class AddPayment
{
    /**
     * @param  array{amount: mixed, method?: string|null, is_invoiced?: bool, payment_status: string, total_amount?: mixed}  $input
     */
    public function __invoke(Reservation $reservation, array $input, User $by): Payment
    {
        if ($reservation->trashed()) {
            throw ValidationException::withMessages(['reservation' => __('reservations.errors.cancelled')]);
        }

        $data = Validator::make($input, [
            'amount' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'method' => ['nullable', Rule::enum(PaymentMethod::class)],
            'is_invoiced' => ['boolean'],
            'payment_status' => ['required', Rule::in([PaymentStatus::Deposit->value, PaymentStatus::Paid->value])],
            'total_amount' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
        ], [], [
            'amount' => __('reservations.fields.amount'),
            'total_amount' => __('reservations.fields.total_amount'),
        ])->validate();

        return DB::transaction(function () use ($reservation, $data, $by) {
            $reservation = Reservation::lockForUpdate()->findOrFail($reservation->id);
            $status = PaymentStatus::from($data['payment_status']);

            $reservation->update([
                'payment_status' => $status,
                'total_amount' => $status->hasTotal() ? ($data['total_amount'] ?? $reservation->total_amount) : null,
            ]);

            $invoiced = (bool) ($data['is_invoiced'] ?? false);
            $payment = $reservation->payments()->make(['amount' => $data['amount'], 'method' => $data['method'] ?? null]);
            $payment->invoiced_at = $invoiced ? now() : null;
            $payment->invoiced_by_id = $invoiced ? $by->id : null;
            $payment->save();

            return $payment;
        });
    }
}
