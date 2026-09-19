@extends('layouts.app')
@section('title','Team')
@section('page_title','Team')
@section('content')
<div class="page-head"><div><h2>Farm Team</h2><p>Control who can access {{ $farm->name }} and revoke access when a staff member leaves.</p></div></div>
<div class="grid two-col">
<div class="card"><div class="card-header"><h3>Members</h3><span class="badge">{{ $members->count() }}</span></div><div class="card-body">
<div class="mobile-records">
@forelse($members as $member)
<div class="mobile-record"><div class="mr-top"><div><h4>{{ $member->user->name }}</h4><p>{{ $member->user->email }} · {{ $member->role?->name ?? 'Staff' }}</p></div><span class="badge {{ $member->status==='active'?'badge-success':'badge-danger' }}">{{ ucfirst($member->status) }}</span></div>
@if($member->user_id !== $farm->owner_id)
<form method="POST" action="{{ route('team.status',$member) }}">@csrf @method('PATCH')
<input type="hidden" name="status" value="{{ $member->status==='active'?'inactive':'active' }}">
<button class="btn {{ $member->status==='active'?'btn-danger':'btn-success' }}" type="submit">{{ $member->status==='active'?'Revoke access':'Restore access' }}</button>
</form>
@else<span class="hint">Farm owner</span>@endif
</div>
@empty<div class="empty">No team members yet.</div>@endforelse
</div></div></div>
<div class="card"><div class="card-header"><h3>Invite staff</h3></div><div class="card-body">
<form method="POST" action="{{ route('team.invite') }}">@csrf
<div class="field"><label>Name</label><input class="input" name="name" value="{{ old('name') }}"></div>
<div class="field" style="margin-top:10px"><label>Email *</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
<div class="field" style="margin-top:10px"><label>Role *</label><select class="input" name="role_id" required>@foreach($roles as $role)<option value="{{ $role->id }}">{{ $role->name }}</option>@endforeach</select></div>
<button class="btn btn-primary btn-block" style="margin-top:14px">Send Invitation</button>
</form>
<div class="divider"></div><h3 style="font-size:14px">Recent invitations</h3>
@forelse($invitations as $invite)<div class="mobile-record" style="margin-top:8px"><div class="mr-top"><div><h4>{{ $invite->email }}</h4><p>{{ $invite->role?->name }}</p></div><span class="badge {{ $invite->accepted_at?'badge-success':($invite->cancelled_at?'badge-danger':'badge-warning') }}">{{ $invite->accepted_at?'Accepted':($invite->cancelled_at?'Cancelled':'Pending') }}</span></div>
@if(!$invite->accepted_at && !$invite->cancelled_at)<form method="POST" action="{{ route('team.invitation.cancel',$invite) }}">@csrf @method('DELETE')<button class="btn btn-secondary">Cancel</button></form>@endif
</div>@empty<p class="hint">No invitations sent yet.</p>@endforelse
</div></div>
</div>
@endsection
