<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A reservation can be paid in parts with different methods (e.g. the
 * deposit by transfer and the rest in cash): each part is a payment, with
 * its own "Facturada". The reservation keeps the total agreed when it's
 * señada. What was already loaded moves to one payment per reservation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method')->nullable();
            // Facturada: null = not invoiced yet. Who and when, for control.
            $table->timestamp('invoiced_at')->nullable()->index();
            $table->foreignId('invoiced_by_id')->nullable()->constrained('users');
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('total_amount', 12, 2)->nullable()->after('payment_status');
        });

        DB::statement(<<<'SQL'
            INSERT INTO payments (reservation_id, amount, method, invoiced_at, invoiced_by_id, created_at, updated_at)
            SELECT id, amount, payment_method, invoiced_at, invoiced_by_id, created_at, updated_at
            FROM reservations
            WHERE amount IS NOT NULL AND amount > 0 AND payment_status <> 'pending'
            SQL);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoiced_by_id');
            $table->dropColumn(['amount', 'payment_method', 'invoiced_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->decimal('amount', 12, 2)->nullable()->after('payment_status');
            $table->string('payment_method')->nullable()->after('amount');
            $table->timestamp('invoiced_at')->nullable()->index()->after('payment_method');
            $table->foreignId('invoiced_by_id')->nullable()->after('invoiced_at')->constrained('users');
        });

        // Back to one amount per reservation: the sum, with the first method.
        DB::statement(<<<'SQL'
            UPDATE reservations r SET
                amount = p.amount,
                payment_method = p.method,
                invoiced_at = p.invoiced_at,
                invoiced_by_id = p.invoiced_by_id
            FROM (
                SELECT reservation_id,
                    SUM(amount) AS amount,
                    (ARRAY_AGG(method ORDER BY id))[1] AS method,
                    CASE WHEN BOOL_AND(invoiced_at IS NOT NULL) THEN MAX(invoiced_at) END AS invoiced_at,
                    CASE WHEN BOOL_AND(invoiced_at IS NOT NULL) THEN MAX(invoiced_by_id) END AS invoiced_by_id
                FROM payments GROUP BY reservation_id
            ) p
            WHERE p.reservation_id = r.id
            SQL);

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('total_amount');
        });

        Schema::dropIfExists('payments');
    }
};
