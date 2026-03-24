<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->string('transaction_type')->default('automatic'); // automatic, manual
            $table->string('payment_method'); // Olycash, Mtn Mobile Money, Airtel Mobile Money, Cash, etc.
            $table->string('status')->default('pending'); // pending, initiated, success, failed
            $table->decimal('amount', 15, 2);
            $table->string('currency', 10)->default('UGX');
            $table->text('notes')->nullable();

            // OlyCash response fields
            $table->string('olycash_purchase_id')->nullable()->index();
            $table->string('olycash_event')->nullable();
            $table->string('olycash_message')->nullable(); // initiated, success, fail
            $table->string('olycash_message_details')->nullable();
            $table->string('olycash_payment_type')->nullable();
            $table->string('olycash_buyer_id')->nullable();
            $table->string('olycash_buyer_name')->nullable();
            $table->string('olycash_buyer_telephone')->nullable();
            $table->string('olycash_buyer_email')->nullable();
            $table->string('olycash_code')->nullable(); // transaction code on success
            $table->integer('olycash_quantity')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('order_id', 'fk_transactions_order_id')
                ->references('id')->on('orders')->onDelete('cascade');

            $table->foreign('created_by', 'fk_transactions_created_by')
                ->references('id')->on('users')->onDelete('set null');

            $table->foreign('updated_by', 'fk_transactions_updated_by')
                ->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
