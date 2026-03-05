<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
class Listing extends Model
{
    use HasFactory;
    use SoftDeletes;
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
        'views_count',
    ];

    /**
     * Get the user that owns the listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (Listing $listing) {
            if ($listing->image) {
                Storage::disk('public')->delete($listing->image);
            }
        });
    }
}
