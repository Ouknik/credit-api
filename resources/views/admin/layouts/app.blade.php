<!DOCTYPE html>
<html lang="fr" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') — Kridi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: { DEFAULT: '#0CF232', dark: '#09C72A' },
                        navy: '#0B1E2D',
                        success: '#16A34A',
                        warning: '#F59E0B',
                        danger: '#DC2626',
                    },
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                }
            }
        }
    </script>
    <style>
        [x-cloak] { display: none !important; }
    </style>
    @stack('head')
</head>
<body class="bg-gray-50 font-sans text-gray-800 min-h-screen">

<div class="flex min-h-screen">
    {{-- ═══ Sidebar ═══ --}}
    <aside id="sidebar" class="hidden lg:flex lg:flex-col w-64 bg-navy text-white fixed inset-y-0 z-30 transition-transform">
        {{-- Logo --}}
        <div class="flex items-center gap-3 px-6 py-5 border-b border-white/10">
            <div class="w-9 h-9 rounded-lg bg-brand flex items-center justify-center">
                <svg class="w-5 h-5 text-navy" fill="currentColor" viewBox="0 0 24 24"><path d="M21 18v1a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v1"/><path d="M16 12H3m13 0-4-4m4 4-4 4"/></svg>
            </div>
            <div>
                <h1 class="text-lg font-bold tracking-tight">Kridi</h1>
                <p class="text-xs text-gray-400">Admin Panel</p>
            </div>
        </div>

        {{-- Navigation --}}
        <nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
            <a href="{{ route('admin.dashboard') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.dashboard') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                Dashboard
            </a>
            <a href="{{ route('admin.shops.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.shops.*') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z"/></svg>
                Boutiques
            </a>
            <a href="{{ route('admin.customers.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.customers.*') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                Clients
            </a>
            <a href="{{ route('admin.reports.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.reports.*') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                Rapports
            </a>
            <a href="{{ route('admin.wallet.summary') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.wallet.*') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3"/></svg>
                Portefeuille
            </a>
            <a href="{{ route('admin.gateway.index') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.gateway.*') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z"/></svg>
                Gateway
            </a>
            <a href="{{ route('admin.products.import.form') }}"
               class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                      {{ request()->routeIs('admin.products.import.*') ? 'bg-brand/20 text-brand' : 'text-gray-300 hover:bg-white/5 hover:text-white' }}">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5A3.375 3.375 0 0010.125 2.25H6.75A2.25 2.25 0 004.5 4.5v15A2.25 2.25 0 006.75 21.75h10.5a2.25 2.25 0 002.25-2.25V14.25z"/><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 2.25V6a2.25 2.25 0 002.25 2.25h3.75M12 18v-6m0 0-2.25 2.25M12 12l2.25 2.25"/></svg>
                Import Produits
            </a>
        </nav>

        {{-- User --}}
        <div class="px-4 py-4 border-t border-white/10">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-brand/30 flex items-center justify-center text-brand font-bold text-sm">
                    {{ strtoupper(substr(auth('web')->user()->name ?? 'A', 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium truncate">{{ auth('web')->user()->name ?? 'Admin' }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth('web')->user()->email ?? '' }}</p>
                </div>
            </div>
        </div>
    </aside>

    {{-- ═══ Main Content ═══ --}}
    <div class="flex-1 lg:ml-64">
        {{-- Top Bar --}}
        <header class="sticky top-0 z-20 bg-white border-b border-gray-200">
            <div class="flex items-center justify-between px-4 sm:px-6 h-16">
                {{-- Mobile menu toggle --}}
                <button onclick="document.getElementById('sidebar').classList.toggle('hidden');document.getElementById('sidebar').classList.toggle('lg:flex')"
                        class="lg:hidden p-2 rounded-lg hover:bg-gray-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5"/></svg>
                </button>

                <h2 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Dashboard')</h2>

                <div class="flex items-center gap-3">
                    <span class="text-sm text-gray-500 hidden sm:block">{{ now()->format('d/m/Y H:i') }}</span>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="flex items-center gap-2 text-sm text-gray-500 hover:text-danger transition px-3 py-2 rounded-lg hover:bg-red-50">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                            Déconnexion
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 bg-green-50 border border-green-200 text-green-800 rounded-lg text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if(session('error'))
            <div class="mx-4 sm:mx-6 mt-4 px-4 py-3 bg-red-50 border border-red-200 text-red-800 rounded-lg text-sm">
                {{ session('error') }}
            </div>
        @endif

        {{-- Page Content --}}
        <main class="p-4 sm:p-6">
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
