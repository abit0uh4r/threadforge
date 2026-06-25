<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['campaign_id', 'raw_content_id', 'hook', 'body_points', 'readability_score', 'suggested_hashtags', 'tone_justification', 'status', 'version'])]
class Post extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Post $post) {
            $post->versions()->create([
                'version' => $post->version,
                'hook' => $post->hook,
                'body_points' => $post->body_points,
                'suggested_hashtags' => $post->suggested_hashtags,
                'tone_justification' => $post->tone_justification,
                'readability_score' => $post->readability_score,
                'change_summary' => 'Initial version',
            ]);
        });
    }

    protected function casts(): array
    {
        return [
            'body_points' => 'array',
            'suggested_hashtags' => 'array',
            'readability_score' => 'integer',
        ];
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function rawContent(): BelongsTo
    {
        return $this->belongsTo(RawContent::class);
    }

    public function versions(): HasMany
    {
        return $this->hasMany(PostVersion::class)->orderByDesc('version');
    }
}
