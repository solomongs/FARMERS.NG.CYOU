@extends('layouts.auth')
@section('title','Reset password — Farmers')
@section('content')
<div class="auth-card">
<h1>Forgot password?</h1><p class="sub">Enter your registered email address. If an account exists, we will send a secure reset link.</p>
<form method="POST" action="{{ route('password.email') }}">@csrf
<div class="field"><label>Email address</label><input class="input" type="email" name="email" value="{{ old('email') }}" required autofocus></div>
<button class="btn btn-primary btn-block" style="margin-top:14px" type="submit">Send reset link</button>
</form>
<div class="auth-links"><a href="{{ route('login') }}">Back to sign in</a></div>
</div>
@endsection
