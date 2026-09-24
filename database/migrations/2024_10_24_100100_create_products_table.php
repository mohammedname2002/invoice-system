<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku', 64)->nullable()->unique();
            // Money is stored in minor units (cents) to avoid float rounding.
            $table->bigInteger('unit_price');
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->boolean('apply_customer_discount')->default(true);
            $table->integer('stock_quantity')->default(0);
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
