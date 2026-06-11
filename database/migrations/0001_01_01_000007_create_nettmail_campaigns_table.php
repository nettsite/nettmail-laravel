<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_campaigns', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('list_id')->constrained('nettmail_lists')->cascadeOnDelete();
            $table->foreignUuid('segment_id')->nullable()->constrained('nettmail_segments')->nullOnDelete();
            $table->foreignUuid('template_id')->constrained('nettmail_templates')->cascadeOnDelete();
            $table->foreignUuid('sender_id')->nullable()->constrained('nettmail_senders')->nullOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('status');
            $table->boolean('track_opens')->default(true);
            $table->boolean('track_clicks')->default(true);
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }
};
