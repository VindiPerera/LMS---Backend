@extends('admin.layouts.app')

@section('title', 'Dashboard')

@section('content')
<h1 class="text-2xl font-semibold text-slate-900">Dashboard</h1>
<p class="text-sm text-slate-500 mt-1 mb-6">A snapshot of the real user base (Firebase Auth / Firestore).</p>

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    <div class="card-pad">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Total users</span>
            <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 17v-1.5a3.5 3.5 0 0 0-3.5-3.5H5.5A3.5 3.5 0 0 0 2 15.5V17m15 0v-1.5a3.5 3.5 0 0 0-2.5-3.35M11.5 3.16a3.5 3.5 0 0 1 0 6.68M9.75 6.5a3.25 3.25 0 1 1-6.5 0 3.25 3.25 0 0 1 6.5 0Z"/>
                </svg>
            </div>
        </div>
        <div class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format($totalUsers) }}</div>
    </div>

    <div class="card-pad">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">VIP users</span>
            <div class="flex size-8 items-center justify-center rounded-lg bg-amber-50 text-amber-600">
                <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m10 3 2.1 4.6 5 .6-3.7 3.4.9 5-4.3-2.5-4.3 2.5.9-5-3.7-3.4 5-.6L10 3Z"/>
                </svg>
            </div>
        </div>
        <div class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format($vipUsers) }}</div>
    </div>

    <div class="card-pad">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Reachable devices</span>
            <div class="flex size-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 2.5h7a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-7a1 1 0 0 1-1-1v-13a1 1 0 0 1 1-1Zm2.75 12.25h1.5"/>
                </svg>
            </div>
        </div>
        <div class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format($reachableDevices) }}</div>
        <p class="text-xs text-slate-400 mt-1">Have a saved push token</p>
    </div>

    <div class="card-pad">
        <div class="flex items-center justify-between">
            <span class="text-xs font-medium text-slate-500 uppercase tracking-wide">Broadcasts sent</span>
            <div class="flex size-8 items-center justify-center rounded-lg bg-slate-100 text-slate-600">
                <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2 8.5 15 3v14L2 11.5v-3Zm0 0v3M6 12v3a1.5 1.5 0 0 0 3 0v-2M15 7.5a2.5 2.5 0 0 1 0 5"/>
                </svg>
            </div>
        </div>
        <div class="mt-3 text-2xl font-semibold text-slate-900">{{ number_format($broadcastsSent) }}</div>
    </div>
</div>

<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h2 class="text-sm font-semibold text-slate-900">Recent broadcasts</h2>
        <a href="{{ route('admin.broadcasts.index') }}" class="text-sm text-indigo-600 hover:underline">View all</a>
    </div>

    @if ($recentBroadcasts->isEmpty())
        <div class="px-5 py-10 text-center text-sm text-slate-400">No broadcasts sent yet.</div>
    @else
        <table class="w-full text-sm">
            <tbody class="divide-y divide-slate-100">
                @foreach ($recentBroadcasts as $broadcast)
                    <tr>
                        <td class="td-cell font-medium text-slate-900">{{ $broadcast->title }}</td>
                        <td class="td-cell text-slate-500">{{ $broadcast->admin?->name ?? '—' }}</td>
                        <td class="td-cell">
                            @include('admin.partials.broadcast-status-badge', ['status' => $broadcast->status])
                        </td>
                        <td class="td-cell text-right text-slate-400">{{ $broadcast->created_at->diffForHumans() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
