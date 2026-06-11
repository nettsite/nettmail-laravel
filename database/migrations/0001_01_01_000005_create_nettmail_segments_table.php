<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('nettmail_segments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('list_id')->constrained('nettmail_lists')->cascadeOnDelete();
            $table->string('name');
            $table->json('conditions');
            $table->timestamps();
        });
    }
};
