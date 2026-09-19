<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class InvitationController extends Controller
{
    public function show(string $token)
    {
        $invitation = $this->resolve($token);
        $existingUser = User::query()->where('email', $invitation->email)->exists();

        return view('team.accept', compact('invitation', 'token', 'existingUser'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = $this->resolve($token);
        $existing = User::query()->where('email', $invitation->email)->first();

        if ($existing) {
            $request->validate(['password' => ['required', 'string']]);

            if (!Hash::check($request->password, $existing->password)) {
                return back()->withErrors(['password' => 'Incorrect password for '.$invitation->email.'.']);
            }

            $user = $existing;
        } else {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:120'],
                'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            ]);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'email_verified_at' => now(),
                'password' => Hash::make($validated['password']),
            ]);
        }

        DB::transaction(function () use ($invitation, $user) {
            DB::table('farm_user')->updateOrInsert(
                ['farm_id' => $invitation->farm_id, 'user_id' => $user->id],
                [
                    'role_id' => $invitation->role_id,
                    'status' => 'active',
                    'joined_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            $invitation->update(['accepted_at' => now()]);
        });

        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->put('current_farm_id', $invitation->farm_id);

        return redirect()->route('dashboard')->with('success', 'You joined '.$invitation->farm->name.'.');
    }

    private function resolve(string $token): Invitation
    {
        $invitation = Invitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->with(['farm', 'role', 'inviter'])
            ->firstOrFail();

        abort_unless($invitation->isUsable(), 410, 'This invitation is no longer valid.');

        return $invitation;
    }
}
