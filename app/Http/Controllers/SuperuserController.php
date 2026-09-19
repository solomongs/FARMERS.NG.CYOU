<?php

namespace App\Http\Controllers;

use App\Models\Farm;
use App\Models\FeatureRequest;
use App\Models\Module;
use App\Models\User;
use Illuminate\Http\Request;

class SuperuserController extends Controller
{
    public function dashboard(Request $request)
    {
        abort_unless($request->user()?->is_superuser, 403);

        return view('superuser.dashboard', [
            'farmers' => User::query()->where('is_superuser', false)->count(),
            'farms' => Farm::query()->count(),
            'modules' => Module::query()->where('enabled', true)->count(),
            'featureRequests' => FeatureRequest::query()->whereIn('status', ['submitted', 'under_review'])->count(),
        ]);
    }

    public function mcp(Request $request)
    {
        abort_unless($request->user()?->is_superuser, 403);

        return view('superuser.mcp');
    }
}
