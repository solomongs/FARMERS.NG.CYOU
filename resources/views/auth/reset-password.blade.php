@extends('layouts.auth')
@section('title','Choose new password — Farmers')
@section('content')
<div class="auth-card">
<h1>Choose a new password</h1><p class="sub">Create a secure password for your Farmers account.</p>
<form method="POST" action="{{ route('password.update') }}">@csrf
<input type="hidden" name="token" value="{{ $token }}">
<div class="field"><label>Email address</label><input class="input" type="email" name="email" value="{{ old('email',$email) }}" required></div>
<div class="field" style="margin-top:12px"><label>New password</label><input class="input" type="password" name="password" minlength="8" required></div>
<div class="field" style="margin-top:12px"><label>Confirm password</label><input class="input" type="password" name="password_confirmation" minlength="8" required></div>
<button class="btn btn-primary btn-block" style="margin-top:14px" type="submit">Reset password</button>
</form>
</div>
@endsection
