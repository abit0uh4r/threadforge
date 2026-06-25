<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('post_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('hook');
            $table->json('body_points');
            $table->json('suggested_hashtags')->nullable();
            $table->text('tone_justification')->nullable();
            $table->unsignedTinyInteger('readability_score')->nullable();
            $table->text('change_summary')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_versions');
    }
};
