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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->string('status')->default('draft'); // draft, sent, paid, cancelled, overdue
            $table->string('type')->default('other'); // dp, pelunasan, termin, other
            $table->date('issue_date');
            $table->date('due_date');
            
            // Sender Snapshot (Company Details)
            $table->string('company_name')->nullable();
            $table->text('company_address')->nullable();
            $table->string('company_phone')->nullable();
            $table->text('company_bank_account')->nullable();
            $table->string('company_logo')->nullable(); // Path to logo

            // Receiver Snapshot (Client Details)
            $table->string('client_name')->nullable();
            $table->text('client_address')->nullable();
            
            // Financials
            $table->json('items')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
