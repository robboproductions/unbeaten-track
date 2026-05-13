@extends('layouts.admin')

@section('title', 'Narrations · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Narrations</div>
            <div class="admin-page-subtitle">{{ $narrations->total() }} audio {{ \Illuminate\Support\Str::plural('file', $narrations->total()) }} (newest first)</div>
        </div>
    </div>

    <div class="admin-content" style="padding-top:16px;">
        <div class="card">
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:13px;">
                    <thead>
                        <tr style="background:var(--color-river-stone);border-bottom:1px solid var(--color-border);text-align:left;">
                            <th style="padding:10px 14px;font-weight:600;color:var(--color-charcoal);">Type</th>
                            <th style="padding:10px 14px;font-weight:600;color:var(--color-charcoal);">Name</th>
                            <th style="padding:10px 14px;font-weight:600;color:var(--color-charcoal);">Voice</th>
                            <th style="padding:10px 14px;font-weight:600;color:var(--color-charcoal);">Generated</th>
                            <th style="padding:10px 14px;font-weight:600;color:var(--color-charcoal);">Play</th>
                            <th style="padding:10px 14px;font-weight:600;color:var(--color-charcoal);text-align:right;">Open</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($narrations as $row)
                            <tr style="border-bottom:1px solid var(--color-border);vertical-align:middle;">
                                <td style="padding:11px 14px;white-space:nowrap;">
                                    @if ($row->kind === 'town')
                                        <span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:var(--badge-saved-bg);color:var(--badge-saved-text);">Town</span>
                                    @else
                                        <span style="display:inline-block;font-size:10px;font-weight:600;padding:2px 7px;border-radius:4px;background:var(--badge-in-progress-bg);color:var(--badge-in-progress-text);">POI</span>
                                    @endif
                                </td>
                                <td style="padding:11px 14px;">
                                    <div style="font-weight:600;color:var(--color-near-black);">{{ $row->name }}</div>
                                    @if (filled($row->subtitle))
                                        <div style="font-size:12px;color:var(--color-mid-grey);margin-top:2px;">{{ $row->subtitle }}</div>
                                    @endif
                                </td>
                                <td style="padding:11px 14px;color:var(--color-charcoal);">{{ $row->voice_label }}</td>
                                <td style="padding:11px 14px;color:var(--color-mid-grey);font-size:12px;white-space:nowrap;">
                                    {{ $row->generated_at?->timezone(config('app.timezone'))->format('j M Y, g:i a') ?? '—' }}
                                </td>
                                <td style="padding:11px 14px;">
                                    @if (filled($row->audio_url))
                                        <audio controls preload="none" src="{{ $row->audio_url }}" style="height:32px;max-width:220px;"></audio>
                                    @else
                                        <span style="color:var(--color-mid-grey);">—</span>
                                    @endif
                                </td>
                                <td style="padding:11px 14px;text-align:right;">
                                    <a class="btn btn-neutral btn-sm" href="{{ $row->edit_url }}">{{ $row->edit_label }}</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding:20px 16px;color:var(--color-mid-grey);">
                                    No narrations yet. Generate audio from a town or POI edit screen.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($narrations->total() > 0)
                <div style="padding:14px 16px;border-top:1px solid var(--color-border);background:var(--color-white);">
                    {{ $narrations->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
