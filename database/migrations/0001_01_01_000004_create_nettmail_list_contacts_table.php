<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_list_contacts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('list_id')->constrained('nettmail_lists')->cascadeOnDelete();
            $table->foreignUuid('contact_id')->constrained('nettmail_contacts')->cascadeOnDelete();
            $table->string('status');
            $table->json('tags')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();

            $table->unique(['list_id', 'contact_id']);
        });
    }
};
