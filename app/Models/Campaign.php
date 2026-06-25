<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'target_audience', 'tone', 'max_length', 'max_hashtags', 'rules'])]
class Campaign extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'rules' => 'array',
            'max_length' => 'integer',
            'max_hashtags' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
