<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
    <meta name="theme-color" content="#2563eb">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="Farmers">
    <link rel="manifest" href="/manifest.webmanifest">
    <link rel="icon" href="/icons/farmers.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/css/app.css">
    <title>@yield('title', 'Farmers') — Farmers</title>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark"><span>🌱</span> Farmers</div>
            <small>Farm Management System</small>
        </div>
        <nav class="side-nav">
            <div class="nav-label">Farm</div>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="nav-icon">⌂</span> Dashboard</a>
            <a href="{{ route('batches.index') }}" class="{{ request()->routeIs('batches.*') ? 'active' : '' }}"><span class="nav-icon">🐔</span> Batches</a>

            <div class="nav-label">Records</div>
            <a href="#"><span class="nav-icon">🗓</span> Daily Records</a>
            <a href="#"><span class="nav-icon">🌾</span> Feed</a>
            <a href="#"><span class="nav-icon">💉</span> Medication</a>
            <a href="#"><span class="nav-icon">📉</span> Mortality</a>
            <a href="#"><span class="nav-icon">🥚</span> Egg Production</a>
            <a href="#"><span class="nav-icon">💵</span> Sales</a>
            <a href="#"><span class="nav-icon">🧾</span> Expenses</a>
            <a href="#"><span class="nav-icon">📦</span> Inventory</a>
            <a href="{{ route('team.index') }}" class="{{ request()->routeIs('team.*') ? 'active' : '' }}"><span class="nav-icon">👥</span> Team</a>

            <div class="nav-label">Tools</div>
            <a href="#"><span class="nav-icon">📊</span> Performance</a>
            <a href="#"><span class="nav-icon">📄</span> Reports</a>
            <a href="{{ route('feature-requests.index') }}" class="{{ request()->routeIs('feature-requests.*') ? 'active' : '' }}"><span class="nav-icon">💡</span> Request Feature</a>
            <a href="{{ route('migration.static') }}" class="{{ request()->routeIs('migration.static*') ? 'active' : '' }}"><span class="nav-icon">⇪</span> Import PoultryPlus</a>
            <a href="{{ route('install-app') }}" class="{{ request()->routeIs('install-app') ? 'active' : '' }}"><span class="nav-icon">⬇</span> Install App</a>
        </nav>
        <div class="sidebar-footer">Powered by Ojenene.com<br>& Qazeem Basit Oladimeji</div>
    </aside>

    <header class="topbar">
        <div class="top-left">
            <h1>@yield('page_title', 'Farmers')</h1>
            <p>{{ $currentFarm?->name ?? 'Farm Management' }}</p>
        </div>
        <div class="top-actions">
            @if(isset($availableFarms) && $availableFarms->count())
            <form method="POST" action="{{ route('farms.switch') }}" class="farm-switcher">
                @csrf
                <span>🌱</span>
                <select name="farm_id" data-auto-submit>
                    @foreach($availableFarms as $farmOption)
                        <option value="{{ $farmOption->id }}" @selected($currentFarm?->id === $farmOption->id)>{{ $farmOption->name }}</option>
                    @endforeach
                </select>
            </form>
            @endif
            <a href="{{ route('install-app') }}" class="icon-btn" aria-label="Install Farmers">⬇</a>
            <span class="avatar" title="{{ auth()->user()->name }}">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
            <form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-btn" type="submit" title="Logout">↪</button></form>
        </div>
    </header>

    <header class="mobile-topbar">
        <div>
            <div class="mt-title">@yield('page_title', 'Farmers')</div>
            <div class="mt-farm">{{ $currentFarm?->name ?? 'Farmers' }}</div>
        </div>
        <div style="display:flex;gap:8px">
            <a class="icon-btn" href="{{ route('install-app') }}" aria-label="Install app">⬇</a>
            <span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span>
        </div>
    </header>

    <main class="content">
        @if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="flash flash-error">{{ $errors->first() }}</div>@endif
        @yield('content')
    </main>

    <div class="mobile-overlay" data-mobile-overlay></div>
    <div class="mobile-add-sheet" data-mobile-sheet>
        <div class="sheet-handle"></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <strong>Quick Add</strong><button class="icon-btn" type="button" data-close-sheet>×</button>
        </div>
        <div class="sheet-grid">
            <a class="sheet-item" href="#"><span>🗓</span>Daily</a>
            <a class="sheet-item" href="#"><span>🌾</span>Feed</a>
            <a class="sheet-item" href="#"><span>📉</span>Mortality</a>
            <a class="sheet-item" href="#"><span>🧾</span>Expense</a>
            <a class="sheet-item" href="#"><span>💵</span>Sale</a>
            <a class="sheet-item" href="#"><span>💉</span>Medication</a>
        </div>
    </div>

    <nav class="mobile-bottom">
        <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><span class="m-icon">⌂</span>Home</a>
        <a href="{{ route('batches.index') }}" class="{{ request()->routeIs('batches.*') ? 'active' : '' }}"><span class="m-icon">☷</span>Records</a>
        <button type="button" data-mobile-add><span class="add-button">＋</span><span>Add</span></button>
        <a href="#"><span class="m-icon">▥</span>Reports</a>
        <a href="{{ route('feature-requests.index') }}" class="{{ request()->routeIs('feature-requests.*','migration.*','install-app') ? 'active' : '' }}"><span class="m-icon">•••</span>More</a>
    </nav>
</div>
<script src="/js/app.js" defer></script>
@stack('scripts')
</body>
</html>
