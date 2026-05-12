@extends('layouts.admin')

@section('title', 'Dashboard · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Dashboard</div>
            <div class="admin-page-subtitle">Good morning — here's what's happening</div>
        </div>
        <div class="admin-page-actions">
            <span class="btn btn-neutral btn-sm">Refresh</span>
            <a class="btn btn-primary btn-sm" href="{{ route('admin.towns.create') }}">Add content</a>
        </div>
    </div>

    <div class="admin-stats-row">
        <div class="admin-stat-card">
            <div class="admin-stat-label">Towns</div>
            <div class="admin-stat-value">{{ \App\Models\Town::count() }}</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-label">POIs</div>
            <div class="admin-stat-value">{{ \App\Models\Poi::count() }}</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-label">Pending review</div>
            <div class="admin-stat-value">{{ \App\Models\Poi::where('status', 'pending')->count() }}</div>
        </div>
        <div class="admin-stat-card">
            <div class="admin-stat-label">Published POIs</div>
            <div class="admin-stat-value">{{ \App\Models\Poi::where('status', 'published')->count() }}</div>
        </div>
    </div>

    <div class="admin-content">
        <div class="card">
            <div class="card-header">
                <div>
                    <div class="card-title">Next steps</div>
                    <div class="card-subtitle">Start adding content</div>
                </div>
            </div>
            <div style="padding:14px 16px;">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.towns.create') }}">+ Add town</a>
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index') }}">View towns</a>
                    <a class="btn btn-primary btn-sm" href="{{ route('admin.pois.create') }}">+ Add POI</a>
                    <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">View POIs</a>
                </div>
            </div>
        </div>
    </div>
@endsection

