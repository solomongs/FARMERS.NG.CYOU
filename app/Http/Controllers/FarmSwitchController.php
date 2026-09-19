<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FarmSwitchController extends Controller
{
    public function __invoke(Request $request)
    {
        $validated = $request->validate([
            'farm_id' => ['required', 'integer'],
        ]);

        $farm = $request->user()->farms()
            ->wherePivot('status', 'active')
            ->where('farms.id', $validated['farm_id'])
            ->firstOrFail();

        $request->session()->put('current_farm_id', $farm->id);

        return back()->with('success', 'Farm switched to '.$farm->name.'.');
    }
}
