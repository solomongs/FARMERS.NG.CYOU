<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\Module;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function registerForm()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'farm_name' => ['required', 'string', 'max:180'],
            'email' => ['required', 'email:rfc', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', 'string', 'max:120'],
            'farm_type' => ['required', 'string', 'max:80'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        [$user, $farm] = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => Str::lower($validated['email']),
                'phone' => $validated['phone'] ?? null,
                'country' => $validated['country'] ?? null,
                'state' => $validated['state'] ?? null,
                'password' => Hash::make($validated['password']),
            ]);

            $farm = Farm::create([
                'owner_id' => $user->id,
                'name' => $validated['farm_name'],
                'slug' => $this->uniqueFarmSlug($validated['farm_name']),
                'type' => $validated['farm_type'],
                'email' => $user->email,
                'phone' => $user->phone,
                'country' => $user->country,
                'state' => $user->state,
            ]);

            $ownerRole = Role::query()->whereNull('farm_id')->where('key', 'owner')->firstOrFail();

            DB::table('farm_user')->insert([
                'farm_id' => $farm->id,
                'user_id' => $user->id,
                'role_id' => $ownerRole->id,
                'status' => 'active',
                'joined_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $modules = Module::query()->where('enabled', true)->pluck('id');
            foreach ($modules as $moduleId) {
                DB::table('tenant_modules')->insert([
                    'farm_id' => $farm->id,
                    'module_id' => $moduleId,
                    'enabled' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            return [$user, $farm];
        });

        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('current_farm_id', $farm->id);

        return redirect()->route('verification.notice');
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $key = Str::lower($validated['email']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()->withErrors([
                'email' => 'Too many login attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.',
            ])->onlyInput('email');
        }

        if (!Auth::attempt([
            'email' => Str::lower($validated['email']),
            'password' => $validated['password'],
        ], (bool) ($validated['remember'] ?? false))) {
            RateLimiter::hit($key, 60);

            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill(['last_login_at' => now()])->save();

        if ($user->is_superuser) {
            return redirect()->route('superuser.dashboard');
        }

        $firstFarm = $user->farms()->wherePivot('status', 'active')->first();
        if ($firstFarm) {
            $request->session()->put('current_farm_id', $firstFarm->id);
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    public function forgotForm()
    {
        return view('auth.forgot-password');
    }

    public function forgot(Request $request)
    {
        $request->validate(['email' => ['required', 'email:rfc']]);

        Password::sendResetLink(['email' => Str::lower($request->email)]);

        return back()->with('status', 'If an account exists for that email, a password reset link has been sent.');
    }

    public function resetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset(
            [
                'email' => Str::lower($validated['email']),
                'password' => $validated['password'],
                'password_confirmation' => $request->password_confirmation,
                'token' => $validated['token'],
            ],
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => __($status)]);
        }

        return redirect()->route('login')->with('success', 'Password reset successfully. You can now sign in.');
    }

    public function verificationNotice(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->is_superuser ? 'superuser.dashboard' : 'dashboard');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('dashboard');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return redirect()->route('dashboard')->with('success', 'Email verified successfully.');
    }

    public function resendVerification(Request $request)
    {
        if (!$request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'Verification link sent.');
    }

    private function uniqueFarmSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'farm';
        $slug = $base;
        $counter = 1;

        while (Farm::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }
}
