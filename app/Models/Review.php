<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'user_id',
        'sentiment',
        'score',
        'topics',
    ];

    protected function casts(): array
    {
        return [
            'topics' => 'array',
        ];
    }

    /**
     * Relation avec l'utilisateur
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
