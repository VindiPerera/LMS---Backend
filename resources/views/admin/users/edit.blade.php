@extends('admin.layouts.app')

@section('title', $profile['name'] ?? $uid)

@php
    // Same label map as admin/reports/index.blade.php — kept in sync
    // manually with lib/models/report_model.dart's ReportReason/UserReportReason.
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
@endphp

@section('content')
<a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 mb-4">
    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 15.5 7 10l5.5-5.5"/>
    </svg>
    Back to users
</a>

<div class="flex items-center gap-3 mb-1">
    <div class="avatar-initial size-10 text-sm">{{ mb_substr($profile['name'] ?? '?', 0, 1) }}</div>
    <h1 class="text-2xl font-semibold text-slate-900">{{ $profile['name'] ?? '(no name)' }}</h1>
    @if ($authRecord?->disabled)
        <span class="badge-danger"><span class="badge-dot bg-rose-500"></span>Banned</span>
    @endif
</div>
<p class="text-xs text-slate-400 font-mono mb-6 ml-13">{{ $uid }}</p>

@if ($authRecord?->disabled && !empty($profile['banReason']))
    <div class="alert-danger mb-5">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
        </svg>
        <span>Ban reason: {{ $profile['banReason'] }}</span>
    </div>
@endif

@unless ($authRecord)
    <div class="alert-warning mb-5">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
        </svg>
        <span>No matching Firebase Auth account (has a Firestore profile but the auth record is missing/deleted) —
            ban, force-logout and password reset aren't available for this user.</span>
    </div>
@endunless

