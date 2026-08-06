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
}
