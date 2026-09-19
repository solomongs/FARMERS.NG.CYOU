<?php

namespace App\Http\Controllers;

use App\Models\LegacyImport;
use App\Services\LegacyPoultryPlusImporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use JsonException;
use Throwable;

class LegacyImportController extends Controller
{
    public function index(Request $request)
    {
        $farm = app('currentFarm');

        abort_unless($farm, 404);
        abort_unless($request->user()->hasFarmPermission($farm->id, 'backup.import'), 403);

        $history = LegacyImport::query()
            ->where('farm_id', $farm->id)
            ->latest()
            ->limit(20)
            ->get();

        return view('migration.static', compact('farm', 'history'));
    }

    public function store(Request $request, LegacyPoultryPlusImporter $importer)
    {
        $farm = app('currentFarm');

        abort_unless($farm, 404);
        abort_unless($request->user()->hasFarmPermission($farm->id, 'backup.import'), 403);

        $validated = $request->validate([
            'backup' => ['required', 'file', 'max:20480'],
            'confirmation' => ['required', 'accepted'],
        ]);

        $uploaded = $validated['backup'];
        $raw = file_get_contents($uploaded->getRealPath());

        if ($raw === false || trim($raw) === '') {
            throw ValidationException::withMessages(['backup' => 'The uploaded backup is empty.']);
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            throw ValidationException::withMessages(['backup' => 'The uploaded file is not valid JSON.']);
        }

        if (!is_array($data)) {
            throw ValidationException::withMessages(['backup' => 'The uploaded backup has an invalid structure.']);
        }

        $hash = hash('sha256', $raw);
        $archiveName = now()->format('Ymd_His').'_'.Str::random(10).'.json';
        $archivePath = 'legacy-imports/'.$farm->id.'/'.$archiveName;

        Storage::disk('local')->put($archivePath, $raw);

        try {
            $import = $importer->import($data, $farm, $request->user(), $hash, $archivePath);
        } catch (Throwable $e) {
            if (!LegacyImport::query()->where('farm_id', $farm->id)->where('file_hash', $hash)->exists()) {
                Storage::disk('local')->delete($archivePath);
            }

            throw ValidationException::withMessages([
                'backup' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('migration.static')
            ->with('success', 'PoultryPlus data imported successfully.')
            ->with('import_id', $import->id);
    }
}
