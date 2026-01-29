<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Listing;

use Illuminate\Http\JsonResponse;

class UserApiController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::select([
            'id',
            'name', 
            'email', 
            'role', 
            'created_at', 
            'updated_at'
             ])->get();
        return response()->json($users);
    }

    public function show($id): JsonResponse
    {
        $user = User::with('listings')->findOrFail($id);
        return response()->json($user);
    }

    public function listings($id): JsonResponse
    {
        $listings = Listing::where('user_id', $id)->with('user')->latest()->get();
        return response()->json($listings);
    }
}
