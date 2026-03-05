<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Message;
use App\Models\Cart;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $viewer = Auth::user();

        $listings = Listing::with('user')
            ->when($search, function ($query, $search) {
                $query->where('breed', 'like', "%{$search}%")
                    ->orWhere('location', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate(9)
            ->withQueryString();

        // Simple view tracker: count impressions when buyers/guests load the listing page.
        if (!$viewer || $viewer->role === 'buyer') {
            $visibleIds = $listings->getCollection()->pluck('id');
            Listing::whereIn('id', $visibleIds)->increment('views_count');
        }

        return Inertia::render('Listings/Index', [
            'listings' => $listings,
            
            'canPost' => Auth::check(),
            'filters' => $request->only(['search']),
            'userRole' => Auth::user() ? Auth::user()->role : 'guest', 
            'cartCount' => Auth::user() ? Auth::user()->carts()->count() : 0,
        ]);
    }

    public function store(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'seller') {
            abort(403, 'Only the seller account can create listings.');
        }

        $validated = $request->validate([
            'breed' => 'required|string|max:50',
            'price' => 'required|numeric|min:1',
            'age_weeks' => 'required|integer',
            'location' => 'required|string',
            'description' => 'required|string|min:10', 
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif,bmp|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('listings', 'public');
            $validated['image'] = $path;
        }

        $request->user()->listings()->create($validated);

        return redirect()->route('listings.index');
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
