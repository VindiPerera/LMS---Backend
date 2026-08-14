<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PartnerController extends Controller
{
    /**
     * List other users to talk with, for the Connect tab
     * (connect_screen.dart's "Partners" list — replaces mockUsers).
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->where('id', '!=', $request->user()->id)
            ->orderByDesc('is_online')
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 30));

        return response()->json([
            'users' => UserResource::collection($users->items()),
            'meta' => [
                'currentPage' => $users->currentPage(),
                'lastPage' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    /**
     * A single partner's profile, for connect_screen.dart tapping into
     * partner_profile_screen.dart. Route-model-bound, so an unknown id
     * results in a 404 automatically.
     */
    public function show(User $user): JsonResponse
    {
        return response()->json([
            'user' => new UserResource($user),
        ]);
    }
}
