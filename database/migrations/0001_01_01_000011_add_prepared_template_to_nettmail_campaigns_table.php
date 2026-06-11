<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nettmail_campaigns', function (Blueprint $table): void {
            $table->longText('prepared_html')->nullable();
            $table->longText('prepared_text')->nullable();
            $table->string('send_token_placeholder')->nullable();
        });
    }
};
