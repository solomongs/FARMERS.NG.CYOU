<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\Membership;
use App\Models\Permission;
use App\Models\Role;
use App\Models\UserPermission;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'team.view'), 403);

        $members = Membership::query()
            ->where('farm_id', $farm->id)
            ->with(['user', 'role'])
            ->orderByDesc('joined_at')
            ->get();

        $roles = Role::query()
            ->where(function ($query) use ($farm) {
                $query->whereNull('farm_id')->orWhere('farm_id', $farm->id);
            })
            ->orderBy('name')
            ->get();

        $invitations = Invitation::query()
            ->where('farm_id', $farm->id)
            ->with('role')
            ->latest()
            ->limit(30)
            ->get();

        $permissions = Permission::query()->orderBy('module_key')->orderBy('name')->get();
        $memberOverrides = UserPermission::query()
            ->where('farm_id', $farm->id)
            ->get()
            ->groupBy('user_id');

        return view('team.index', compact('farm', 'members', 'roles', 'invitations', 'permissions', 'memberOverrides'));
    }

    public function invite(Request $request)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'team.manage'), 403);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'role_id' => ['required', 'integer'],
        ]);

        $role = Role::query()
            ->where('id', $validated['role_id'])
            ->where(function ($query) use ($farm) {
                $query->whereNull('farm_id')->orWhere('farm_id', $farm->id);
            })
            ->firstOrFail();

        $alreadyMember = DB::table('farm_user')
            ->join('users', 'users.id', '=', 'farm_user.user_id')
            ->where('farm_user.farm_id', $farm->id)
            ->whereRaw('LOWER(users.email) = ?', [Str::lower($validated['email'])])
            ->exists();

        if ($alreadyMember) {
            return back()->withErrors(['email' => 'That email already belongs to a member of this farm.']);
        }

        $token = Str::random(64);

        $invitation = Invitation::query()->updateOrCreate(
            [
                'farm_id' => $farm->id,
                'email' => Str::lower($validated['email']),
                'accepted_at' => null,
            ],
            [
                'role_id' => $role->id,
                'invited_by' => $request->user()->id,
                'name' => $validated['name'] ?? null,
                'token_hash' => hash('sha256', $token),
                'expires_at' => now()->addDays(7),
                'cancelled_at' => null,
            ]
        );

        $invitation->load(['farm', 'role', 'inviter']);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation, $token));

        DB::table('audit_logs')->insert([
            'farm_id' => $farm->id,
            'user_id' => $request->user()->id,
            'action' => 'team.invitation.sent',
            'resource_type' => Invitation::class,
            'resource_id' => (string) $invitation->id,
            'metadata' => json_encode(['email' => $invitation->email, 'role' => $role->key], JSON_THROW_ON_ERROR),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);

        return back()->with('success', 'Staff invitation sent to '.$invitation->email.'.');
    }

    public function status(Request $request, Membership $membership)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'team.manage'), 403);
        abort_unless($membership->farm_id === $farm->id, 404);
        abort_if($membership->user_id === $farm->owner_id, 422, 'The farm owner cannot be deactivated.');

        $validated = $request->validate([
            'status' => ['required', 'in:active,inactive'],
        ]);

        $membership->update([
            'status' => $validated['status'],
            'last_active_at' => $validated['status'] === 'active' ? now() : $membership->last_active_at,
        ]);

        return back()->with('success', $validated['status'] === 'active' ? 'Staff access restored.' : 'Staff access revoked.');
    }

    public function permissions(Request $request, Membership $membership)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'team.manage'), 403);
        abort_unless($membership->farm_id === $farm->id, 404);
        abort_if($membership->user_id === $farm->owner_id, 422, 'The farm owner permissions cannot be overridden.');

        $allowedKeys = collect($request->input('permissions', []))
            ->filter(fn ($key) => is_string($key))
            ->values();

        $permissions = Permission::query()->get();

        DB::transaction(function () use ($farm, $membership, $permissions, $allowedKeys) {
            foreach ($permissions as $permission) {
                UserPermission::updateOrCreate(
                    [
                        'farm_id' => $farm->id,
                        'user_id' => $membership->user_id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'allowed' => $allowedKeys->contains($permission->key),
                    ]
                );
            }
        });

        return back()->with('success', 'Custom staff permissions updated.');
    }

    public function resetPermissions(Request $request, Membership $membership)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'team.manage'), 403);
        abort_unless($membership->farm_id === $farm->id, 404);
        abort_if($membership->user_id === $farm->owner_id, 422, 'The farm owner permissions cannot be overridden.');

        UserPermission::query()
            ->where('farm_id', $farm->id)
            ->where('user_id', $membership->user_id)
            ->delete();

        return back()->with('success', 'Staff permissions reset to the assigned role defaults.');
    }

    public function cancelInvitation(Request $request, Invitation $invitation)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'team.manage'), 403);
        abort_unless($invitation->farm_id === $farm->id, 404);

        $invitation->update(['cancelled_at' => now()]);

        return back()->with('success', 'Invitation cancelled.');
    }
}
