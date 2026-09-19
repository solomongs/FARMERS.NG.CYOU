<!DOCTYPE html>
<html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#172033"><link rel="stylesheet" href="/css/app.css"><title>@yield('title','Superuser') — Farmers</title></head>
<body>
<div class="app-shell">
<aside class="sidebar"><div class="brand"><div class="brand-mark"><span>⚙</span> Farmers</div><small>Superuser Control Center</small></div>
<nav class="side-nav"><div class="nav-label">Platform</div>
<a href="{{ route('superuser.dashboard') }}" class="{{ request()->routeIs('superuser.dashboard')?'active':'' }}"><span class="nav-icon">▦</span> Dashboard</a>
<a href="#"><span class="nav-icon">👥</span> Farmers</a><a href="#"><span class="nav-icon">🌱</span> Farms</a><a href="#"><span class="nav-icon">◫</span> Modules</a><a href="#"><span class="nav-icon">💡</span> Feature Requests</a>
<div class="nav-label">System</div><a href="#"><span class="nav-icon">☷</span> Audit Logs</a><a href="{{ route('superuser.mcp') }}" class="{{ request()->routeIs('superuser.mcp')?'active':'' }}"><span class="nav-icon">⌘</span> MCP</a><a href="#"><span class="nav-icon">⚙</span> Settings</a>
</nav><div class="sidebar-footer">Powered by Ojenene.com<br>& Qazeem Basit Oladimeji</div></aside>
<header class="topbar"><div class="top-left"><h1>@yield('page_title','Superuser')</h1><p>Platform administration</p></div><div class="top-actions"><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span><form method="POST" action="{{ route('logout') }}">@csrf<button class="icon-btn">↪</button></form></div></header>
<header class="mobile-topbar"><div><div class="mt-title">@yield('page_title','Superuser')</div><div class="mt-farm">Platform Control Center</div></div><span class="avatar">{{ strtoupper(substr(auth()->user()->name,0,1)) }}</span></header>
<main class="content">@if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif @if($errors->any())<div class="flash flash-error">{{ $errors->first() }}</div>@endif @yield('content')</main>
<nav class="mobile-bottom"><a href="{{ route('superuser.dashboard') }}" class="{{ request()->routeIs('superuser.dashboard')?'active':'' }}"><span class="m-icon">▦</span>Home</a><a href="#"><span class="m-icon">👥</span>Farmers</a><button type="button"><span class="add-button">⚙</span><span>Manage</span></button><a href="{{ route('superuser.mcp') }}" class="{{ request()->routeIs('superuser.mcp')?'active':'' }}"><span class="m-icon">⌘</span>MCP</a><a href="#"><span class="m-icon">•••</span>More</a></nav>
</div><script src="/js/app.js" defer></script></body></html>
