@extends('layouts.auth')
@section('title','Join '.$invitation->farm->name.' — Farmers')
@section('content')
<div class="auth-card">
<h1>Join {{ $invitation->farm->name }}</h1>
<p class="sub">{{ $invitation->inviter->name }} invited <strong>{{ $invitation->email }}</strong> to join as {{ $invitation->role?->name ?? 'Staff' }}.</p>
<div class="safe-box">This invitation expires {{ $invitation->expires_at->diffForHumans() }}.</div>
<form method="POST" action="{{ route('invitations.accept',$token) }}" style="margin-top:14px">@csrf
@if($existingUser)
<div class="field"><label>Password for {{ $invitation->email }}</label><input class="input" type="password" name="password" required autocomplete="current-password"></div>
<p class="hint">This email already has a Farmers account. Enter its password to accept the invitation.</p>
@else
<div class="field"><label>Your name</label><input class="input" name="name" value="{{ old('name',$invitation->name) }}" required></div>
<div class="field" style="margin-top:10px"><label>Create password</label><input class="input" type="password" name="password" minlength="8" required></div>
<div class="field" style="margin-top:10px"><label>Confirm password</label><input class="input" type="password" name="password_confirmation" minlength="8" required></div>
@endif
<button class="btn btn-primary btn-block" style="margin-top:14px">Accept Invitation</button>
</form>
</div>
@endsection
