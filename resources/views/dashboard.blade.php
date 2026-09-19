@extends('layouts.app')
@section('title','Dashboard')
@section('page_title','Dashboard')
@section('content')
<div class="page-head">
<div><h2>Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 18 ? 'afternoon' : 'evening') }}, {{ explode(' ',auth()->user()->name)[0] }}</h2><p>Real-time overview for {{ $farm->name }}. Feed cost is sourced only from Feed Records.</p></div>
<div class="actions"><a href="{{ route('batches.index') }}" class="btn btn-primary">＋ New Batch</a><a href="{{ route('migration.static') }}" class="btn btn-secondary">⇪ Import old data</a></div>
</div>

<div class="grid stats">
<div class="stat-card"><div class="stat-label">Active Batches</div><div class="stat-value">{{ number_format($activeBatches) }}</div><div class="stat-sub">currently running</div></div>
<div class="stat-card"><div class="stat-label">Current Birds</div><div class="stat-value">{{ number_format($currentBirds) }}</div><div class="stat-sub">{{ number_format($initialBirds) }} initially stocked</div></div>
<div class="stat-card"><div class="stat-label">Mortality</div><div class="stat-value">{{ number_format($mortality) }}</div><div class="stat-sub">{{ $initialBirds > 0 ? number_format(($mortality/$initialBirds)*100,1) : '0.0' }}% mortality</div></div>
<div class="stat-card"><div class="stat-label">Feed Used</div><div class="stat-value">{{ number_format($feedUsed,1) }} kg</div><div class="stat-sub">₦{{ number_format($feedCost,2) }} feed cost</div></div>
<div class="stat-card"><div class="stat-label">Production Cost</div><div class="stat-value">₦{{ number_format($productionCost,2) }}</div><div class="stat-sub">feed + other expenses</div></div>
<div class="stat-card"><div class="stat-label">Revenue</div><div class="stat-value">₦{{ number_format($revenue,2) }}</div><div class="stat-sub">recorded sales</div></div>
<div class="stat-card"><div class="stat-label">Profit / Loss</div><div class="stat-value {{ $profit >= 0 ? 'positive':'negative' }}">₦{{ number_format($profit,2) }}</div><div class="stat-sub">current farm records</div></div>
<div class="stat-card"><div class="stat-label">Needs Attention</div><div class="stat-value">{{ $inventoryAlerts + $upcomingMedication }}</div><div class="stat-sub">{{ $inventoryAlerts }} stock · {{ $upcomingMedication }} medication</div></div>
</div>

<div class="card" style="margin-bottom:16px">
<div class="card-header"><h3>Quick actions</h3></div>
<div class="card-body"><div class="quick-grid">
<a class="quick-action" href="#"><span class="qa-icon">🗓</span>Daily Record</a>
<a class="quick-action" href="#"><span class="qa-icon">🌾</span>Feed</a>
<a class="quick-action" href="#"><span class="qa-icon">🧾</span>Expense</a>
<a class="quick-action" href="#"><span class="qa-icon">💵</span>Sale</a>
</div></div>
</div>

<div class="grid two-col">
<div class="card">
<div class="card-header"><h3>Recent batches</h3><a class="btn btn-secondary" href="{{ route('batches.index') }}">View all</a></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Batch</th><th>Type</th><th>Date In</th><th>Birds</th><th>Status</th></tr></thead><tbody>
@forelse($recentBatches as $batch)<tr><td><strong>{{ $batch->batch_number }}</strong></td><td>{{ ucfirst($batch->production_type) }}</td><td>{{ CarbonCarbon::parse($batch->date_in)->format('d M Y') }}</td><td>{{ number_format($batch->current_birds) }}</td><td><span class="badge badge-success">{{ ucfirst($batch->status) }}</span></td></tr>
@empty<tr><td colspan="5"><div class="empty">No batches yet.</div></td></tr>@endforelse
</tbody></table></div>
<div class="mobile-menu-card card-body"><div class="mobile-records">@forelse($recentBatches as $batch)<div class="mobile-record"><div class="mr-top"><div><h4>{{ $batch->batch_number }}</h4><p>{{ ucfirst($batch->production_type) }}</p></div><span class="badge badge-success">{{ ucfirst($batch->status) }}</span></div><div class="mr-meta"><span>Date: {{ CarbonCarbon::parse($batch->date_in)->format('d M Y') }}</span><span>Birds: {{ number_format($batch->current_birds) }}</span></div></div>@empty<div class="empty">No batches yet.</div>@endforelse</div></div>
</div>
<div class="card"><div class="card-header"><h3>Migration & setup</h3></div><div class="card-body">
<p style="font-size:13px;color:var(--muted);margin-top:0">Already used the static PoultryPlus app? Import its JSON backup into this farm.</p>
<a class="btn btn-primary btn-block" href="{{ route('migration.static') }}">Import PoultryPlus backup</a>
<a class="btn btn-secondary btn-block" style="margin-top:8px" href="{{ route('install-app') }}">Install Farmers app</a>
</div></div>
</div>
@endsection
