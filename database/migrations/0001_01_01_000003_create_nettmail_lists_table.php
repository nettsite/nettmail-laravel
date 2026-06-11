<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_lists', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->foreignUuid('sender_id')->nullable()->constrained('nettmail_senders')->nullOnDelete();
            $table->boolean('double_optin')->default(false);
            $table->string('contact_source_key')->nullable();
            $table->timestamps();
        });
    }
};
