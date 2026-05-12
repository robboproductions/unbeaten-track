@extends('layouts.admin')

@section('title', 'Add town · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Add town</div>
            <div class="admin-page-subtitle">Create a new town profile</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index') }}">← Back to towns</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card town-form-page-card">
            @php
                $townFormId = 'admin-town-form-create';
            @endphp

            <div class="town-form-two-col">
                <form id="{{ $townFormId }}" method="post" action="{{ route('admin.towns.store') }}" enctype="multipart/form-data" class="town-form-main">
                    @csrf

                    @include('admin.towns._town_form_fields', [
                        'town' => null,
                        'stateOptions' => $stateOptions,
                        'regionsByState' => $regionsByState,
                        'selectedState' => old('state', 'New South Wales'),
                        'selectedRegion' => (string) old('region', ''),
                        'adminMap' => $adminMap,
                        'townAboutAi' => $townAboutAi,
                    ])
                </form>

                @include('admin.towns._town_form_sidebar', ['town' => null, 'mainFormId' => $townFormId, 'adminMap' => $adminMap])
            </div>

            <div class="town-form-footer-actions">
                <input class="btn btn-primary btn-sm" type="submit" form="{{ $townFormId }}" value="Save town" />
                <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index') }}">Cancel</a>
            </div>
        </div>
    </div>
@endsection
