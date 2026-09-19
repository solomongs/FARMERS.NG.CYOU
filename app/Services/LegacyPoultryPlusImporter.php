<?php

namespace App\Services;

use App\Models\Farm;
use App\Models\LegacyImport;
use App\Models\LegacyRecordMap;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class LegacyPoultryPlusImporter
{
    private const STORES = [
        'batches',
        'dailyRecords',
        'feedRecords',
        'vaccinationRecords',
        'mortalityRecords',
        'eggProductionRecords',
        'salesRecords',
        'expenseRecords',
        'inventoryRecords',
        'weeklyBodyWeightRecords',
        'performanceSummaries',
        'auditLogs',
        'settings',
        'users',
        'farms',
    ];

    public function inspect(array $data): array
    {
        $this->validateBackup($data);

        $farm = Arr::first($data['farms'] ?? []);

        $counts = [];
        foreach (self::STORES as $store) {
            $counts[$store] = is_array($data[$store] ?? null) ? count($data[$store]) : 0;
        }

        return [
            'source_farm_id' => (string) ($data['_farmId'] ?? ''),
            'source_farm_name' => (string) ($farm['farmName'] ?? $farm['name'] ?? 'Legacy Farm'),
            'source_version' => isset($data['_version']) ? (int) $data['_version'] : null,
            'source_exported_at' => $data['_exportedAt'] ?? null,
            'mode' => $data['_mode'] ?? null,
            'counts' => $counts,
        ];
    }

    public function import(array $data, Farm $farm, User $user, string $hash, ?string $archivePath = null): LegacyImport
    {
        $summary = $this->inspect($data);

        if (LegacyImport::query()->where('farm_id', $farm->id)->where('file_hash', $hash)->exists()) {
            throw new RuntimeException('This exact PoultryPlus backup has already been imported into this farm.');
        }

        $import = LegacyImport::create([
            'uuid' => (string) Str::uuid(),
            'farm_id' => $farm->id,
            'imported_by' => $user->id,
            'source' => 'poultryplus-v2',
            'source_farm_id' => $summary['source_farm_id'] ?: null,
            'source_farm_name' => $summary['source_farm_name'],
            'file_hash' => $hash,
            'archive_path' => $archivePath,
            'source_version' => $summary['source_version'],
            'source_exported_at' => $this->dateTime($summary['source_exported_at']),
            'status' => 'processing',
            'counts' => [],
            'warnings' => [],
        ]);

        try {
            $result = DB::transaction(function () use ($data, $farm, $user, $import, $summary) {
                $counts = [];
                $warnings = [];

                $this->importFarmMetadata($data, $farm);
                $counts['farmMetadata'] = 1;

                $batchMap = $this->importBatches($data['batches'] ?? [], $farm, $user, $import, $counts);

                $this->importDaily($data['dailyRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importFeed($data['feedRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importMedication($data['vaccinationRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importMortality($data['mortalityRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importEggs($data['eggProductionRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importSales($data['salesRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importExpenses($data['expenseRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importInventory($data['inventoryRecords'] ?? [], $farm, $user, $import, $counts, $warnings);
                $this->importWeights($data['weeklyBodyWeightRecords'] ?? [], $farm, $user, $import, $batchMap, $counts, $warnings);
                $this->importAudit($data['auditLogs'] ?? [], $farm, $user, $import, $counts);
                $this->preserveSettingsAndDerivedData($data, $farm, $counts);

                $this->recalculateBatchPopulations($farm);

                DB::table('audit_logs')->insert([
                    'farm_id' => $farm->id,
                    'user_id' => $user->id,
                    'action' => 'legacy.import.completed',
                    'resource_type' => LegacyImport::class,
                    'resource_id' => (string) $import->id,
                    'metadata' => json_encode([
                        'source' => 'PoultryPlus IndexedDB JSON backup',
                        'source_farm_id' => $summary['source_farm_id'],
                        'counts' => $counts,
                    ], JSON_THROW_ON_ERROR),
                    'ip_address' => request()?->ip(),
                    'user_agent' => request()?->userAgent(),
                    'created_at' => now(),
                ]);

                return [$counts, array_values(array_unique($warnings))];
            });

            [$counts, $warnings] = $result;

            $import->update([
                'status' => 'completed',
                'counts' => $counts,
                'warnings' => $warnings,
            ]);

            return $import->fresh();
        } catch (Throwable $e) {
            $import->update([
                'status' => 'failed',
                'error_message' => Str::limit($e->getMessage(), 5000),
            ]);

            throw $e;
        }
    }

    private function validateBackup(array $data): void
    {
        if (!$data) {
            throw new RuntimeException('The uploaded backup is empty.');
        }

        $recognized = array_intersect(array_keys($data), self::STORES);
        if (!$recognized) {
            throw new RuntimeException('This file does not look like a PoultryPlus backup.');
        }

        $sourceFarmId = $data['_farmId'] ?? null;
        if (!$sourceFarmId) {
            throw new RuntimeException('The PoultryPlus backup is missing its source farm identifier.');
        }

        foreach (self::STORES as $store) {
            if (isset($data[$store]) && !is_array($data[$store])) {
                throw new RuntimeException("Invalid {$store} data in backup.");
            }
        }

        foreach (self::STORES as $store) {
            if (in_array($store, ['users', 'farms', 'settings'], true)) {
                continue;
            }

            foreach ($data[$store] ?? [] as $record) {
                if (!is_array($record)) {
                    throw new RuntimeException("Invalid record found in {$store}.");
                }

                $recordFarmId = $record['farmId'] ?? null;
                if ($recordFarmId && (string) $recordFarmId !== (string) $sourceFarmId) {
                    throw new RuntimeException("The backup contains mixed farm data in {$store}. Import stopped.");
                }
            }
        }
    }

    private function importFarmMetadata(array $data, Farm $farm): void
    {
        $legacy = Arr::first($data['farms'] ?? []);

        if (is_array($legacy)) {
            $updates = array_filter([
                'name' => $legacy['farmName'] ?? $legacy['name'] ?? null,
                'address' => $legacy['location'] ?? $legacy['address'] ?? null,
                'phone' => $legacy['phone'] ?? null,
                'email' => $legacy['email'] ?? null,
            ], fn ($value) => $value !== null && $value !== '');

            if ($updates) {
                $farm->fill($updates)->save();
            }

            DB::table('farm_settings')->updateOrInsert(
                ['farm_id' => $farm->id, 'key' => 'legacy_farm_profile'],
                [
                    'value' => json_encode($legacy, JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    private function importBatches(array $records, Farm $farm, User $user, LegacyImport $import, array &$counts): array
    {
        $map = [];
        $counts['batches'] = 0;
        $counts['batchesSkipped'] = 0;

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $legacyId = (string) ($record['id'] ?? '');
            if ($legacyId === '') {
                $counts['batchesSkipped']++;
                continue;
            }

            $existingMap = $this->existingMap($farm->id, 'batches', $legacyId);
            if ($existingMap?->target_id) {
                $map[$legacyId] = (int) $existingMap->target_id;
                $counts['batchesSkipped']++;
                continue;
            }

            $number = trim((string) ($record['batchNo'] ?? ''));
            if ($number === '') {
                $number = 'LEGACY-'.substr(preg_replace('/[^A-Za-z0-9]/', '', $legacyId), 0, 12);
            }

            $base = $number;
            $suffix = 1;
            while (DB::table('batches')->where('farm_id', $farm->id)->where('batch_number', $number)->exists()) {
                $number = $base.'-IMP'.$suffix++;
            }

            $initial = max(0, (int) ($record['chicksPurchased'] ?? 0));
            $id = DB::table('batches')->insertGetId([
                'farm_id' => $farm->id,
                'created_by' => $user->id,
                'batch_number' => $number,
                'production_type' => strtolower((string) ($record['productionType'] ?? 'broiler')),
                'breed' => $record['breed'] ?? null,
                'date_in' => $this->date($record['dateIn'] ?? null) ?? now()->toDateString(),
                'initial_birds' => $initial,
                'current_birds' => $initial,
                'purchase_cost' => (float) ($record['purchaseCost'] ?? 0),
                'supplier' => $record['supplier'] ?? null,
                'source' => $record['source'] ?? 'PoultryPlus import',
                'expected_cycle_days' => isset($record['expectedCycleDays']) ? (int) $record['expectedCycleDays'] : null,
                'status' => $record['deleted'] ?? false ? 'archived' : ($record['status'] ?? 'active'),
                'notes' => $record['notes'] ?? null,
                'created_at' => $this->dateTime($record['createdAt'] ?? null) ?? now(),
                'updated_at' => $this->dateTime($record['updatedAt'] ?? null) ?? now(),
                'deleted_at' => ($record['deleted'] ?? false) ? ($this->dateTime($record['deletedAt'] ?? null) ?? now()) : null,
            ]);

            $this->map($import, $farm, 'batches', $legacyId, 'batches', $id);
            $map[$legacyId] = $id;
            $counts['batches']++;
        }

        return $map;
    }

    private function importDaily(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('dailyRecords', 'daily_records', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            fn (array $r, int $batchId) => [
                'batch_id' => $batchId,
                'recorded_by' => $user->id,
                'record_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                'age_days' => (int) ($r['birdAge'] ?? 0),
                'feed_consumed' => (float) ($r['feedGiven'] ?? 0),
                'water_consumed' => (float) ($r['waterGiven'] ?? 0),
                'mortality' => (int) ($r['mortality'] ?? 0),
                'remarks' => $r['remarks'] ?? null,
            ]);
    }

    private function importFeed(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('feedRecords', 'feed_records', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            function (array $r, int $batchId) use ($user) {
                $quantity = (float) ($r['quantityKg'] ?? $r['quantity'] ?? 0);
                $costPerUnit = (float) ($r['costPerKg'] ?? $r['costPerUnit'] ?? 0);

                return [
                    'batch_id' => $batchId,
                    'recorded_by' => $user->id,
                    'record_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                    'age_days' => (int) ($r['birdAge'] ?? 0),
                    'feed_type' => $r['feedType'] ?? 'Imported Feed',
                    'quantity' => $quantity,
                    'unit' => $r['unit'] ?? 'kg',
                    'cost_per_unit' => $costPerUnit,
                    'total_cost' => isset($r['totalCost']) ? (float) $r['totalCost'] : $quantity * $costPerUnit,
                    'supplier' => $r['supplier'] ?? null,
                    'notes' => $r['notes'] ?? null,
                ];
            });
    }

    private function importMedication(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('vaccinationRecords', 'medication_records', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            fn (array $r, int $batchId) => [
                'batch_id' => $batchId,
                'recorded_by' => $user->id,
                'record_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                'age_days' => (int) ($r['birdAge'] ?? 0),
                'name' => $r['vaccineDrugName'] ?? 'Imported Medication',
                'program_type' => $r['programType'] ?? 'general',
                'route' => $r['route'] ?? null,
                'dosage' => $r['dosage'] ?? null,
                'status' => $this->medicationStatus($r['status'] ?? 'scheduled'),
                'administered_by' => $r['administeredBy'] ?? null,
                'next_due_date' => $this->date($r['nextDueDate'] ?? null),
                'notes' => trim(implode("\n", array_filter([
                    isset($r['purpose']) ? 'Purpose: '.$r['purpose'] : null,
                    $r['notes'] ?? null,
                ]))) ?: null,
            ]);
    }

    private function importMortality(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('mortalityRecords', 'mortality_records', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            fn (array $r, int $batchId) => [
                'batch_id' => $batchId,
                'recorded_by' => $user->id,
                'record_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                'age_days' => (int) ($r['birdAge'] ?? 0),
                'number_dead' => (int) ($r['noDead'] ?? 0),
                'suspected_cause' => $r['cause'] ?? null,
                'corrective_action' => $r['actionTaken'] ?? null,
                'notes' => $r['notes'] ?? null,
            ]);
    }

    private function importEggs(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('eggProductionRecords', 'egg_production_records', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            function (array $r, int $batchId) use ($user) {
                $birds = max(0, (int) ($r['numBirds'] ?? 0));
                $eggs = max(0, (int) ($r['eggsCollected'] ?? 0));
                $cracked = max(0, (int) ($r['crackedEggs'] ?? 0));
                $damaged = max(0, (int) ($r['damagedEggs'] ?? 0));
                $saleable = isset($r['saleableEggs']) ? max(0, (int) $r['saleableEggs']) : max(0, $eggs - $cracked - $damaged);

                return [
                    'batch_id' => $batchId,
                    'recorded_by' => $user->id,
                    'record_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                    'age_days' => (int) ($r['birdAge'] ?? 0),
                    'number_of_birds' => $birds,
                    'eggs_collected' => $eggs,
                    'cracked_eggs' => $cracked,
                    'damaged_eggs' => $damaged,
                    'saleable_eggs' => $saleable,
                    'production_rate' => $birds > 0 ? ($eggs / $birds) * 100 : 0,
                    'remarks' => $r['remarks'] ?? null,
                ];
            });
    }

    private function importSales(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('salesRecords', 'sales', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            function (array $r, int $batchId) use ($user) {
                $quantity = (float) ($r['quantity'] ?? 0);
                $unitPrice = (float) ($r['unitPrice'] ?? 0);

                return [
                    'batch_id' => $batchId,
                    'recorded_by' => $user->id,
                    'sale_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                    'item' => $r['itemSold'] ?? 'Imported Sale',
                    'category' => $r['category'] ?? null,
                    'quantity' => $quantity,
                    'unit' => $r['unit'] ?? null,
                    'unit_price' => $unitPrice,
                    'total_revenue' => isset($r['totalRevenue']) ? (float) $r['totalRevenue'] : $quantity * $unitPrice,
                    'buyer_name' => $r['buyerName'] ?? null,
                    'buyer_contact' => $r['buyerContact'] ?? null,
                    'payment_status' => $r['paymentStatus'] ?? 'paid',
                    'payment_method' => $r['paymentMethod'] ?? null,
                    'notes' => $r['notes'] ?? null,
                ];
            }, 'sale_date');
    }

    private function importExpenses(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $filtered = [];
        foreach ($records as $record) {
            if (strtolower((string) ($record['category'] ?? '')) === 'feed') {
                $warnings[] = 'Legacy expense records categorized as Feed were skipped to prevent double-counting because Feed Records remain the authoritative feed-cost source.';
                continue;
            }
            $filtered[] = $record;
        }

        $this->importBatchRecords('expenseRecords', 'expenses', $filtered, $farm, $user, $import, $batchMap, $counts, $warnings,
            function (array $r, int $batchId) use ($user) {
                $quantity = (float) ($r['quantity'] ?? 1);
                $costPerUnit = (float) ($r['costPerUnit'] ?? 0);

                return [
                    'batch_id' => $batchId,
                    'recorded_by' => $user->id,
                    'expense_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                    'item' => $r['itemService'] ?? 'Imported Expense',
                    'category' => $r['category'] ?? 'Other',
                    'quantity' => $quantity,
                    'cost_per_unit' => $costPerUnit,
                    'total' => isset($r['totalCost']) ? (float) $r['totalCost'] : $quantity * $costPerUnit,
                    'vendor' => $r['vendor'] ?? null,
                    'payment_method' => $r['paymentMethod'] ?? null,
                    'receipt_path' => null,
                    'notes' => $r['notes'] ?? null,
                ];
            }, 'expense_date');
    }

    private function importInventory(array $records, Farm $farm, User $user, LegacyImport $import, array &$counts, array &$warnings): void
    {
        $counts['inventoryRecords'] = 0;
        $counts['inventoryRecordsSkipped'] = 0;
        $itemIds = [];

        usort($records, fn ($a, $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $legacyId = (string) ($record['id'] ?? '');
            if ($legacyId === '' || $this->existingMap($farm->id, 'inventoryRecords', $legacyId)) {
                $counts['inventoryRecordsSkipped']++;
                continue;
            }

            $name = trim((string) ($record['itemName'] ?? 'Imported Inventory Item'));
            $key = Str::lower($name).'|'.Str::lower((string) ($record['category'] ?? ''));

            if (!isset($itemIds[$key])) {
                $itemIds[$key] = (int) DB::table('inventory_items')->insertGetId([
                    'farm_id' => $farm->id,
                    'name' => $name,
                    'sku' => null,
                    'category' => $record['category'] ?? null,
                    'unit' => $record['unit'] ?? 'unit',
                    'balance' => 0,
                    'reorder_level' => (float) ($record['reorderLevel'] ?? 0),
                    'supplier' => $record['supplier'] ?? null,
                    'unit_cost' => (float) ($record['unitCost'] ?? 0),
                    'storage_location' => $record['storageLocation'] ?? null,
                    'expiry_date' => $this->date($record['expiryDate'] ?? null),
                    'notes' => $record['notes'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $itemId = $itemIds[$key];
            $qtyIn = max(0, (float) ($record['qtyIn'] ?? 0));
            $qtyOut = max(0, (float) ($record['qtyOut'] ?? 0));
            $balance = (float) ($record['balance'] ?? ($qtyIn - $qtyOut));

            if ($qtyIn > 0 && $qtyOut > 0) {
                $type = 'adjustment';
                $quantity = abs($qtyIn - $qtyOut);
            } elseif ($qtyIn > 0) {
                $type = 'stock_in';
                $quantity = $qtyIn;
            } elseif ($qtyOut > 0) {
                $type = 'stock_out';
                $quantity = $qtyOut;
            } else {
                $type = 'adjustment';
                $quantity = 0;
            }

            $movementId = DB::table('inventory_movements')->insertGetId([
                'farm_id' => $farm->id,
                'inventory_item_id' => $itemId,
                'recorded_by' => $user->id,
                'type' => $type,
                'quantity' => $quantity,
                'balance_after' => $balance,
                'reference_type' => 'legacy_poultryplus',
                'reference_id' => $legacyId,
                'notes' => trim(implode("\n", array_filter([
                    $record['notes'] ?? null,
                    isset($record['date']) ? 'Legacy date: '.$record['date'] : null,
                ]))) ?: null,
                'created_at' => $this->dateTime($record['createdAt'] ?? null) ?? now(),
                'updated_at' => $this->dateTime($record['updatedAt'] ?? null) ?? now(),
            ]);

            DB::table('inventory_items')->where('id', $itemId)->update([
                'balance' => $balance,
                'updated_at' => now(),
            ]);

            $this->map($import, $farm, 'inventoryRecords', $legacyId, 'inventory_movements', $movementId);
            $counts['inventoryRecords']++;
        }
    }

    private function importWeights(array $records, Farm $farm, User $user, LegacyImport $import, array $batchMap, array &$counts, array &$warnings): void
    {
        $this->importBatchRecords('weeklyBodyWeightRecords', 'weekly_weights', $records, $farm, $user, $import, $batchMap, $counts, $warnings,
            fn (array $r, int $batchId) => [
                'batch_id' => $batchId,
                'recorded_by' => $user->id,
                'record_date' => $this->date($r['date'] ?? $r['recordDate'] ?? null),
                'week' => max(0, (int) ($r['weekNumber'] ?? 0)),
                'sample_size' => max(0, (int) ($r['sampleSize'] ?? 0)),
                'average_weight' => (float) ($r['averageWeightKg'] ?? 0),
                'minimum_weight' => isset($r['minWeightKg']) ? (float) $r['minWeightKg'] : null,
                'maximum_weight' => isset($r['maxWeightKg']) ? (float) $r['maxWeightKg'] : null,
                'target_weight' => isset($r['targetWeightKg']) ? (float) $r['targetWeightKg'] : null,
                'notes' => trim(implode("\n", array_filter([
                    $r['notes'] ?? null,
                    isset($r['birdAge']) ? 'Legacy bird age: '.$r['birdAge'].' days' : null,
                ]))) ?: null,
            ]);
    }

    private function importAudit(array $records, Farm $farm, User $user, LegacyImport $import, array &$counts): void
    {
        $counts['auditLogs'] = 0;
        $counts['auditLogsSkipped'] = 0;

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $legacyId = (string) ($record['id'] ?? '');
            if ($legacyId === '' || $this->existingMap($farm->id, 'auditLogs', $legacyId)) {
                $counts['auditLogsSkipped']++;
                continue;
            }

            $id = DB::table('audit_logs')->insertGetId([
                'farm_id' => $farm->id,
                'user_id' => $user->id,
                'action' => 'legacy.'.Str::slug((string) ($record['action'] ?? $record['type'] ?? 'activity'), '.'),
                'resource_type' => 'PoultryPlusLegacyRecord',
                'resource_id' => $legacyId,
                'metadata' => json_encode(['legacy' => $record], JSON_THROW_ON_ERROR),
                'ip_address' => null,
                'user_agent' => 'PoultryPlus static import',
                'created_at' => $this->dateTime($record['createdAt'] ?? $record['date'] ?? null) ?? now(),
            ]);

            $this->map($import, $farm, 'auditLogs', $legacyId, 'audit_logs', $id);
            $counts['auditLogs']++;
        }
    }

    private function preserveSettingsAndDerivedData(array $data, Farm $farm, array &$counts): void
    {
        if (!empty($data['settings'])) {
            DB::table('farm_settings')->updateOrInsert(
                ['farm_id' => $farm->id, 'key' => 'legacy_poultryplus_settings'],
                [
                    'value' => json_encode($data['settings'], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (!empty($data['performanceSummaries'])) {
            DB::table('farm_settings')->updateOrInsert(
                ['farm_id' => $farm->id, 'key' => 'legacy_performance_summaries'],
                [
                    'value' => json_encode($data['performanceSummaries'], JSON_THROW_ON_ERROR),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
            $counts['performanceSummariesPreserved'] = count($data['performanceSummaries']);
        }

        $counts['legacyUsersPreservedInArchive'] = count($data['users'] ?? []);
        $counts['legacySettings'] = count($data['settings'] ?? []);
    }

    private function importBatchRecords(
        string $store,
        string $table,
        array $records,
        Farm $farm,
        User $user,
        LegacyImport $import,
        array $batchMap,
        array &$counts,
        array &$warnings,
        callable $transform,
        string $dateColumn = 'record_date'
    ): void {
        $counts[$store] = 0;
        $counts[$store.'Skipped'] = 0;

        foreach ($records as $record) {
            if (!is_array($record)) {
                continue;
            }

            $legacyId = (string) ($record['id'] ?? '');
            if ($legacyId === '' || $this->existingMap($farm->id, $store, $legacyId)) {
                $counts[$store.'Skipped']++;
                continue;
            }

            $legacyBatchId = (string) ($record['batchId'] ?? '');
            $batchId = $batchMap[$legacyBatchId] ?? null;
            if (!$batchId) {
                $counts[$store.'Skipped']++;
                $warnings[] = "{$store}: one or more records were skipped because their legacy batch could not be mapped.";
                $this->map($import, $farm, $store, $legacyId, $table, null, 'skipped', 'Legacy batch not found.');
                continue;
            }

            $payload = $transform($record, $batchId);

            if (empty($payload[$dateColumn])) {
                $payload[$dateColumn] = now()->toDateString();
                $warnings[] = "{$store}: one or more records had no valid date and were assigned the import date.";
            }

            $payload['farm_id'] = $farm->id;
            $payload['created_at'] = $this->dateTime($record['createdAt'] ?? null) ?? now();
            $payload['updated_at'] = $this->dateTime($record['updatedAt'] ?? null) ?? now();

            if (in_array($table, [
                'daily_records', 'feed_records', 'medication_records', 'mortality_records',
                'egg_production_records', 'sales', 'expenses', 'weekly_weights',
            ], true)) {
                $payload['deleted_at'] = ($record['deleted'] ?? false)
                    ? ($this->dateTime($record['deletedAt'] ?? null) ?? now())
                    : null;
            }

            $id = DB::table($table)->insertGetId($payload);
            $this->map($import, $farm, $store, $legacyId, $table, $id);
            $counts[$store]++;
        }
    }

    private function recalculateBatchPopulations(Farm $farm): void
    {
        $batches = DB::table('batches')->where('farm_id', $farm->id)->get();

        foreach ($batches as $batch) {
            $mortality = (int) DB::table('mortality_records')
                ->where('farm_id', $farm->id)
                ->where('batch_id', $batch->id)
                ->whereNull('deleted_at')
                ->sum('number_dead');

            $sold = (int) DB::table('sales')
                ->where('farm_id', $farm->id)
                ->where('batch_id', $batch->id)
                ->whereNull('deleted_at')
                ->where(function ($query) use ($batch) {
                    if ($batch->production_type === 'broiler') {
                        $query->whereRaw('1 = 1');
                    } else {
                        $query->where(function ($nested) {
                            $nested->whereRaw('LOWER(item) LIKE ?', ['%bird%'])
                                ->orWhereRaw('LOWER(item) LIKE ?', ['%hen%'])
                                ->orWhereRaw('LOWER(item) LIKE ?', ['%layer%'])
                                ->orWhereRaw('LOWER(item) LIKE ?', ['%broiler%']);
                        });
                    }
                })
                ->sum('quantity');

            DB::table('batches')->where('id', $batch->id)->update([
                'current_birds' => max(0, (int) $batch->initial_birds - $mortality - $sold),
                'updated_at' => now(),
            ]);
        }
    }

    private function existingMap(int $farmId, string $store, string $legacyId): ?LegacyRecordMap
    {
        return LegacyRecordMap::query()
            ->where('farm_id', $farmId)
            ->where('store_name', $store)
            ->where('legacy_id', $legacyId)
            ->first();
    }

    private function map(
        LegacyImport $import,
        Farm $farm,
        string $store,
        string $legacyId,
        string $targetTable,
        ?int $targetId,
        string $status = 'imported',
        ?string $note = null
    ): void {
        LegacyRecordMap::updateOrCreate(
            [
                'farm_id' => $farm->id,
                'store_name' => $store,
                'legacy_id' => $legacyId,
            ],
            [
                'legacy_import_id' => $import->id,
                'target_table' => $targetTable,
                'target_id' => $targetId,
                'status' => $status,
                'note' => $note,
            ]
        );
    }

    private function medicationStatus(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'scheduled',
            'due' => 'due',
            'completed' => 'completed',
            'skipped', 'missed' => 'missed',
            default => 'scheduled',
        };
    }

    private function date(mixed $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value)->toDateString();
        } catch (Throwable) {
            return null;
        }
    }

    private function dateTime(mixed $value): ?Carbon
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse((string) $value);
        } catch (Throwable) {
            return null;
        }
    }
}
