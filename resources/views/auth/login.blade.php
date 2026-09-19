@extends('layouts.auth')
@section('title','Sign in — Farmers')
@section('content')
<div class="auth-card">
<h1>Welcome back</h1><p class="sub">Sign in to manage your farm.</p>
<form method="POST" action="{{ route('login.store') }}">@csrf
<div class="field"><label>Email address</label><input class="input" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus></div>
<div class="field" style="margin-top:12px"><label>Password</label><input class="input" type="password" name="password" autocomplete="current-password" required></div>
<label style="display:flex;gap:8px;align-items:center;font-size:12px;margin:12px 0"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
<button class="btn btn-primary btn-block" type="submit">Sign in</button>
</form>
<div class="auth-links"><a href="{{ route('password.request') }}">Forgot password?</a><a href="{{ route('register') }}">Create account</a></div>
</div>
@endsection
