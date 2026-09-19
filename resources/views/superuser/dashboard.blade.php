@extends('layouts.superuser')
@section('title','Superuser Dashboard')
@section('page_title','Superuser Dashboard')
@section('content')
<div class="page-head"><div><h2>Farmers Platform</h2><p>Platform-wide control center. Farm tenant data remains isolated from ordinary users.</p></div></div>
<div class="grid stats">
<div class="stat-card"><div class="stat-label">Farmers</div><div class="stat-value">{{ number_format($farmers) }}</div></div>
<div class="stat-card"><div class="stat-label">Farm Workspaces</div><div class="stat-value">{{ number_format($farms) }}</div></div>
<div class="stat-card"><div class="stat-label">Enabled Modules</div><div class="stat-value">{{ number_format($modules) }}</div></div>
<div class="stat-card"><div class="stat-label">Feature Requests</div><div class="stat-value">{{ number_format($featureRequests) }}</div><div class="stat-sub">awaiting review</div></div>
</div>
<div class="grid two-col">
<div class="card"><div class="card-header"><h3>Platform controls</h3></div><div class="card-body"><div class="quick-grid"><a class="quick-action" href="#"><span class="qa-icon">👥</span>Farmers</a><a class="quick-action" href="#"><span class="qa-icon">◫</span>Modules</a><a class="quick-action" href="#"><span class="qa-icon">💡</span>Requests</a><a class="quick-action" href="{{ route('superuser.mcp') }}"><span class="qa-icon">⌘</span>MCP</a></div></div></div>
<div class="card"><div class="card-header"><h3>Build status</h3></div><div class="card-body"><div class="safe-box">Laravel SaaS foundation, installer, tenant model, authentication, PWA shell, batch module and PoultryPlus migration are active in this milestone.</div></div></div>
</div>
@endsection
