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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('quantity')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->decimal('discount', 5, 2)->default(0.00);
            $table->string('status')->default('active')->index();
            $table->boolean('show_in_slider')->default(false)->nullable();
            $table->boolean('featured')->default(false);
            $table->unsignedBigInteger('product_subcategory_id');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Foreign key constraints
            $table->foreign('product_subcategory_id')->references('id')->on('product_subcategories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('restrict');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
