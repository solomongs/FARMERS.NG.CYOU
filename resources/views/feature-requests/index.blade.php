@extends('layouts.app')
@section('title','Feature Requests')
@section('page_title','Request a Feature')
@section('content')
<div class="page-head"><div><h2>Help shape Farmers</h2><p>Tell us what would make your farm management easier. Farmers is currently free to use.</p></div></div>
<div class="grid two-col">
<div class="card"><div class="card-header"><h3>Your requests</h3></div><div class="card-body">
<div class="mobile-records">
@forelse($requests as $item)<div class="mobile-record"><div class="mr-top"><div><h4>{{ $item->title }}</h4><p>{{ ucfirst(str_replace('_',' ',$item->category)) }}</p></div><span class="badge">{{ ucfirst(str_replace('_',' ',$item->status)) }}</span></div><p>{{ IlluminateSupportStr::limit($item->description,180) }}</p><div class="hint">{{ $item->created_at->format('d M Y') }} · {{ ucfirst($item->priority) }} priority</div></div>
@empty<div class="empty"><div class="emoji">💡</div>No feature requests submitted yet.</div>@endforelse
</div></div></div>
<div class="card"><div class="card-header"><h3>Submit request</h3></div><div class="card-body">
<form method="POST" action="{{ route('feature-requests.store') }}">@csrf
<div class="field"><label>Title *</label><input class="input" name="title" value="{{ old('title') }}" required></div>
<div class="field" style="margin-top:10px"><label>Category *</label><select class="input" name="category"><option value="records">Farm Records</option><option value="reports">Reports</option><option value="mobile">Mobile / PWA</option><option value="livestock">New Livestock</option><option value="integration">Integration</option><option value="general">General</option></select></div>
<div class="field" style="margin-top:10px"><label>What do you need? *</label><textarea class="input" name="description" required>{{ old('description') }}</textarea></div>
<div class="field" style="margin-top:10px"><label>Problem it solves</label><textarea class="input" name="problem">{{ old('problem') }}</textarea></div>
<div class="field" style="margin-top:10px"><label>Suggested solution</label><textarea class="input" name="suggested_solution">{{ old('suggested_solution') }}</textarea></div>
<div class="field" style="margin-top:10px"><label>Priority</label><select class="input" name="priority"><option value="normal">Normal</option><option value="low">Low</option><option value="high">High</option><option value="critical">Critical</option></select></div>
<label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;margin:12px 0"><input type="checkbox" name="contact_permission" value="1" checked> You may contact me for clarification about this request.</label>
<button class="btn btn-primary btn-block">Submit Feature Request</button>
</form>
</div></div>
</div>
@endsection
