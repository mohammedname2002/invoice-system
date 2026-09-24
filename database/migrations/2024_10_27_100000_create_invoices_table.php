<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            // Restrict: deleting a customer must never take its invoices with it.
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('number', 32)->unique();
            $table->date('issue_date');
            $table->date('due_date');
            $table->string('status', 20)->default('unpaid');
            // Snapshot of the customer discount at the time the invoice was issued.
            $table->decimal('discount_rate', 5, 2)->default(0);

            // Totals in minor units, recalculated by InvoiceService on every write.
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_total')->default(0);
            $table->bigInteger('tax_total')->default(0);
            $table->bigInteger('total')->default(0);
            $table->bigInteger('amount_credited')->default(0);
            $table->bigInteger('amount_paid')->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status', 'due_date']);
            $table->index('issue_date');
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedSmallInteger('position')->default(0);
            $table->string('description');
            $table->unsignedInteger('quantity');
            // Bonus units shipped free of charge.
            $table->unsignedInteger('free_quantity')->default(0);
            $table->bigInteger('unit_price');
            $table->decimal('discount_rate', 5, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->bigInteger('subtotal');
            $table->bigInteger('discount_amount');
            $table->bigInteger('tax_amount');
            $table->bigInteger('total');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
    }
};
