<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('product_id')->nullable();
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('total_price', 15, 2);
            $table->timestamps();

            $table->foreign('order_id', 'fk_order_items_order_id')
                ->references('id')->on('orders')->onDelete('cascade');

            $table->foreign('product_id', 'fk_order_items_product_id')
                ->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
