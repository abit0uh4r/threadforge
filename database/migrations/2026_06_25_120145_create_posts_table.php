<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('raw_content_id')->constrained()->cascadeOnDelete();
            $table->string('hook');
            $table->json('body_points');
            $table->unsignedTinyInteger('readability_score')->nullable();
            $table->json('suggested_hashtags')->nullable();
            $table->text('tone_justification')->nullable();
            $table->string('status')->default('draft'); // draft, archived, posted
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
