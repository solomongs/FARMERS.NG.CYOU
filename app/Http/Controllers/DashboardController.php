<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $farm = app('currentFarm');

        abort_unless($farm, 404);
        abort_unless($request->user()->hasFarmPermission($farm->id, 'dashboard.view'), 403);

        $activeBatches = DB::table('batches')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->where('status', 'active')
            ->count();

        $initialBirds = (int) DB::table('batches')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('initial_birds');

        $currentBirds = (int) DB::table('batches')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('current_birds');

        $mortality = (int) DB::table('mortality_records')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('number_dead');

        $feedUsed = (float) DB::table('feed_records')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('quantity');

        $feedCost = (float) DB::table('feed_records')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('total_cost');

        $otherExpenses = (float) DB::table('expenses')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('total');

        $revenue = (float) DB::table('sales')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->sum('total_revenue');

        $productionCost = $feedCost + $otherExpenses;
        $profit = $revenue - $productionCost;

        $inventoryAlerts = DB::table('inventory_items')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->whereColumn('balance', '<=', 'reorder_level')
            ->count();

        $upcomingMedication = DB::table('medication_records')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->whereIn('status', ['scheduled', 'due'])
            ->whereNotNull('next_due_date')
            ->whereDate('next_due_date', '<=', now()->addDays(7))
            ->count();

        $recentBatches = DB::table('batches')
            ->where('farm_id', $farm->id)
            ->whereNull('deleted_at')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('dashboard', compact(
            'farm',
            'activeBatches',
            'initialBirds',
            'currentBirds',
            'mortality',
            'feedUsed',
            'feedCost',
            'otherExpenses',
            'revenue',
            'productionCost',
            'profit',
            'inventoryAlerts',
            'upcomingMedication',
            'recentBatches',
        ));
    }
}
