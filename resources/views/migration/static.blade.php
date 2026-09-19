@extends('layouts.app')
@section('title','Import PoultryPlus')
@section('page_title','Import PoultryPlus')
@section('content')
<div class="page-head"><div><h2>Move your static farm data to Farmers</h2><p>Import an existing PoultryPlus JSON backup into <strong>{{ $farm->name }}</strong>.</p></div></div>

<div class="grid two-col">
<div class="card"><div class="card-header"><h3>Legacy backup importer</h3><span class="badge badge-success">Tenant-safe</span></div><div class="card-body">
<div class="safe-box" style="margin-bottom:12px"><strong>Your Laravel account remains unchanged.</strong> Old static-site passwords and sessions are never imported. Farm records are mapped into this farm workspace only.</div>
<form method="POST" action="{{ route('migration.static.store') }}" enctype="multipart/form-data" id="legacyImportForm">@csrf
<div class="import-drop">
<div style="font-size:32px">⇪</div><strong>Select PoultryPlus backup</strong>
<p class="hint">Choose the <code>poultryplus_backup_*.json</code> file exported from the static app. Maximum 20 MB.</p>
<input class="input" id="legacyBackup" type="file" name="backup" accept=".json,application/json" required>
</div>
<div class="import-preview" id="importPreview">
<div class="divider"></div>
<h3 style="font-size:14px;margin:0 0 8px">Backup preview</h3>
<div class="count-grid" id="importCounts"></div>
<div class="hint" style="margin-top:10px" id="importMeta"></div>
</div>
<div class="warning-box" style="margin-top:14px">The importer checks that the JSON contains one legacy farm, maps legacy batch IDs to Laravel IDs, skips duplicate legacy records, archives the original JSON privately, and recalculates current bird populations after import.</div>
<label style="display:flex;gap:9px;align-items:flex-start;font-size:12px;margin:14px 0"><input type="checkbox" name="confirmation" value="1" required> I confirm this backup belongs to my farm and I want to merge its records into <strong>{{ $farm->name }}</strong>.</label>
<button class="btn btn-primary btn-block" type="submit">Import into {{ $farm->name }}</button>
</form>
</div></div>

<div class="card"><div class="card-header"><h3>What is imported?</h3></div><div class="card-body">
<div class="mobile-records">
@foreach(['Batches','Daily Records','Feed Records','Medication / Vaccination','Mortality','Egg Production','Sales','Expenses','Inventory movements','Weekly Body Weight','Settings & farm profile','Audit history'] as $label)
<div class="mobile-record"><div class="mr-top"><h4>{{ $label }}</h4><span class="badge badge-success">✓</span></div></div>
@endforeach
</div>
<div class="divider"></div>
<p class="hint"><strong>Performance summaries:</strong> the raw legacy summaries are preserved, while Farmers recalculates live performance from the imported source records.</p>
</div></div>
</div>

<div class="card" style="margin-top:16px"><div class="card-header"><h3>Import history</h3></div><div class="table-wrap"><table class="data-table"><thead><tr><th>Date</th><th>Source Farm</th><th>Status</th><th>Source Version</th><th>Records</th></tr></thead><tbody>
@forelse($history as $import)<tr><td>{{ $import->created_at->format('d M Y H:i') }}</td><td>{{ $import->source_farm_name ?: 'Legacy Farm' }}</td><td><span class="badge {{ $import->status==='completed'?'badge-success':($import->status==='failed'?'badge-danger':'badge-warning') }}">{{ ucfirst($import->status) }}</span></td><td>{{ $import->source_version ?? '—' }}</td><td>{{ collect($import->counts ?? [])->filter(fn($v,$k)=>is_numeric($v) && !str_ends_with($k,'Skipped'))->sum() }}</td></tr>@empty<tr><td colspan="5"><div class="empty">No legacy imports yet.</div></td></tr>@endforelse
</tbody></table></div></div>
@endsection
@push('scripts')
<script>
(() => {
 const input=document.getElementById('legacyBackup'), preview=document.getElementById('importPreview'), counts=document.getElementById('importCounts'), meta=document.getElementById('importMeta');
 const labels={batches:'Batches',dailyRecords:'Daily',feedRecords:'Feed',vaccinationRecords:'Medication',mortalityRecords:'Mortality',eggProductionRecords:'Eggs',salesRecords:'Sales',expenseRecords:'Expenses',inventoryRecords:'Inventory',weeklyBodyWeightRecords:'Weights',auditLogs:'Audit',settings:'Settings'};
 input?.addEventListener('change', async () => {
   preview.style.display='none'; counts.innerHTML=''; meta.textContent='';
   const file=input.files?.[0]; if(!file) return;
   try {
     const data=JSON.parse(await file.text());
     const recognized=Object.keys(labels).filter(k=>Array.isArray(data[k]));
     if(!data._farmId || recognized.length===0) throw new Error('Not a recognized PoultryPlus backup');
     recognized.forEach(k=>{const div=document.createElement('div');div.className='count-item';div.innerHTML='<strong>'+data[k].length+'</strong>'+labels[k];counts.appendChild(div)});
     const farm=Array.isArray(data.farms)&&data.farms[0]?(data.farms[0].farmName||data.farms[0].name||'Legacy Farm'):'Legacy Farm';
     meta.textContent='Farm: '+farm+' · Version: '+(data._version??'unknown')+' · Exported: '+(data._exportedAt??'unknown');
     preview.style.display='block';
   } catch(e) { meta.textContent=''; alert('This does not appear to be a valid PoultryPlus JSON backup.'); input.value=''; }
 });
})();
</script>
@endpush
