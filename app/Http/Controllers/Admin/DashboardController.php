<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Broadcast;
use App\Models\FcmToken;
use App\Services\FirestoreUserDirectory;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly FirestoreUserDirectory $directory)
    {
    }

    public function index(): View
    {
        // Firestore counts are a network call each — cache briefly so
        // repeated dashboard loads don't hit it on every request.
        $stats = Cache::remember('admin_dashboard_stats', now()->addMinute(), function () {
            return [
                'total_users' => $this->directory->count([]),
                'vip_users' => $this->directory->count(['is_vip' => '1']),
                'reachable_devices' => FcmToken::count(),
            ];
        });

        return view('admin.dashboard', [
            'totalUsers' => $stats['total_users'],
            'vipUsers' => $stats['vip_users'],
            'reachableDevices' => $stats['reachable_devices'],
            'broadcastsSent' => Broadcast::count(),
            'recentBroadcasts' => Broadcast::with('admin')->latest()->take(5)->get(),
        ]);
    }
}
