@extends('layouts.app')
@section('title','Batches')
@section('page_title','Batches')
@section('content')
<div class="page-head"><div><h2>Batch Management</h2><p>Create and track Broiler, Layer and future production batches.</p></div></div>
<div class="grid two-col">
<div class="card">
<div class="card-header"><h3>Production batches</h3><span class="badge">{{ $batches->total() }} total</span></div>
<div class="table-wrap"><table class="data-table"><thead><tr><th>Batch</th><th>Type</th><th>Date In</th><th>Initial</th><th>Current</th><th>Status</th></tr></thead><tbody>
@forelse($batches as $batch)<tr><td><strong>{{ $batch->batch_number }}</strong><div class="hint">{{ $batch->breed }}</div></td><td>{{ ucfirst($batch->production_type) }}</td><td>{{ $batch->date_in->format('d M Y') }}</td><td>{{ number_format($batch->initial_birds) }}</td><td>{{ number_format($batch->current_birds) }}</td><td><span class="badge {{ $batch->status==='active'?'badge-success':'' }}">{{ ucfirst($batch->status) }}</span></td></tr>@empty<tr><td colspan="6"><div class="empty"><div class="emoji">🐔</div>No batches yet. Create your first production batch.</div></td></tr>@endforelse
</tbody></table></div>
<div class="mobile-menu-card card-body"><div class="mobile-records">@foreach($batches as $batch)<div class="mobile-record"><div class="mr-top"><div><h4>{{ $batch->batch_number }}</h4><p>{{ ucfirst($batch->production_type) }} · {{ $batch->breed ?: 'Breed not set' }}</p></div><span class="badge badge-success">{{ ucfirst($batch->status) }}</span></div><div class="mr-meta"><span>Initial: {{ number_format($batch->initial_birds) }}</span><span>Current: {{ number_format($batch->current_birds) }}</span><span>Date: {{ $batch->date_in->format('d M Y') }}</span></div></div>@endforeach</div></div>
</div>
<div class="card"><div class="card-header"><h3>＋ New batch</h3></div><div class="card-body">
<form method="POST" action="{{ route('batches.store') }}">@csrf
<div class="form-grid">
<div class="field"><label>Batch Number *</label><input class="input" name="batch_number" value="{{ old('batch_number') }}" placeholder="e.g. BR-001" required></div>
<div class="field"><label>Production Type *</label><select class="input" name="production_type" required><option value="broiler">Broiler</option><option value="layer">Layer</option><option value="cockerel">Cockerel</option><option value="noiler">Noiler</option><option value="other">Other</option></select></div>
<div class="field"><label>Date In *</label><input class="input" type="date" name="date_in" value="{{ old('date_in',now()->toDateString()) }}" required></div>
<div class="field"><label>Number of Birds *</label><input class="input" type="number" min="1" name="initial_birds" value="{{ old('initial_birds') }}" required></div>
<div class="field"><label>Breed</label><input class="input" name="breed" value="{{ old('breed') }}"></div>
<div class="field"><label>Purchase Cost</label><input class="input" type="number" min="0" step="0.01" name="purchase_cost" value="{{ old('purchase_cost',0) }}"></div>
<div class="field"><label>Supplier</label><input class="input" name="supplier" value="{{ old('supplier') }}"></div>
<div class="field"><label>Source</label><input class="input" name="source" value="{{ old('source') }}"></div>
<div class="field full"><label>Notes</label><textarea class="input" name="notes">{{ old('notes') }}</textarea></div>
</div>
<button class="btn btn-primary btn-block" style="margin-top:14px">Create Batch</button>
</form>
</div></div>
</div>
@endsection
