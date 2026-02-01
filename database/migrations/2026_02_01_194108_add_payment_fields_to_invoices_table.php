<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->uuid('uuid')->after('id')->nullable()->unique();
            $table->string('payment_proof')->nullable()->after('notes'); // Path gambar bukti bayar
            $table->timestamp('payment_date')->nullable()->after('payment_proof');
            $table->string('payment_status')->default('unpaid')->after('status'); // unpaid, pending_verification, paid, rejected
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['uuid', 'payment_proof', 'payment_date', 'payment_status']);
        });
    }
};
