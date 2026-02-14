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
        Schema::create('shopping_carts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->integer('selected_quantity');
            $table->decimal('price', 15, 2);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('product_id', 'fk_shopping_cart_product_id')
                ->references('id')
                ->on('products')
                ->onDelete('restrict');

            $table->foreign('created_by', 'fk_shopping_cart_created_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');

            $table->foreign('updated_by', 'fk_shopping_cart_updated_by')
                ->references('id')
                ->on('users')
                ->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopping_carts');
    }
};
