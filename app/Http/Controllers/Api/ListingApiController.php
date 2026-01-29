<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ListingApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $listings = Listing::with('user')
            ->when($request->query('search'), fn($q, $s) => $q->where('breed', 'like', "%{$s}%")->orWhere('location', 'like', "%{$s}%"))
            ->latest()
            ->get();

        return response()->json($listings);
    }

    public function show(Listing $listing): JsonResponse
    {
        return response()->json($listing->load('user'));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'breed' => 'required|string|max:50',
            'price' => 'required|numeric|min:1',
            'age_weeks' => 'required|integer',
            'location' => 'required|string',
            'description' => 'required|string|min:10',
        ]);

        $listing = $request->user()->listings()->create($validated);

        return response()->json($listing, 201);
    }

    public function update(Request $request, Listing $listing): JsonResponse
    {
        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $listing->update($request->only(['is_available', 'price', 'description', 'location', 'breed']));

        return response()->json($listing);
    }

    public function destroy(Request $request, Listing $listing): JsonResponse
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        if ($request->user()->id !== $listing->user_id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $listing->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
