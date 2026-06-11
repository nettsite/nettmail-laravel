<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_campaign_links', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('campaign_id')->constrained('nettmail_campaigns')->cascadeOnDelete();
            $table->string('link_hash');
            $table->text('url');
            $table->timestamps();

            $table->unique(['campaign_id', 'link_hash']);
        });
    }
};
