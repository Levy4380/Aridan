<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            // Facturada: null = not invoiced yet. Who and when, for control.
            $table->timestamp('invoiced_at')->nullable()->after('payment_method');
            $table->foreignId('invoiced_by_id')->nullable()->after('invoiced_at')->constrained('users');
            $table->index('invoiced_at');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('invoiced_by_id');
            $table->dropColumn('invoiced_at');
        });
    }
};
