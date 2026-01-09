<?php

namespace App\Http\Controllers;

use App\Models\Listing;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ListingController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        return Inertia::render('Listings/Index', [
            // Keep your search logic here
            'listings' => Listing::with('user')
                ->when($search, function ($query, $search) {
                    $query->where('breed', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%");
                })
                ->latest()
                ->get(),
            
            // Add these new lines for the Buyer logic
            'canPost' => Auth::check(),
            'filters' => $request->only(['search']),
            
            // --- ADDED THESE ---
            'userRole' => Auth::user() ? Auth::user()->role : 'guest', 
            'cartCount' => Auth::user()->carts()->count(),
        ]);
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'breed' => 'required|string|max:50',
                'price' => 'required|numeric|min:1',
                'age_weeks' => 'required|integer',
                'location' => 'required|string',
                'description' => 'required|string|min:10',
                'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // Validate image
            ]);

            // Handle Image Upload
            if ($request->hasFile('image')) {
                // Stores in storage/app/public/listings
                $path = $request->file('image')->store('listings', 'public');
                $validated['image'] = $path;
            }

            $request->user()->listings()->create($validated);

            return redirect()->route('listings.index');
            
        } catch (\Exception $e) {
            dd($e->getMessage()); 
        }
    }
}