@extends('layouts.superuser')
@section('title','MCP')
@section('page_title','MCP')
@section('content')
<div class="page-head"><div><h2>MCP Configuration</h2><p>Reserved exclusively for the Farmers Superuser.</p></div></div>
<div class="grid two-col">
<div class="card"><div class="card-header"><h3>MCP Gateway</h3><span class="badge badge-warning">Foundation</span></div><div class="card-body">
<div class="warning-box">The secure MCP control surface is scaffolded but external MCP actions are intentionally disabled until authenticated capability endpoints and token rotation are completed.</div>
<div class="divider"></div>
<div class="mobile-records">
<div class="mobile-record"><div class="mr-top"><h4>Tenant isolation</h4><span class="badge badge-success">Enforced</span></div><p>MCP must never bypass farm boundaries.</p></div>
<div class="mobile-record"><div class="mr-top"><h4>Secrets</h4><span class="badge badge-success">Protected</span></div><p>Password hashes, sessions, APP_KEY, database credentials and SMTP passwords are not exposed.</p></div>
<div class="mobile-record"><div class="mr-top"><h4>Audit</h4><span class="badge badge-success">Required</span></div><p>Every future MCP mutation will be written to the audit log.</p></div>
</div>
</div></div>
<div class="card"><div class="card-header"><h3>Planned capabilities</h3></div><div class="card-body"><ul style="margin:0;padding-left:18px;font-size:13px;line-height:1.9"><li>Inspect module registry</li><li>Inspect safe schema metadata</li><li>Inspect permissions and feature flags</li><li>Read feature requests</li><li>Approved module scaffolding</li><li>Diagnostics</li><li>Token issuance, rotation and revocation</li></ul></div></div>
</div>
@endsection
