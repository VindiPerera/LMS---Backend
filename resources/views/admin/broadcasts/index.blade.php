@extends('admin.layouts.app')

@section('title', 'Broadcasts')

@section('content')
<div class="flex items-center justify-between mb-1">
    <h1 class="text-2xl font-semibold text-slate-900">Broadcasts</h1>
    <a href="{{ route('admin.broadcasts.create') }}" class="btn-primary">
        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 4.5v11M4.5 10h11"/>
        </svg>
        New broadcast
    </a>
</div>
<p class="text-sm text-slate-500 mt-1 mb-6">
    Sending happens in the background — reload this page to see progress on one still "Sending".
</p>

<div class="card overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/80 border-b border-slate-100">
            <tr>
                <th class="th-cell">Title</th>
                <th class="th-cell">Audience</th>
                <th class="th-cell">Status</th>
                <th class="th-cell">Sent / Failed</th>
                <th class="th-cell">By</th>
                <th class="th-cell">Created</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($broadcasts as $broadcast)
                <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="td-cell font-medium text-slate-900">{{ $broadcast->title }}</td>
                    <td class="td-cell text-slate-500">
                        @if (empty($broadcast->audience_filters))
                            Everyone
                        @else
                            {{ array_key_first($broadcast->audience_filters) }} = {{ reset($broadcast->audience_filters) }}
                        @endif
                        <span class="text-xs text-slate-400">(~{{ number_format($broadcast->recipient_estimate) }})</span>
                    </td>
                    <td class="td-cell">
                        @include('admin.partials.broadcast-status-badge', ['status' => $broadcast->status])
                    </td>
                    <td class="td-cell text-slate-500">{{ number_format($broadcast->sent_count) }} / {{ number_format($broadcast->failed_count) }}</td>
                    <td class="td-cell text-slate-500">{{ $broadcast->admin?->name ?? '—' }}</td>
                    <td class="td-cell text-slate-400">{{ $broadcast->created_at->format('Y-m-d H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center">
                        <svg class="size-8 mx-auto text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2 8.5 15 3v14L2 11.5v-3Zm0 0v3"/>
                        </svg>
                        <p class="mt-2 text-sm text-slate-400">No broadcasts sent yet.</p>
                        <a href="{{ route('admin.broadcasts.create') }}" class="btn-primary mt-4 inline-flex">New broadcast</a>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="mt-4">
    {{ $broadcasts->links() }}
</div>
@endsection
