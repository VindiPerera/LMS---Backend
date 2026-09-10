@extends('admin.layouts.app')

@section('title', 'Users')

@section('content')
<h1 class="text-2xl font-semibold text-slate-900">Users</h1>
<p class="text-sm text-slate-500 mt-1 mb-6">
    Real app users (Firebase Auth / Firestore) — only one filter applies at a time.
</p>

<form method="GET" action="{{ route('admin.users.index') }}" class="card-pad mb-5">
    <div class="flex flex-wrap items-end gap-4">
        <div class="min-w-48">
            <label class="field-label" for="search">Search</label>
            <input id="search" name="search" type="text" value="{{ $filters['search'] }}" placeholder="Name (prefix) or exact email"
                   class="field-input">
        </div>
        <div>
            <label class="field-label" for="role">Role</label>
            <select id="role" name="role" class="field-input">
                <option value="">All</option>
                <option value="student" @selected($filters['role'] === 'student')>Student</option>
                <option value="teacher" @selected($filters['role'] === 'teacher')>Teacher</option>
            </select>
        </div>
        <div>
            <label class="field-label" for="is_vip">VIP</label>
            <select id="is_vip" name="is_vip" class="field-input">
                <option value="">All</option>
                <option value="1" @selected($filters['is_vip'] === '1')>VIP only</option>
                <option value="0" @selected($filters['is_vip'] === '0')>Non-VIP only</option>
            </select>
        </div>
        <div class="min-w-40">
            <label class="field-label" for="native_lang">Native language</label>
            <input id="native_lang" name="native_lang" type="text" value="{{ $filters['native_lang'] }}" placeholder="e.g. English"
                   class="field-input">
        </div>
        <button type="submit" class="btn-primary">
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="m17.5 17.5-3.6-3.6m1.77-4.15a5.92 5.92 0 1 1-11.84 0 5.92 5.92 0 0 1 11.84 0Z"/>
            </svg>
            Filter
        </button>
        @if (collect($filters)->filter()->isNotEmpty())
            <a href="{{ route('admin.users.index') }}" class="btn-ghost">Clear</a>
        @endif
    </div>
</form>

<div class="card overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50/80 border-b border-slate-100">
            <tr>
                <th class="th-cell">Name</th>
                <th class="th-cell">Email</th>
                <th class="th-cell">Role</th>
                <th class="th-cell">VIP</th>
                <th class="th-cell">Languages</th>
                <th class="th-cell text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
                <tr class="hover:bg-slate-50/60 transition-colors">
                    <td class="td-cell">
                        <div class="flex items-center gap-3">
                            <div class="avatar-initial">{{ mb_substr($user['name'] ?? '?', 0, 1) }}</div>
                            <span class="font-medium text-slate-900">{{ $user['name'] ?? '(no name)' }}</span>
                        </div>
                    </td>
                    <td class="td-cell text-slate-500">{{ $user['email'] ?? '—' }}</td>
                    <td class="td-cell capitalize">{{ $user['role'] ?? '—' }}</td>
                    <td class="td-cell">
                        @if (($user['isVip'] ?? false))
                            <span class="badge-warning"><span class="badge-dot bg-amber-500"></span>VIP</span>
                        @endif
                    </td>
                    <td class="td-cell text-slate-500">
                        {{ $user['nativeLang'] ?? '?' }} &rarr; {{ $user['learningLang'] ?? '?' }}
                    </td>
                    <td class="td-cell text-right">
                        <a href="{{ route('admin.users.edit', $user['id']) }}" class="btn-secondary">Manage</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="px-5 py-14 text-center">
                        <svg class="size-8 mx-auto text-slate-300" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m17.5 17.5-3.6-3.6m1.77-4.15a5.92 5.92 0 1 1-11.84 0 5.92 5.92 0 0 1 11.84 0Z"/>
                        </svg>
                        <p class="mt-2 text-sm text-slate-400">No users match those filters.</p>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($nextCursor)
    <div class="mt-5">
        <a href="{{ route('admin.users.index', array_filter($filters) + ['after' => $nextCursor]) }}" class="btn-secondary">
            Next page
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5 13 10l-5.5 5.5"/>
            </svg>
        </a>
    </div>
@endif
@endsection
