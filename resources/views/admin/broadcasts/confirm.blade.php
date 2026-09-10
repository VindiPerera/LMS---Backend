@extends('admin.layouts.app')

@section('title', 'Confirm broadcast')

@section('content')
<a href="{{ route('admin.broadcasts.create') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 mb-4">
    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 15.5 7 10l5.5-5.5"/>
    </svg>
    Edit
</a>

<h1 class="text-2xl font-semibold text-slate-900 mb-6">Confirm broadcast</h1>

<div class="card-pad max-w-xl space-y-5">
    <div class="rounded-lg bg-slate-50 p-4 space-y-3">
        <div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-0.5">Title</div>
            <div class="text-sm font-medium text-slate-900">{{ $data['title'] }}</div>
        </div>
        <div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-0.5">Message</div>
            <div class="text-sm text-slate-700 whitespace-pre-wrap">{{ $data['body'] }}</div>
        </div>
        <div>
            <div class="text-xs font-medium text-slate-400 uppercase tracking-wide mb-0.5">Audience</div>
            <div class="text-sm text-slate-700">
                @if (empty($filters))
                    Everyone with a saved push token
                @else
                    {{ array_key_first($filters) }} = {{ reset($filters) }}
                @endif
            </div>
        </div>
    </div>

    <div class="alert-warning">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
        </svg>
        <span>
            <strong>~{{ number_format($recipientEstimate) }}</strong> matching users will get this as a chat
            message from FaceTalk (read-only — they can't reply). Whichever of them also have a saved push
            token get a notification too. This is an estimate (matches Firestore's profile data, at the
            moment you clicked preview) — sending can't be undone once it starts.
        </span>
    </div>

    <form method="POST" action="{{ route('admin.broadcasts.store') }}"
          onsubmit="return confirm('Send this to ~{{ number_format($recipientEstimate) }} devices now?')">
        @csrf
        <input type="hidden" name="title" value="{{ $data['title'] }}">
        <input type="hidden" name="body" value="{{ $data['body'] }}">
        <input type="hidden" name="audience_type" value="{{ $data['audience_type'] }}">
        <input type="hidden" name="audience_value" value="{{ $data['audience_value'] ?? '' }}">
        <button type="submit" class="btn-danger">
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2 8.5 15 3v14L2 11.5v-3Zm0 0v3"/>
            </svg>
            Confirm &amp; send
        </button>
    </form>
</div>
@endsection
