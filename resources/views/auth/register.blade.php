@extends('layouts.auth')
@section('title','Create account — Farmers')
@section('content')
<div class="auth-card">
<h1>Create your farm</h1><p class="sub">Start with your farm workspace. You can invite staff after setup.</p>
<form method="POST" action="{{ route('register.store') }}">@csrf
<div class="form-grid">
<div class="field"><label>Full name</label><input class="input" name="name" value="{{ old('name') }}" required></div>
<div class="field"><label>Farm / Business name</label><input class="input" name="farm_name" value="{{ old('farm_name') }}" required></div>
<div class="field"><label>Email</label><input class="input" type="email" name="email" value="{{ old('email') }}" required></div>
<div class="field"><label>Phone</label><input class="input" name="phone" value="{{ old('phone') }}"></div>
<div class="field"><label>Country</label><input class="input" name="country" value="{{ old('country','Nigeria') }}"></div>
<div class="field"><label>State / Region</label><input class="input" name="state" value="{{ old('state') }}"></div>
<div class="field full"><label>Farm type</label><select class="input" name="farm_type" required><option value="poultry">Poultry</option><option value="mixed">Mixed Farming</option><option value="fish">Fish</option><option value="piggery">Piggery</option><option value="cattle">Cattle</option><option value="crop">Crops</option><option value="other">Other</option></select></div>
<div class="field"><label>Password</label><input class="input" type="password" name="password" minlength="8" required></div>
<div class="field"><label>Confirm password</label><input class="input" type="password" name="password_confirmation" minlength="8" required></div>
</div>
<button class="btn btn-primary btn-block" style="margin-top:16px" type="submit">Create free account</button>
</form>
<div class="auth-links"><span>Farmers is free to use.</span><a href="{{ route('login') }}">Already registered?</a></div>
</div>
@endsection
