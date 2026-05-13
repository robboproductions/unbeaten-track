@extends('layouts.admin')

@section('title', 'Edit POI · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Edit POI</div>
            <div class="admin-page-subtitle">{{ $poi->name }} · {{ implode(' · ', $poi->categoryList()) ?: '—' }} · {{ $poi->town?->name }} · {{ $poi->state }}</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.pois.index') }}">← Back to POIs</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card town-form-page-card">
            @php
                $poiFormId = 'admin-poi-form-edit-' . $poi->id;
            @endphp

            <div class="town-form-two-col">
                <form id="{{ $poiFormId }}" method="post" action="{{ route('admin.pois.update', $poi) }}" enctype="multipart/form-data" class="town-form-main">
                    @csrf
                    @method('put')

                    @include('admin.pois._poi_form_fields', [
                        'poi' => $poi,
                        'towns' => $towns,
                        'stateOptions' => $stateOptions,
                        'categoryOptions' => $categoryOptions,
                        'adminPoiMap' => $adminPoiMap,
                        'poiAboutAi' => $poiAboutAi,
                        'poiNarrationAi' => $poiNarrationAi,
                        'poiNarration' => $poiNarration,
                    ])
                </form>

                {{-- Must stay outside the main <form>: nested forms are invalid HTML and break submit buttons in some browsers. --}}
                @php
                    $narrationVoiceTerryId = (string) data_get(config('poi_narration.voices', []), 'terry.id', '');
                    $narrationVoiceSarahId = (string) data_get(config('poi_narration.voices', []), 'sarah.id', '');
                @endphp
                <form id="ut-poi-narration-generate-terry-{{ $poi->id }}" method="post" action="{{ route('admin.pois.narration.generate', $poi) }}" hidden aria-hidden="true">
                    @csrf
                    <input type="hidden" name="narration_voice_id" value="{{ $narrationVoiceTerryId }}">
                </form>
                <form id="ut-poi-narration-generate-sarah-{{ $poi->id }}" method="post" action="{{ route('admin.pois.narration.generate', $poi) }}" hidden aria-hidden="true">
                    @csrf
                    <input type="hidden" name="narration_voice_id" value="{{ $narrationVoiceSarahId }}">
                </form>
                <form id="ut-poi-narration-destroy-{{ $poi->id }}" method="post" action="{{ route('admin.pois.narration.destroy', $poi) }}" hidden aria-hidden="true">
                    @csrf
                    @method('delete')
                </form>

                @include('admin.pois._poi_form_sidebar', ['poi' => $poi, 'mainFormId' => $poiFormId, 'adminPoiMap' => $adminPoiMap])
            </div>

            <div class="town-form-footer-actions">
                <input class="btn btn-primary btn-sm" type="submit" form="{{ $poiFormId }}" value="Save changes" />
                <span class="town-form-footer-status">
                    @if (session('status')) {{ session('status') }} @endif
                </span>
            </div>
        </div>
    </div>
@endsection
