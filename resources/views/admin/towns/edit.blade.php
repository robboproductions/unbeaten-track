@extends('layouts.admin')

@section('title', 'Edit town · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Edit town</div>
            <div class="admin-page-subtitle">{{ $town->name }}</div>
        </div>
        <div class="admin-page-actions">
            <a class="btn btn-neutral btn-sm" href="{{ route('admin.towns.index') }}">← Back to towns</a>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card town-form-page-card">
            @php
                $townFormId = 'admin-town-form-edit-' . $town->id;
            @endphp

            <div class="town-form-two-col">
                <form id="{{ $townFormId }}" method="post" action="{{ route('admin.towns.update', $town) }}" enctype="multipart/form-data" class="town-form-main">
                    @csrf
                    @method('put')

                    @include('admin.towns._town_form_fields', [
                        'town' => $town,
                        'stateOptions' => $stateOptions,
                        'regionsByState' => $regionsByState,
                        'selectedState' => old('state', $town->state),
                        'selectedRegion' => (string) old('region', $town->region ?? ''),
                        'adminMap' => $adminMap,
                        'townAboutAi' => $townAboutAi,
                        'townNarrationAi' => $townNarrationAi,
                        'townNarration' => $townNarration,
                    ])
                </form>

                @php
                    $narrationVoiceBaxterId = (string) data_get(config('poi_narration.voices', []), 'baxter.id', '');
                    $narrationVoiceZoeId = (string) data_get(config('poi_narration.voices', []), 'zoe.id', '');
                @endphp
                <form id="ut-town-narration-generate-baxter-{{ $town->id }}" method="post" action="{{ route('admin.towns.narration.generate', $town) }}" hidden aria-hidden="true">
                    @csrf
                    <input type="hidden" name="narration_voice_id" value="{{ $narrationVoiceBaxterId }}">
                </form>
                <form id="ut-town-narration-generate-zoe-{{ $town->id }}" method="post" action="{{ route('admin.towns.narration.generate', $town) }}" hidden aria-hidden="true">
                    @csrf
                    <input type="hidden" name="narration_voice_id" value="{{ $narrationVoiceZoeId }}">
                </form>
                <form id="ut-town-narration-destroy-{{ $town->id }}" method="post" action="{{ route('admin.towns.narration.destroy', $town) }}" hidden aria-hidden="true">
                    @csrf
                    @method('delete')
                </form>

                @include('admin.towns._town_form_sidebar', ['town' => $town, 'mainFormId' => $townFormId, 'adminMap' => $adminMap])
            </div>

            <div class="town-form-footer-actions">
                <input class="btn btn-primary btn-sm" type="submit" form="{{ $townFormId }}" value="Save changes" />
                <span class="town-form-footer-status">
                    @if (session('status')) {{ session('status') }} @endif
                </span>
            </div>
        </div>
    </div>
@endsection
