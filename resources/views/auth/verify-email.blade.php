@extends('layouts.auth')
@section('title','Verify email — Farmers')
@section('content')
<div class="auth-card">
<h1>Verify your email</h1>
<p class="sub">We sent a verification link to <strong>{{ auth()->user()->email }}</strong>. Verify your email to open your farm workspace.</p>
<div class="safe-box">If the message is not in your inbox, check spam/junk or request another link.</div>
<form method="POST" action="{{ route('verification.send') }}">@csrf<button class="btn btn-primary btn-block" style="margin-top:14px" type="submit">Resend verification email</button></form>
<form method="POST" action="{{ route('logout') }}" style="margin-top:9px">@csrf<button class="btn btn-secondary btn-block" type="submit">Sign out</button></form>
</div>
@endsection
