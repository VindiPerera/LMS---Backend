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
    Posts as a read-only chat message from "FaceTalk Company" to every matching user, plus a push notification
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
                <option value="specific_users" @selected(old('audience_type') === 'specific_users')>Specific users&hellip;</option>
            </select>

            <div id="audience-value-group" class="mt-4">
                <label class="field-label" for="audience_value">Value</label>
                <input id="audience_value" name="audience_value" type="text" value="{{ old('audience_value') }}"
                       placeholder="Ignored for &quot;Everyone&quot; — e.g. teacher, 1, English" class="field-input">
                <p class="field-hint">Only one filter can apply at a time — combining several isn't supported yet.</p>
            </div>

            <div id="recipient-picker-group" class="mt-4 hidden">
                <label class="field-label" for="recipient_search">Recipients</label>
                <input id="recipient_search" type="text" autocomplete="off" class="field-input" placeholder="Search by name or email&hellip;">
                <div id="recipient_results" class="hidden mt-1 max-h-56 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-sm divide-y divide-slate-100"></div>
                <div id="recipient_chips" class="mt-3 flex flex-wrap gap-2"></div>
                <p class="field-hint">Search and click a result to add them — you can add as many as you like.</p>
                <input type="hidden" id="recipients_json" name="recipients_json" value="{{ old('recipients_json') }}">
            </div>
        </div>

        <button type="submit" class="btn-primary">
            Preview recipients
            <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 4.5 13 10l-5.5 5.5"/>
            </svg>
        </button>
    </form>
</div>

<script>
(function () {
    var audienceType = document.getElementById('audience_type');
    var valueGroup = document.getElementById('audience-value-group');
    var pickerGroup = document.getElementById('recipient-picker-group');
    var searchInput = document.getElementById('recipient_search');
    var resultsBox = document.getElementById('recipient_results');
    var chipsBox = document.getElementById('recipient_chips');
    var hiddenField = document.getElementById('recipients_json');
    var searchUrl = @json(route('admin.broadcasts.search-recipients'));

    // Repopulate a previously-selected recipient list after a validation
    // error redirects back here (old('recipients_json') on the hidden
    // field) — otherwise the admin would have to re-search and re-pick
    // everyone from scratch just to fix, say, a typo in the title.
    var selected = [];
    try {
        var initial = JSON.parse(hiddenField.value || '[]');
        if (Array.isArray(initial)) selected = initial;
    } catch (e) { /* ignore malformed old input, start empty */ }

    function toggleAudienceUI() {
        var isSpecific = audienceType.value === 'specific_users';
        valueGroup.classList.toggle('hidden', isSpecific);
        pickerGroup.classList.toggle('hidden', !isSpecific);
        document.getElementById('audience_value').required = false;
    }

    function syncHiddenField() {
        hiddenField.value = JSON.stringify(selected);
    }

    function renderChips() {
        chipsBox.innerHTML = '';
        selected.forEach(function (user) {
            var chip = document.createElement('span');
            chip.className = 'inline-flex items-center gap-1.5 rounded-full bg-indigo-50 text-indigo-700 text-sm pl-3 pr-2 py-1';
            var label = document.createElement('span');
            label.textContent = user.name;
            chip.appendChild(label);
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'text-indigo-400 hover:text-indigo-700 leading-none';
            remove.setAttribute('aria-label', 'Remove ' + user.name);
            remove.textContent = '×';
            remove.addEventListener('click', function () {
                selected = selected.filter(function (u) { return u.uid !== user.uid; });
                syncHiddenField();
                renderChips();
            });
            chip.appendChild(remove);
            chipsBox.appendChild(chip);
        });
    }

    function addRecipient(user) {
        if (selected.some(function (u) { return u.uid === user.uid; })) return;
        selected.push({ uid: user.uid, name: user.name });
        syncHiddenField();
        renderChips();
        searchInput.value = '';
        resultsBox.classList.add('hidden');
        resultsBox.innerHTML = '';
    }

    function renderResults(users) {
        resultsBox.innerHTML = '';
        if (!users.length) {
            resultsBox.classList.add('hidden');
            return;
        }
        users.forEach(function (user) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'w-full text-left px-3 py-2 text-sm hover:bg-slate-50 flex flex-col';
            var name = document.createElement('span');
            name.className = 'font-medium text-slate-900';
            name.textContent = user.name;
            row.appendChild(name);
            if (user.email) {
                var email = document.createElement('span');
                email.className = 'text-xs text-slate-500';
                email.textContent = user.email;
                row.appendChild(email);
            }
            row.addEventListener('click', function () { addRecipient(user); });
            resultsBox.appendChild(row);
        });
        resultsBox.classList.remove('hidden');
    }

    var debounceTimer = null;
    searchInput.addEventListener('input', function () {
        var q = searchInput.value.trim();
        clearTimeout(debounceTimer);
        if (q === '') {
            resultsBox.classList.add('hidden');
            resultsBox.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(function () {
            fetch(searchUrl + '?q=' + encodeURIComponent(q), { headers: { Accept: 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) { renderResults(data.users || []); })
                .catch(function () { /* transient network hiccup — next keystroke retries */ });
        }, 250);
    });

    audienceType.addEventListener('change', toggleAudienceUI);
    toggleAudienceUI();
    renderChips();
    syncHiddenField();
})();
</script>
@endsection
