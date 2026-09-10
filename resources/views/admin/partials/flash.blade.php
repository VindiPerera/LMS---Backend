@if (session('status'))
    <div class="alert-success mb-5">
        <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10.5 8.5 14 15 6.5"/>
        </svg>
        <span>{{ session('status') }}</span>
    </div>
@endif
