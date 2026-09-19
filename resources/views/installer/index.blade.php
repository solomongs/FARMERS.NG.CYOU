<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Install Farmers</title><link rel="stylesheet" href="/css/app.css">
</head>
<body class="auth-body">
<div class="install-shell">
    <div class="install-hero">
        <div class="logo">🌱</div>
        <h1>Install Farmers</h1>
        <p>Shared-hosting friendly setup wizard for Farmers.ng.cyou. It will validate your server, connect MySQL/MariaDB, run migrations and create the first Superuser.</p>
    </div>
    @if($errors->any())<div class="flash flash-error">{{ $errors->first() }}</div>@endif
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>1. Server requirements</h3></div>
        <div class="card-body">
            <div class="require-grid">
                @foreach($requirements as $requirement)
                <div class="require"><span>{{ $requirement['label'] }}</span><span class="{{ $requirement['ok'] ? 'ok' : 'fail' }}">{{ $requirement['ok'] ? 'Ready' : 'Fix' }}</span></div>
                @endforeach
            </div>
        </div>
    </div>
    <form class="card" method="POST" action="{{ route('installer.store') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="card-header"><h3>2. Application & database</h3></div>
        <div class="card-body">
            <div class="form-grid">
                <div class="field"><label>Application name</label><input class="input" name="app_name" value="{{ old('app_name','Farmers') }}" required></div>
                <div class="field"><label>Application URL</label><input class="input" type="url" name="app_url" value="{{ old('app_url','https://farmers.ng.cyou') }}" required></div>
                <div class="field"><label>Database host</label><input class="input" name="db_host" value="{{ old('db_host','127.0.0.1') }}" required></div>
                <div class="field"><label>Database port</label><input class="input" type="number" name="db_port" value="{{ old('db_port','3306') }}" required></div>
                <div class="field"><label>Database name</label><input class="input" name="db_database" value="{{ old('db_database') }}" required></div>
                <div class="field"><label>Database username</label><input class="input" name="db_username" value="{{ old('db_username') }}" required></div>
                <div class="field full"><label>Database password</label><input class="input" type="password" name="db_password" autocomplete="new-password"></div>
            </div>
            <div class="divider"></div>
            <h3 style="font-size:15px;margin:0 0 12px">3. First Superuser</h3>
            <div class="form-grid">
                <div class="field"><label>Name</label><input class="input" name="superuser_name" value="{{ old('superuser_name') }}" required></div>
                <div class="field"><label>Email</label><input class="input" type="email" name="superuser_email" value="{{ old('superuser_email') }}" required></div>
                <div class="field"><label>Password</label><input class="input" type="password" name="superuser_password" minlength="10" required></div>
                <div class="field"><label>Confirm password</label><input class="input" type="password" name="superuser_password_confirmation" minlength="10" required></div>
            </div>
            <div class="safe-box" style="margin-top:16px">The installer regenerates the Laravel application key, runs migrations, seeds module/permission data, creates the Superuser, and writes an installation lock.</div>
            <button class="btn btn-primary btn-block" style="margin-top:16px" type="submit">Install Farmers</button>
        </div>
    </form>
    <div class="auth-credit">Powered by Ojenene.com & Qazeem Basit Oladimeji</div>
</div>
</body></html>
