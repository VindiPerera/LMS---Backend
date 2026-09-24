@if (session('status'))
    <div class="alert-success mb-5">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5 8.5 14 15 6.5"/>
        </svg>
        <span>{{ session('status') }}</span>
    </div>
@endif

@if (session('error'))
    <div class="alert-danger mb-5">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M10 6.5v4.25M10 13.75h.008M2.5 10a7.5 7.5 0 1 1 15 0 7.5 7.5 0 0 1-15 0Z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
@endif
