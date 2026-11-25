<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('apartment_id')->constrained();
            $table->foreignId('guest_id')->constrained();
            // The responsible employee (who made the reservation, not necessarily who loaded it).
            $table->foreignId('employee_id')->constrained('users');
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->boolean('early_check_in')->default(false);
            $table->boolean('late_check_out')->default(false);
            // Derived half-day range [start_slot, end_slot), see App\Domain\StayRange.
            $table->integer('start_slot');
            $table->integer('end_slot');
            $table->unsignedTinyInteger('people_count');
            $table->string('payment_status');
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('payment_method')->nullable();
            $table->unsignedTinyInteger('extra_mattresses')->default(0);
            $table->boolean('is_movable')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users');

            $table->index(['apartment_id', 'start_slot', 'end_slot']);
        });

        Schema::create('extra_reservation', function (Blueprint $table) {
            $table->foreignId('extra_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->primary(['extra_id', 'reservation_id']);
        });

        DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_valid_dates CHECK (check_out_date > check_in_date)');
        DB::statement('ALTER TABLE reservations ADD CONSTRAINT reservations_valid_slots CHECK (end_slot > start_slot)');

        // Safety net in the database: two active (not cancelled) reservations of
        // the same apartment can never share a half-day. The main validation
        // lives in the actions; this only catches what slips past them.
        // int4range is half-open [start, end), same as the slots.
        DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');
        DB::statement(<<<'SQL'
            ALTER TABLE reservations
              ADD CONSTRAINT reservations_no_overlap
              EXCLUDE USING gist (
                apartment_id WITH =,
                int4range(start_slot, end_slot) WITH &&
              )
              WHERE (deleted_at IS NULL)
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_reservation');
        Schema::dropIfExists('reservations');
    }
};
