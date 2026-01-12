<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Listing extends Model
{
    use HasFactory;

    // This allows these fields to be saved into the database
    protected $fillable = [
        'user_id',
        'breed',
        'description',
        'price',
        'age_weeks',
        'location',
        'status',
        'image',
        'is_available',
    ];

    /**
     * Get the user that owns the listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}