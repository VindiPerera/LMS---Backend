@extends('admin.layouts.app')

@section('title', 'New broadcast')

@section('content')
<a href="{{ route('admin.broadcasts.index') }}" class="inline-flex items-center gap-1 text-sm text-slate-500 hover:text-slate-900 mb-4">
    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12.5 15.5 7 10l5.5-5.5"/>
    </svg>
    Back to broadcasts
</a>

<h1 class="text-2xl font-semibold text-slate-900">New broadcast</h1>
<p class="text-sm text-slate-500 mt-1 mb-6">
    Posts as a read-only chat message from "FaceTalk" to every matching user, plus a push notification
    to whichever of them have a saved device token.
</p>

<div class="card-pad max-w-xl">
    @include('admin.partials.errors')

    <form method="POST" action="{{ route('admin.broadcasts.preview') }}" class="space-y-5">
        @csrf
        <div>
            <label class="field-label" for="title">Title</label>
            <input id="title" name="title" type="text" value="{{ old('title') }}" required maxlength="255" class="field-input">
        </div>
        <div>
            <label class="field-label" for="body">Message</label>
            <textarea id="body" name="body" rows="4" required maxlength="1000" class="field-input">{{ old('body') }}</textarea>
        </div>

        <div class="pt-2 border-t border-slate-100">
            <label class="field-label" for="audience_type">Audience</label>
            <select id="audience_type" name="audience_type" class="field-input">
                <option value="everyone" @selected(old('audience_type', 'everyone') === 'everyone')>Everyone (every device with a saved push token)</option>
                <option value="role" @selected(old('audience_type') === 'role')>Role equals&hellip;</option>
                <option value="is_vip" @selected(old('audience_type') === 'is_vip')>VIP equals&hellip; (1 = VIP, 0 = non-VIP)</option>
                <option value="native_lang" @selected(old('audience_type') === 'native_lang')>Native language equals&hellip;</option>
                <option value="learning_lang" @selected(old('audience_type') === 'learning_lang')>Learning language equals&hellip;</option>
            </select>

            <label class="field-label mt-4" for="audience_value">Value</label>
            <input id="audience_value" name="audience_value" type="text" value="{{ old('audience_value') }}"
                   placeholder="Ignored for &quot;Everyone&quot; — e.g. teacher, 1, English" class="field-input">
            <p class="field-hint">Only one filter can apply at a time — combining several isn't supported yet.</p>
        </div>

        <button type="submit" class="btn-primary">
            Preview recipients
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5 13 10l-5.5 5.5"/>
            </svg>
        </button>
    </form>
</div>
@endsection
