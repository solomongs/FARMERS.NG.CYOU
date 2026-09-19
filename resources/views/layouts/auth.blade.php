<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover">
<meta name="theme-color" content="#2563eb"><link rel="manifest" href="/manifest.webmanifest"><link rel="stylesheet" href="/css/app.css">
<title>@yield('title','Farmers')</title>
</head>
<body class="auth-body">
<div class="auth-wrap">
    <div class="auth-brand"><div class="logo">🌱</div><strong>Farmers</strong></div>
    @if(session('success'))<div class="flash flash-success">{{ session('success') }}</div>@endif
    @if(session('status'))<div class="flash flash-success">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="flash flash-error">{{ $errors->first() }}</div>@endif
    @yield('content')
    <div class="auth-credit">Powered by Ojenene.com & Qazeem Basit Oladimeji</div>
</div>
<script src="/js/app.js" defer></script>
</body></html>
