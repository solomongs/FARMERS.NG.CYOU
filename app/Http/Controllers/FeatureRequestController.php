<?php

namespace App\Http\Controllers;

use App\Models\FeatureRequest;
use Illuminate\Http\Request;

class FeatureRequestController extends Controller
{
    public function index(Request $request)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'feature-requests.view'), 403);

        $requests = FeatureRequest::query()
            ->where('farm_id', $farm->id)
            ->latest()
            ->paginate(20);

        return view('feature-requests.index', compact('farm', 'requests'));
    }

    public function store(Request $request)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'feature-requests.create'), 403);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', 'string', 'max:80'],
            'description' => ['required', 'string', 'max:10000'],
            'problem' => ['nullable', 'string', 'max:10000'],
            'suggested_solution' => ['nullable', 'string', 'max:10000'],
            'priority' => ['required', 'in:low,normal,high,critical'],
            'contact_permission' => ['nullable', 'boolean'],
        ]);

        FeatureRequest::create([
            ...$validated,
            'farm_id' => $farm->id,
            'user_id' => $request->user()->id,
            'status' => 'submitted',
            'contact_permission' => (bool) ($validated['contact_permission'] ?? false),
        ]);

        return back()->with('success', 'Feature request submitted. You can track its status here.');
    }
}
