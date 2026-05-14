<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_auth_providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('provider')->index();
            $table->string('provider_id');
            $table->string('provider_token', 500)->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);

            $table->foreign('user_id', 'fk_social_auth_providers_user_id')
                ->references('id')->on('users')->onDelete('cascade');

            $table->foreign('created_by', 'fk_social_auth_providers_created_by')
                ->references('id')->on('users')->onDelete('set null');

            $table->foreign('updated_by', 'fk_social_auth_providers_updated_by')
                ->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_auth_providers');
    }
};
