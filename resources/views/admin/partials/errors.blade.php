@if ($errors->any())
    <div class="alert-danger mb-4">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
        </svg>
        <ul class="space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
