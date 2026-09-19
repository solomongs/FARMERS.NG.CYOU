@extends('layouts.app')
@section('title','Install Farmers')
@section('page_title','Install App')
@section('content')
<div class="page-head"><div><h2>Install Farmers on your device</h2><p>Use Farmers like a native app on phone, tablet or computer.</p></div></div>
<div class="grid two-col">
<div class="card"><div class="card-header"><h3>Install this app</h3></div><div class="card-body">
<div class="safe-box">Farmers is a Progressive Web App (PWA). Once installed, it opens in its own app window and keeps the mobile navigation optimized for touch.</div>
<button class="btn btn-primary btn-block hidden" style="margin-top:14px" data-install-app>Install Farmers</button>
<div id="iosHelp" class="warning-box" style="margin-top:12px">On iPhone/iPad: open Farmers in <strong>Safari</strong> → tap <strong>Share</strong> → <strong>Add to Home Screen</strong>.</div>
</div></div>
<div class="card"><div class="card-header"><h3>Device guide</h3></div><div class="card-body"><div class="mobile-records">
<div class="mobile-record"><div class="mr-top"><h4>Android</h4><span class="badge">Chrome</span></div><p>Open Farmers in Chrome and choose Install App or Add to Home screen.</p></div>
<div class="mobile-record"><div class="mr-top"><h4>iPhone / iPad</h4><span class="badge">Safari</span></div><p>Use Safari's Share menu and choose Add to Home Screen.</p></div>
<div class="mobile-record"><div class="mr-top"><h4>Windows / macOS / Linux</h4><span class="badge">Chrome / Edge</span></div><p>Use the browser install icon or the Install Farmers button when available.</p></div>
</div></div></div>
</div>
@endsection
