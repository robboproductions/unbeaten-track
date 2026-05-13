@extends('layouts.admin')

@section('title', 'Add POI · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Add POI</div>
            <div class="admin-page-subtitle">Create a new point of interest</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">← Back to POIs</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card town-form-page-card">
            @php
                $poiFormId = 'admin-poi-form-create';
            @endphp

            <div class="town-form-two-col">
                <form id="{{ $poiFormId }}" method="post" action="{{ route('admin.pois.store') }}" enctype="multipart/form-data" class="town-form-main">
                    @csrf

                    @include('admin.pois._poi_form_fields', [
                        'poi' => null,
                        'towns' => $towns,
                        'stateOptions' => $stateOptions,
                        'categoryOptions' => $categoryOptions,
                        'adminPoiMap' => $adminPoiMap,
                        'poiAboutAi' => $poiAboutAi,
                        'poiNarrationAi' => $poiNarrationAi,
                    ])
                </form>

                @include('admin.pois._poi_form_sidebar', ['poi' => null, 'mainFormId' => $poiFormId, 'adminPoiMap' => $adminPoiMap])
            </div>

            <div class="town-form-footer-actions">
                <input class="btn btn-primary btn-sm" type="submit" form="{{ $poiFormId }}" value="Save POI" />
                <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">Cancel</a>
            </div>
        </div>
    </div>
@endsection
