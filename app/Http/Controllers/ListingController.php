<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use App\Models\Message; // 💡 Import the Message model
use App\Models\Cart;    // 💡 Import the Cart model if you have one
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        return Inertia::render('Listings/Index', [
            'listings' => Listing::with('user')
                ->when($search, function ($query, $search) {
                    $query->where('breed', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                })
                ->latest()
                ->get(),
            
            'canPost' => Auth::check(),
            'filters' => $request->only(['search']),
            'userRole' => Auth::user() ? Auth::user()->role : 'guest', 
            // 💡 Fix: check if user exists before counting carts to prevent crash for guests
            'cartCount' => Auth::user() ? Auth::user()->carts()->count() : 0,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'breed' => 'required|string|max:50',
            'price' => 'required|numeric|min:1',
            'age_weeks' => 'required|integer',
            'location' => 'required|string',
            'description' => 'required|string|min:10', 
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
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
        // Only the owner can delete
        if ($listing->user_id !== Auth::id()) {
            abort(403);
        }

        // 1. Delete related child rows to satisfy foreign key constraints
        Message::where('listing_id', $listing->id)->delete();
        Cart::where('listing_id', $listing->id)->delete();

        // 2. Delete the image file from storage if it exists
        if ($listing->image) {
            Storage::disk('public')->delete($listing->image);
        }

        // 3. Delete the listing itself
        $listing->delete();

        return back();
    }
}