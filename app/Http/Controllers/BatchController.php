<?php

namespace App\Http\Controllers;

use App\Models\Batch;
use Illuminate\Http\Request;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'batch.view'), 403);

        $batches = Batch::query()
            ->where('farm_id', $farm->id)
            ->latest('date_in')
            ->paginate(20);

        return view('batches.index', compact('farm', 'batches'));
    }

    public function store(Request $request)
    {
        $farm = app('currentFarm');
        abort_unless($farm && $request->user()->hasFarmPermission($farm->id, 'batch.create'), 403);

        $validated = $request->validate([
            'batch_number' => ['required', 'string', 'max:120'],
            'production_type' => ['required', 'string', 'max:80'],
            'breed' => ['nullable', 'string', 'max:120'],
            'date_in' => ['required', 'date'],
            'initial_birds' => ['required', 'integer', 'min:1'],
            'purchase_cost' => ['nullable', 'numeric', 'min:0'],
            'supplier' => ['nullable', 'string', 'max:180'],
            'source' => ['nullable', 'string', 'max:180'],
            'expected_cycle_days' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $duplicate = Batch::query()
            ->where('farm_id', $farm->id)
            ->where('batch_number', $validated['batch_number'])
            ->exists();

        if ($duplicate) {
            return back()->withErrors(['batch_number' => 'This batch number already exists for the selected farm.'])->withInput();
        }

        Batch::create([
            ...$validated,
            'farm_id' => $farm->id,
            'created_by' => $request->user()->id,
            'current_birds' => $validated['initial_birds'],
            'purchase_cost' => $validated['purchase_cost'] ?? 0,
            'status' => 'active',
        ]);

        return back()->with('success', 'Batch created successfully.');
    }
}
