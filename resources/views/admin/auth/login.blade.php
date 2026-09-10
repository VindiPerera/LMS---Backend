@extends('admin.layouts.app')

@section('title', 'Log in')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 bg-gradient-to-b from-slate-50 to-slate-100">
    <div class="w-full max-w-sm">
        <div class="flex flex-col items-center mb-6">
            <div class="flex size-11 items-center justify-center rounded-xl bg-slate-900 text-white font-bold text-lg shadow-sm">H</div>
            <h1 class="mt-3 text-lg font-semibold text-slate-900">Hello Admin</h1>
            <p class="text-sm text-slate-500">Sign in to manage users and broadcasts</p>
        </div>

        <div class="card-pad">
            @include('admin.partials.errors')

            <form method="POST" action="{{ route('admin.login') }}" class="space-y-4">
                @csrf
                <div>
                    <label class="field-label" for="email">Email</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                           class="field-input" placeholder="you@example.com">
                </div>
                <div>
                    <label class="field-label" for="password">Password</label>
                    <input id="password" name="password" type="password" required
                           class="field-input" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;">
                </div>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="remember" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500/30">
                    Remember me
                </label>
                <button type="submit" class="btn-primary w-full">
                    Log in
                </button>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-400">Admin accounts are created from the CLI — there's no self-registration.</p>
    </div>
</div>
@endsection
