<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PDO;
use Throwable;

class InstallerController extends Controller
{
    public function index(Request $request)
    {
        if ($this->installed()) {
            return redirect()->route('login')->with('success', 'Farmers is already installed.');
        }

        $this->authorizeInstaller($request);

        return view('installer.index', [
            'requirements' => $this->requirements(),
            'token' => $request->query('token'),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->installed()) {
            return redirect()->route('login');
        }

        $this->authorizeInstaller($request);

        $validated = $request->validate([
            'app_name' => ['required', 'string', 'max:80'],
            'app_url' => ['required', 'url', 'max:255'],
            'db_host' => ['required', 'string', 'max:255'],
            'db_port' => ['required', 'integer', 'between:1,65535'],
            'db_database' => ['required', 'string', 'max:128'],
            'db_username' => ['required', 'string', 'max:128'],
            'db_password' => ['nullable', 'string', 'max:255'],
            'superuser_name' => ['required', 'string', 'max:120'],
            'superuser_email' => ['required', 'email:rfc', 'max:255'],
            'superuser_password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        $failed = collect($this->requirements())->contains(fn ($item) => !$item['ok']);
        if ($failed) {
            return back()->withErrors([
                'installer' => 'Server requirements are not satisfied. Fix the failed checks and try again.',
            ])->withInput();
        }

        try {
            $this->testDatabase($validated);
            $this->writeEnvironment($validated);

            config([
                'app.name' => $validated['app_name'],
                'app.url' => rtrim($validated['app_url'], '/'),
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $validated['db_host'],
                'database.connections.mysql.port' => (string) $validated['db_port'],
                'database.connections.mysql.database' => $validated['db_database'],
                'database.connections.mysql.username' => $validated['db_username'],
                'database.connections.mysql.password' => $validated['db_password'] ?? '',
            ]);

            DB::purge('mysql');
            DB::reconnect('mysql');

            Artisan::call('migrate', ['--force' => true]);
            Artisan::call('db:seed', ['--force' => true]);

            User::query()->updateOrCreate(
                ['email' => Str::lower($validated['superuser_email'])],
                [
                    'name' => $validated['superuser_name'],
                    'password' => Hash::make($validated['superuser_password']),
                    'email_verified_at' => now(),
                    'is_superuser' => true,
                ]
            );

            try {
                Artisan::call('storage:link');
            } catch (Throwable) {
                // Some shared hosts block symlinks. The application still installs.
            }

            if (!is_dir(storage_path('app'))) {
                mkdir(storage_path('app'), 0775, true);
            }

            file_put_contents(
                storage_path('app/installed.lock'),
                json_encode([
                    'installed_at' => now()->toIso8601String(),
                    'app_url' => rtrim($validated['app_url'], '/'),
                    'version' => '0.1.0',
                ], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR)
            );

            Artisan::call('config:clear');
            Artisan::call('view:clear');

            return redirect()->route('installer.complete');
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors([
                'installer' => 'Installation failed: '.$e->getMessage(),
            ])->withInput();
        }
    }

    public function complete(Request $request)
    {
        if (!$this->installed()) {
            return redirect()->route('installer.index', array_filter([
                'token' => $request->query('token'),
            ]));
        }

        return view('installer.complete');
    }

    private function installed(): bool
    {
        return file_exists(storage_path('app/installed.lock'));
    }

    private function authorizeInstaller(Request $request): void
    {
        $expected = (string) env('INSTALLER_TOKEN', '');

        if ($expected === '') {
            return;
        }

        $provided = (string) ($request->query('token') ?: $request->input('token', ''));

        abort_unless($provided !== '' && hash_equals($expected, $provided), 403, 'Invalid installer token.');
    }

    private function requirements(): array
    {
        $checks = [
            ['label' => 'PHP 8.2 or newer', 'ok' => version_compare(PHP_VERSION, '8.2.0', '>=')],
        ];

        foreach (['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'fileinfo'] as $extension) {
            $checks[] = [
                'label' => "PHP extension: {$extension}",
                'ok' => extension_loaded($extension),
            ];
        }

        $checks[] = [
            'label' => 'storage/ is writable',
            'ok' => is_writable(storage_path()),
        ];

        $checks[] = [
            'label' => 'bootstrap/cache/ is writable',
            'ok' => is_writable(base_path('bootstrap/cache')),
        ];

        $env = base_path('.env');
        $checks[] = [
            'label' => '.env can be created/updated',
            'ok' => file_exists($env) ? is_writable($env) : is_writable(base_path()),
        ];

        return $checks;
    }

    private function testDatabase(array $data): void
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $data['db_host'],
            $data['db_port'],
            $data['db_database']
        );

        new PDO($dsn, $data['db_username'], $data['db_password'] ?? '', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
    }

    private function writeEnvironment(array $data): void
    {
        $path = base_path('.env');
        $content = file_exists($path)
            ? (string) file_get_contents($path)
            : (string) file_get_contents(base_path('.env.example'));

        $values = [
            'APP_NAME' => $data['app_name'],
            'APP_ENV' => 'production',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_DEBUG' => 'false',
            'APP_URL' => rtrim($data['app_url'], '/'),
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => $data['db_host'],
            'DB_PORT' => (string) $data['db_port'],
            'DB_DATABASE' => $data['db_database'],
            'DB_USERNAME' => $data['db_username'],
            'DB_PASSWORD' => $data['db_password'] ?? '',
            'SESSION_DRIVER' => 'file',
            'CACHE_STORE' => 'file',
            'QUEUE_CONNECTION' => 'database',
        ];

        foreach ($values as $key => $value) {
            $line = $key.'='.$this->envValue((string) $value);
            $pattern = '/^'.preg_quote($key, '/').'=.*$/m';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $line, $content);
            } else {
                $content .= PHP_EOL.$line;
            }
        }

        if (file_put_contents($path, rtrim($content).PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('Unable to write the .env file.');
        }
    }

    private function envValue(string $value): string
    {
        if (in_array($value, ['true', 'false', 'null'], true) || preg_match('/^[A-Za-z0-9_.:\/\-]+$/', $value)) {
            return $value;
        }

        return '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value).'"';
    }
}