<div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
    <div class="lg:col-span-2 card-pad">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Profile</h2>

        @include('admin.partials.errors')

        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-5 p-3 rounded-lg bg-slate-50">
            <dt class="text-slate-400">Email</dt>
            <dd class="text-slate-700">{{ $authRecord?->email ?? $profile['email'] ?? '—' }}</dd>
            <dt class="text-slate-400">Joined</dt>
            <dd class="text-slate-700">{{ $authRecord?->metadata->createdAt->format('Y-m-d') ?? '—' }}</dd>
            <dt class="text-slate-400">Last sign-in</dt>
            <dd class="text-slate-700">{{ $authRecord?->metadata->lastLoginAt?->format('Y-m-d H:i') ?? '—' }}</dd>
        </dl>

        <form method="POST" action="{{ route('admin.users.update', $uid) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="field-label" for="name">Name</label>
                <input id="name" name="name" type="text" value="{{ old('name', $profile['name'] ?? '') }}" required class="field-input">
            </div>
            <div>
                <label class="field-label" for="role">Role</label>
                <select id="role" name="role" class="field-input">
                    <option value="student" @selected(old('role', $profile['role'] ?? 'student') === 'student')>Student</option>
                    <option value="teacher" @selected(old('role', $profile['role'] ?? '') === 'teacher')>Teacher</option>
                </select>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="field-label" for="native_lang">Native language</label>
                    <input id="native_lang" name="native_lang" type="text" value="{{ old('native_lang', $profile['nativeLang'] ?? '') }}" class="field-input">
                </div>
                <div>
                    <label class="field-label" for="learning_lang">Learning language</label>
                    <input id="learning_lang" name="learning_lang" type="text" value="{{ old('learning_lang', $profile['learningLang'] ?? '') }}" class="field-input">
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="is_vip" value="1" @checked(old('is_vip', $profile['isVip'] ?? false)) class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30">
                VIP
            </label>
            <button type="submit" class="btn-primary">Save changes</button>
        </form>
    </div>

    @if ($authRecord)
        <div class="card-pad space-y-3 h-fit">
            <h2 class="text-sm font-semibold text-slate-900 mb-1">Actions</h2>

            <form method="POST" action="{{ route('admin.users.reset-password', $uid) }}"
                  onsubmit="return confirm('Send a password reset email to {{ $authRecord->email }}?')">
                @csrf
                <button type="submit" class="btn-secondary w-full justify-start">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 6.5 10 11l7-4.5M3.5 4.5h13a.5.5 0 0 1 .5.5v10a.5.5 0 0 1-.5.5h-13a.5.5 0 0 1-.5-.5V5a.5.5 0 0 1 .5-.5Z"/>
                    </svg>
                    Send password reset email
                </button>
            </form>

            <form method="POST" action="{{ route('admin.users.force-logout', $uid) }}"
                  onsubmit="return confirm('Sign this user out on every device?')">
                @csrf
                <button type="submit" class="btn-secondary w-full justify-start">
                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 17.5H4.75A1.75 1.75 0 0 1 3 15.75V4.25A1.75 1.75 0 0 1 4.75 2.5H7.5M13 14l4-4-4-4M17 10H7.5"/>
                    </svg>
                    Force logout (all devices)
                </button>
            </form>

            <div class="pt-2 border-t border-slate-100">
                @if ($authRecord->disabled)
                    <form method="POST" action="{{ route('admin.users.unban', $uid) }}" onsubmit="return confirm('Unban this user?')">
                        @csrf
                        <button type="submit" class="btn-success w-full">Unban user</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.users.ban', $uid) }}"
                          onsubmit="return confirm('Ban this user? This immediately signs them out everywhere.')"
                          class="space-y-2">
                        @csrf
                        <label class="field-label" for="ban_reason">Ban reason <span class="text-slate-400 font-normal">(internal only)</span></label>
                        <input id="ban_reason" name="ban_reason" type="text" class="field-input">
                        <button type="submit" class="btn-danger w-full">Ban user</button>
                    </form>
                @endif
            </div>

            <div class="pt-2 border-t border-slate-100 space-y-2">
                <label class="field-label" for="warn_message">Send warning message</label>
                <form method="POST" action="{{ route('admin.users.warn', $uid) }}"
                      onsubmit="return confirm('Send this warning to the user\'s chat?')" class="space-y-2">
                    @csrf
                    <textarea id="warn_message" name="message" rows="2" required maxlength="1000"
                              placeholder="e.g. Please follow the community guidelines — further reports may lead to account action."
                              class="field-input"></textarea>
                    <button type="submit" class="btn-secondary w-full justify-start">
                        <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
                        </svg>
                        Send warning to chat
                    </button>
                </form>
            </div>

            <div class="pt-2 border-t border-slate-100 space-y-2">
                <form method="POST" action="{{ route('admin.users.destroy', $uid) }}"
                      onsubmit="return confirm('This permanently deletes the account and cannot be undone. Continue?')"
                      class="space-y-2">
                    @csrf
                    @method('DELETE')
                    <label class="field-label" for="confirm_name">Delete account permanently</label>
                    <p class="field-hint -mt-1">Type the user's name (<span class="font-medium">{{ $profile['name'] ?? '' }}</span>) to confirm. This cannot be undone.</p>
                    <input id="confirm_name" name="confirm_name" type="text" required class="field-input">
                    <button type="submit" class="btn-danger w-full">Delete account permanently</button>
                </form>
            </div>
        </div>
    @endif
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-5 mt-5">
    <div class="card-pad">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-slate-900">Reports about this user</h2>
            <span class="badge-neutral">{{ $reportCount }} total</span>
        </div>
        @forelse ($reports as $report)
            <div class="py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-slate-900">{{ ucfirst($report['status']) }}</span>
                    <span class="text-xs text-slate-400">{{ \Illuminate\Support\Carbon::parse($report['createdAt'] ?? null)->format('Y-m-d H:i') }}</span>
                </div>
                <p class="text-sm text-slate-600 mt-0.5">{{ $reasonLabels[$report['reason'] ?? ''] ?? ($report['reason'] ?? '—') }}</p>
                @if (!empty($report['details']))
                    <p class="text-xs text-slate-400 mt-0.5">{{ $report['details'] }}</p>
                @endif
                <p class="text-xs text-slate-400 mt-0.5">Reported by {{ $report['reporterId'] ?? '—' }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-400">No reports on file for this user.</p>
        @endforelse
        @if (count($reports) > 0)
            <a href="{{ route('admin.reports.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 mt-3 inline-block">Manage statuses in Reports &rarr;</a>
        @endif
    </div>

    <div class="card-pad">
        <h2 class="text-sm font-semibold text-slate-900 mb-4">Moderation history</h2>
        @forelse ($moderationHistory as $entry)
            <div class="py-2.5 {{ !$loop->last ? 'border-b border-slate-100' : '' }}">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm font-medium text-slate-900">{{ str_replace('_', ' ', str_replace('user.', '', $entry->action)) }}</span>
                    <span class="text-xs text-slate-400">{{ $entry->created_at->format('Y-m-d H:i') }}</span>
                </div>
                <p class="text-xs text-slate-400 mt-0.5">by {{ $entry->admin?->name ?? 'Unknown admin' }}</p>
            </div>
        @empty
            <p class="text-sm text-slate-400">No moderation actions recorded for this user.</p>
        @endforelse
    </div>
</div>
@endsection
