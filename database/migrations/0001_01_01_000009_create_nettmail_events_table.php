<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('send_id')->nullable()->constrained('nettmail_sends')->cascadeOnDelete();
            $table->string('type');
            $table->string('provider');
            $table->string('provider_event_id')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_event_id']);
        });
    }
};
