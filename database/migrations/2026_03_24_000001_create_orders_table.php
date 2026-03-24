<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('payment_option'); // "Pay Now" or "Pay on Delivery"
            $table->string('status')->default('pending'); // pending, processing, completed, cancelled
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id', 'fk_orders_user_id')
                ->references('id')->on('users')->onDelete('set null');

            $table->foreign('created_by', 'fk_orders_created_by')
                ->references('id')->on('users')->onDelete('set null');

            $table->foreign('updated_by', 'fk_orders_updated_by')
                ->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
