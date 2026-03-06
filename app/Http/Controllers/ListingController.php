<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Message;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ListingController extends Controller
{
    private const DEFAULT_SELLER_LOCATION = 'Bulacao Sta. Filomena Alegria, Cebu';
    private const CHICKEN_TYPES = ['not_fighting_cock', 'fighting_cock'];
    private const CHICKEN_CONDITIONS = ['live', 'processed', 'frozen'];
    private const DELIVERY_OPTIONS = ['pickup_only', 'local_delivery', 'shipping'];
    private const CONTACT_PREFERENCES = ['call', 'platform_message'];
    private const CATEGORY_TAGS = ['organic', 'free_range', 'antibiotic_free'];

    private const BREEDS_BY_TYPE = [
        'not_fighting_cock' => [
            'Rhode Island Red',
            'Black Australorp',
            'Barred Plymouth Rock',
            'White Leghorn',
            'SASSO',
            'Dominant CZ',
        ],
        'fighting_cock' => [
            'Sweater',
            'Hatch',
            'Roundhead',
            'Claret',
            'Whitehackle',
            'Kelso',
            'Radio',
            'Brown Red',
            'Lemon 84',
        ],
    ];

    public function index(Request $request)
    {
        $search = $request->input('search');
        $chickenType = $request->input('chicken_type');
        $breed = $request->input('breed');
        $categoryTags = array_values(array_filter((array) $request->input('category_tags', [])));
        $viewer = Auth::user();

        $listings = Listing::with(['user', 'media'])
            ->when($search, function ($query, $search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery->where('breed', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                });
            })
            ->when($chickenType, fn ($query, $value) => $query->where('chicken_type', $value))
            ->when($breed, fn ($query, $value) => $query->where('breed', $value))
            ->when(! empty($categoryTags), function ($query) use ($categoryTags) {
                foreach ($categoryTags as $tag) {
                    $query->whereJsonContains('category_tags', $tag);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Simple view tracker: count impressions when buyers/guests load the listing page.
        if (!$viewer || $viewer->role === 'buyer') {
            $visibleIds = $listings->getCollection()->pluck('id');
            Listing::whereIn('id', $visibleIds)->increment('views_count');
        }

        return Inertia::render('Listings/Index', [
            'listings' => $listings,
            'filters' => [
                ...$request->only(['search', 'chicken_type', 'breed']),
                'category_tags' => $categoryTags,
            ],
            'userRole' => Auth::user() ? Auth::user()->role : 'guest', 
            'defaultSellerLocation' => $viewer && $viewer->role === 'seller'
                ? ($viewer->seller_default_location ?: self::DEFAULT_SELLER_LOCATION)
                : null,
            'defaultSellerContactNumber' => $viewer && $viewer->role === 'seller'
                ? ($viewer->seller_contact_number ?: '09491735243')
                : null,
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'seller') {
            abort(403, 'Only the seller account can create listings.');
        }

        $validated = $request->validate([
            'chicken_type' => ['required', 'string', Rule::in(self::CHICKEN_TYPES)],
            'breed' => 'required|string|max:50',
            'price' => 'required|numeric|min:1',
            'quantity' => 'required|integer|min:1',
            'weight_kg' => 'nullable|numeric|min:0',
            'size_label' => 'nullable|string|max:50',
            'chicken_condition' => ['required', 'string', Rule::in(self::CHICKEN_CONDITIONS)],
            'delivery_option' => ['required', 'string', Rule::in(self::DELIVERY_OPTIONS)],
            'contact_preference' => ['required', 'string', Rule::in(self::CONTACT_PREFERENCES)],
            'category_tags' => 'nullable|array',
            'category_tags.*' => ['string', Rule::in(self::CATEGORY_TAGS)],
            'age_weeks' => 'required|integer',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,bmp|max:5120',
            'media' => 'nullable|array|max:8',
            'media.*' => 'file|mimes:jpg,jpeg,png,webp,gif,bmp,mp4,mov,avi,webm,mkv|max:20480',
        ]);

        $allowedBreeds = self::BREEDS_BY_TYPE[$validated['chicken_type']] ?? [];
        if (! in_array($validated['breed'], $allowedBreeds, true)) {
            return back()->withErrors([
                'breed' => 'Selected breed is not valid for the chosen chicken type.',
            ])->withInput();
        }

        $sellerLocation = $request->user()->seller_default_location ?: self::DEFAULT_SELLER_LOCATION;
        $validated['location'] = $sellerLocation;
        $validated['contact_preference'] = $validated['contact_preference'] ?? 'call';
        $validated['category_tags'] = array_values($validated['category_tags'] ?? []);
        $validated['description'] = trim((string) ($validated['description'] ?? '')) ?: 'No additional description provided.';

        $legacyImagePath = null;
        if ($request->hasFile('image')) {
            $legacyImagePath = $request->file('image')->store('listings', 'public');
            $validated['image'] = $legacyImagePath;
        }

        try {
            $listing = $request->user()->listings()->create(Arr::except($validated, ['media']));
            if (! $request->user()->seller_default_location) {
                $request->user()->update([
                    'seller_default_location' => $sellerLocation,
                ]);
            }

            $mediaPayload = [];
            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $index => $file) {
                    $mimeType = (string) $file->getMimeType();
                    $mediaPayload[] = [
                        'path' => $file->store('listings', 'public'),
                        'type' => str_starts_with($mimeType, 'video/') ? 'video' : 'image',
                        'original_name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'sort_order' => $index,
                    ];
                }
            }

            if (empty($mediaPayload) && $legacyImagePath) {
                $mediaPayload[] = [
                    'path' => $legacyImagePath,
                    'type' => 'image',
                    'original_name' => null,
                    'size' => null,
                    'sort_order' => 0,
                ];
            }

            if (!empty($mediaPayload)) {
                $listing->media()->createMany($mediaPayload);

                if (!$listing->image) {
                    $firstImage = collect($mediaPayload)->firstWhere('type', 'image');
                    if ($firstImage) {
                        $listing->update(['image' => $firstImage['path']]);
                    }
                }
            }

            return redirect()->route('listings.index');
        } catch (Throwable $e) {
            Log::error('Listing creation failed', [
                'message' => $e->getMessage(),
                'user_id' => $request->user()?->id,
            ]);

            return back()->withErrors([
                'general' => 'Failed to create listing: '.$e->getMessage(),
            ])->withInput();
        }
    }

    public function update(Request $request, Listing $listing)
    {
        if (!$request->user() || $request->user()->role !== 'seller') {
            abort(403, 'Only the seller account can update listings.');
        }

        // Only the owner can change status
        if ($listing->user_id !== Auth::id()) {
            abort(403);
        }

        $listing->update([
            'is_available' => $request->is_available
        ]);

        return back();
    }

    /**
     * Remove the listing and its associated data.
     */
    public function destroy(Listing $listing)
    {
        if (!Auth::user() || Auth::user()->role !== 'seller') {
            abort(403, 'Only the seller account can delete listings.');
        }

        // Only the owner can delete
        if ($listing->user_id !== Auth::id()) {
            abort(403);
        }

        // 1. Delete related child rows to satisfy foreign key constraints
        Message::where('listing_id', $listing->id)->delete();
        Cart::where('listing_id', $listing->id)->delete();

        // 2. Soft-delete the listing.
        // Keep image file so restore still has a valid image path.
        $listing->delete();

        return back();
    }
}

