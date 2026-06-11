<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_sends', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->nullable()->constrained('nettmail_campaigns')->cascadeOnDelete();
            $table->string('transactional_key')->nullable();
            $table->foreignUuid('contact_id')->constrained('nettmail_contacts')->cascadeOnDelete();
            $table->string('send_token')->unique();
            $table->string('provider_message_id')->nullable()->index();
            $table->string('status');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('clicked_at')->nullable();
            $table->timestamp('bounced_at')->nullable();
            $table->timestamps();

            $table->unique(['campaign_id', 'contact_id']);
        });
    }
};
