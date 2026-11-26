<?php

namespace App\Actions\Employees;

use App\Models\Payment;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Deletes an employee for good (no soft delete). Their reservations, past,
 * future and cancelled, pass to another active employee chosen when
 * deleting, and take that employee's color. Who cancelled a reservation or
 * invoiced a payment is left blank: it can't be someone else.
 *
 * Nobody can delete themselves, so the app never ends up without anyone
 * able to log in.
 */
class DeleteEmployee
{
    public function __invoke(User $employee, User $actor, ?int $transferToId = null): void
    {
        if ($employee->is($actor)) {
            throw ValidationException::withMessages(['delete' => __('employees.delete.cannot_self')]);
        }

        DB::transaction(function () use ($employee, $transferToId) {
            $reservations = Reservation::withTrashed()->where('employee_id', $employee->id);

            if ($reservations->exists()) {
                $heir = $transferToId && $transferToId !== $employee->id
                    ? User::active()->find($transferToId)
                    : null;

                if (! $heir) {
                    throw ValidationException::withMessages(['transferTo' => __('employees.delete.choose_heir')]);
                }

                $reservations->update(['employee_id' => $heir->id]);
            }

            Reservation::withTrashed()->where('cancelled_by_id', $employee->id)->update(['cancelled_by_id' => null]);
            Payment::query()->where('invoiced_by_id', $employee->id)->update(['invoiced_by_id' => null]);

            // Logs them out everywhere; passkeys go with the user (cascade).
            DB::table('sessions')->where('user_id', $employee->id)->delete();

            $employee->delete();
        });
    }
}
