@php
    $variant = match ($status) {
        'completed' => 'badge-success',
        'sending' => 'badge-warning',
        'failed' => 'badge-danger',
        default => 'badge-neutral',
    };
    $dot = match ($status) {
        'completed' => 'bg-emerald-500',
        'sending' => 'bg-amber-500 animate-pulse',
        'failed' => 'bg-rose-500',
        default => 'bg-slate-400',
    };
@endphp
<span class="{{ $variant }} capitalize">
    <span class="badge-dot {{ $dot }}"></span>
    {{ $status }}
</span>
