<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->json('metadata')->nullable();
            $table->string('source_type')->nullable();
            $table->string('source_id')->nullable();
            $table->timestamp('global_unsubscribed_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->string('bounce_type')->nullable();
            $table->unsignedInteger('consecutive_soft_bounces')->default(0);
            $table->timestamps();
        });
    }
};
