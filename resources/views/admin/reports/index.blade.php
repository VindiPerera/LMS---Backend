@extends('admin.layouts.app')

@section('title', 'Reports')

@php
    // Mirrors ReportReason (Moments posts) + UserReportReason (voice-room
    // "Report a person") labels from lib/models/report_model.dart — kept in
    // sync manually since this is a display-only convenience, not something
    // either side reads back.
    $reasonLabels = [
        'spam' => 'Spam',
        'inappropriate' => 'Inappropriate content',
        'harassment' => 'Harassment or bullying',
        'languagesMismatch' => 'Languages used and set do not match',
        'fraud' => 'Attempted/committed fraud',
        'sexualContent' => 'Sent sexual content',
        'abusiveLanguage' => 'Used abusive language',
        'religiousPoliticalContent' => 'Sent religious/political content',
        'other' => 'Other',
    ];
    $statusBadge = [
        'pending' => ['badge-warning', 'bg-amber-500'],
        'reviewed' => ['badge-brand', 'bg-indigo-500'],
        'resolved' => ['badge-success', 'bg-emerald-500'],
        'dismissed' => ['badge-neutral', 'bg-slate-400'],
    ];
@endphp

@section('content')
<h1 class="text-2xl font-semibold text-slate-900">Reports</h1>
<p class="text-sm text-slate-500 mt-1 mb-6">
    User-submitted reports against a Moments post or directly against another person (e.g. a voice room's "Report" action).
</p>

<form method="GET" action="{{ route('admin.reports.index') }}" class="card-pad mb-5">
    <div class="flex flex-wrap items-end gap-4">
        <div>
            <label class="field-label" for="status">Status</label>
            <select id="status" name="status" class="field-input">
                <option value="">All</option>
                @foreach (\App\Services\FirestoreReportService::STATUSES as $s)
                    <option value="{{ $s }}" @selected($status === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="btn-primary">
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="m17.5 17.5-3.6-3.6m1.77-4.15a5.92 5.92 0 1 1-11.84 0 5.92 5.92 0 0 1 11.84 0Z"/>
            </svg>
            Filter
        </button>
        @if ($status !== '')
            <a href="{{ route('admin.reports.index') }}" class="btn-ghost">Clear</a>
        @endif
    </div>
</form>

<div class="card overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/80 border-b border-slate-100">
            <tr>
                <th class="th-cell">Reported user</th>
                <th class="th-cell">Reporter</th>
                <th class="th-cell">Reason</th>
                <th class="th-cell">Reported on</th>
                <th class="th-cell">Status</th>
                <th class="th-cell text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($reports as $report)
                @php
                    $reportedUser = $users[$report['reportedUserId'] ?? null] ?? null;
                    $reporterUser = $users[$report['reporterId'] ?? null] ?? null;
                @endphp
                <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="td-cell">
                        @if ($report['reportedUserId'] ?? null)
                            <a href="{{ route('admin.users.edit', $report['reportedUserId']) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                {{ $reportedUser['name'] ?? $report['reportedUserId'] }}
                            </a>
                        @else
                            <span class="text-slate-400">Post #{{ $report['postId'] ?? '—' }}</span>
                        @endif
                    </td>
                    <td class="td-cell text-slate-500">
                        @if ($report['reporterId'] ?? null)
                            <a href="{{ route('admin.users.edit', $report['reporterId']) }}" class="hover:text-indigo-600">
                                {{ $reporterUser['name'] ?? $report['reporterId'] }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="td-cell text-slate-500">
                        {{ $reasonLabels[$report['reason'] ?? ''] ?? ($report['reason'] ?? '—') }}
                        @if (!empty($report['details']))
                            <p class="text-xs text-slate-400 mt-0.5 max-w-xs truncate" title="{{ $report['details'] }}">{{ $report['details'] }}</p>
                        @endif
                    </td>
                    <td class="td-cell text-slate-500">
                        {{ \Illuminate\Support\Carbon::parse($report['createdAt'] ?? null)->format('Y-m-d H:i') }}
                    </td>
                    @php [$badgeClass, $dotClass] = $statusBadge[$report['status']] ?? ['badge-neutral', 'bg-slate-400']; @endphp
                    <td class="td-cell">
                        <span class="{{ $badgeClass }}">
                            <span class="badge-dot {{ $dotClass }}"></span>{{ ucfirst($report['status']) }}
                        </span>
                    </td>
                    <td class="td-cell text-right">
                        <div class="inline-flex items-center gap-2">
                            @if ($report['reportedUserId'] ?? null)
                                <a href="{{ route('admin.users.edit', $report['reportedUserId']) }}" class="btn-secondary">View User</a>
                            @endif
                            <form method="POST" action="{{ route('admin.reports.status', $report['id']) }}" class="inline-flex items-center gap-2">
                                @csrf
                                <select name="status" class="field-input py-1 text-xs" onchange="this.form.requestSubmit()">
                                    @foreach (\App\Services\FirestoreReportService::STATUSES as $s)
                                        <option value="{{ $s }}" @selected($report['status'] === $s)>{{ ucfirst($s) }}</option>
                                    @endforeach
                                </select>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center">
                        <svg class="size-8 mx-auto text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
                        </svg>
                        <p class="mt-2 text-sm text-slate-400">No reports match those filters.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($nextCursor)
    <div class="mt-5">
        <a href="{{ route('admin.reports.index', array_filter(['status' => $status]) + ['after' => $nextCursor]) }}" class="btn-secondary">
            Next page
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5 13 10l-5.5 5.5"/>
            </svg>
        </a>
    </div>
@endif
@endsection
