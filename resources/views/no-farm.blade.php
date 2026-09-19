@extends('layouts.auth')
@section('title','No farm access — Farmers')
@section('content')
<div class="auth-card">
<h1>No active farm workspace</h1><p class="sub">Your account is signed in, but it is not currently attached to an active farm.</p>
<div class="warning-box">Ask a farm owner to invite/reactivate you, or contact the Farmers administrator if you believe this is an error.</div>
<form method="POST" action="{{ route('logout') }}" style="margin-top:14px">@csrf<button class="btn btn-secondary btn-block">Sign out</button></form>
</div>
@endsection
