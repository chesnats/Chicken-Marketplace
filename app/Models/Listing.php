<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
class Listing extends Model
{
    use HasFactory;
    use SoftDeletes;
    // This allows these fields to be saved into the database
    protected $fillable = [
        'user_id',
        'chicken_type',
        'breed',
        'description',
        'price',
        'quantity',
        'weight_kg',
        'size_label',
        'chicken_condition',
        'delivery_option',
        'contact_preference',
        'category_tags',
        'age_weeks',
        'location',
        'status',
        'image',
        'is_available',
        'views_count',
    ];

    protected $casts = [
        'category_tags' => 'array',
    ];

    /**
     * Get the user that owns the listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(ListingMedia::class)->orderBy('sort_order')->orderBy('id');
    }

    protected static function booted(): void
    {
        static::forceDeleted(function (Listing $listing) {
            if ($listing->image) {
                Storage::disk('public')->delete($listing->image);
            }

            $listing->media()->each(function (ListingMedia $media): void {
                Storage::disk('public')->delete($media->path);
            });
        });
    }
}
