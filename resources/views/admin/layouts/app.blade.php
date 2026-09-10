<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Admin') &middot; Hello Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-900 antialiased">
@auth('admin')
    <div class="min-h-screen flex">
        <aside class="w-60 shrink-0 bg-slate-900 text-slate-100 flex flex-col">
            <div class="flex items-center gap-2.5 px-5 py-5">
                <div class="flex size-8 items-center justify-center rounded-lg bg-indigo-500 text-white font-bold text-sm">H</div>
                <div class="leading-tight">
                    <div class="text-sm font-semibold text-white">Hello Admin</div>
                    <div class="text-xs text-slate-400">Control panel</div>
                </div>
            </div>

            <nav class="flex-1 px-3 py-2 space-y-1">
                <a href="{{ route('admin.dashboard') }}"
                   class="nav-link {{ request()->routeIs('admin.dashboard') ? 'nav-link-active' : '' }}">
                    <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h3.5V17H3v-3.5Zm5.25-5H11.75V17H8.25V8.5ZM13.5 4H17v13h-3.5V4Z"/>
                    </svg>
                    Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}"
                   class="nav-link {{ request()->routeIs('admin.users.*') ? 'nav-link-active' : '' }}">
                    <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 17v-1.5a3.5 3.5 0 0 0-3.5-3.5H5.5A3.5 3.5 0 0 0 2 15.5V17m15 0v-1.5a3.5 3.5 0 0 0-2.5-3.35M11.5 3.16a3.5 3.5 0 0 1 0 6.68M9.75 6.5a3.25 3.25 0 1 1-6.5 0 3.25 3.25 0 0 1 6.5 0Z"/>
                    </svg>
                    Users
                </a>
                <a href="{{ route('admin.broadcasts.index') }}"
                   class="nav-link {{ request()->routeIs('admin.broadcasts.*') ? 'nav-link-active' : '' }}">
                    <svg class="size-5 shrink-0" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2 8.5 15 3v14L2 11.5v-3Zm0 0v3M6 12v3a1.5 1.5 0 0 0 3 0v-2M15 7.5a2.5 2.5 0 0 1 0 5"/>
                    </svg>
                    Broadcasts
                </a>
            </nav>

            <div class="border-t border-white/10 p-3">
                <div class="flex items-center gap-2.5 rounded-lg px-2 py-2">
                    <div class="flex size-8 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-indigo-300 text-xs font-semibold uppercase">
                        {{ strtoupper(substr(auth('admin')->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1 leading-tight">
                        <div class="truncate text-sm font-medium text-white">{{ auth('admin')->user()->name }}</div>
                        <div class="truncate text-xs text-slate-400">Administrator</div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" title="Log out" aria-label="Log out"
                                class="flex size-8 items-center justify-center rounded-lg text-slate-400 hover:bg-white/10 hover:text-white transition-colors">
                            <svg class="size-4.5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 17.5H4.75A1.75 1.75 0 0 1 3 15.75V4.25A1.75 1.75 0 0 1 4.75 2.5H7.5M13 14l4-4-4-4M17 10H7.5"/>
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex-1 flex flex-col min-w-0">
            <main class="flex-1 px-8 py-7 max-w-6xl w-full mx-auto">
                @include('admin.partials.flash')
                @yield('content')
            </main>
        </div>
    </div>
@else
    @yield('content')
@endauth
</body>
</html>
