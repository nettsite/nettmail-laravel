<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_senders', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('from_email');
            $table->string('from_name');
            $table->string('driver');
            $table->json('config')->nullable();
            $table->json('bounce_mailbox')->nullable();
            $table->timestamps();
        });
    }
};
