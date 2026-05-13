@extends('layouts.admin')

@section('title', 'Narrations · Admin')

@section('content')
    <div class="admin-page-header">
        <div>
            <div class="admin-page-title">Narrations</div>
            <div class="admin-page-subtitle">{{ $narrations->total() }} audio {{ \Illuminate\Support\Str::plural('file', $narrations->total()) }} (newest first)</div>
        </div>
    </div>

    <div class="admin-content" style="padding-top:12px;">
        <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-start;">
            <div style="flex:1 1 480px;min-width:0;">
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
                                        <td style="padding:11px 14px;color:var(--color-charcoal);">
                                            @if (filled($row->voice_portrait_url))
                                                <div style="display:flex;align-items:center;gap:8px;">
                                                    <img
                                                        src="{{ $row->voice_portrait_url }}"
                                                        alt="{{ $row->voice_label }} portrait"
                                                        width="48"
                                                        height="48"
                                                        style="width:48px;height:48px;border-radius:10px;object-fit:cover;border:1px solid var(--color-border);flex-shrink:0;background:var(--color-river-stone);"
                                                        loading="lazy"
                                                        decoding="async"
                                                    />
                                                    <span>{{ $row->voice_label }}</span>
                                                </div>
                                            @else
                                                {{ $row->voice_label }}
                                            @endif
                                        </td>
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

            <aside style="flex:0 1 300px;width:100%;max-width:300px;">
                <div class="card" style="height:100%;">
                    <div style="padding:10px 12px;border-bottom:1px solid var(--color-border);background:var(--color-river-stone);">
                        <div style="font-weight:600;font-size:14px;color:var(--color-near-black);line-height:1.25;">Narrators</div>
                        <div style="font-size:11px;color:var(--color-mid-grey);margin-top:2px;line-height:1.35;">Voices for town and POI narration.</div>
                    </div>
                    <div style="padding:10px 10px 12px;display:flex;flex-direction:column;gap:12px;">
                        @foreach ($narrator_showcase as $narrator)
                            <div style="text-align:center;@unless($loop->last)padding-bottom:12px;border-bottom:1px solid var(--color-border);@endunless">
                                @if (filled($narrator['portrait_url']))
                                    <img
                                        src="{{ $narrator['portrait_url'] }}"
                                        alt="{{ $narrator['label'] }} portrait"
                                        width="152"
                                        height="152"
                                        style="width:152px;height:152px;max-width:100%;border-radius:12px;object-fit:cover;border:1px solid var(--color-border);background:var(--color-river-stone);box-shadow:0 1px 2px rgba(0,0,0,0.05);"
                                        loading="lazy"
                                        decoding="async"
                                    />
                                @else
                                    <div style="width:100%;max-width:152px;aspect-ratio:1;margin:0 auto;border-radius:12px;background:var(--color-river-stone);border:1px dashed var(--color-border);display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--color-mid-grey);padding:6px;">No photo</div>
                                @endif
                                <div style="font-weight:600;font-size:15px;color:var(--color-near-black);margin-top:6px;line-height:1.2;">{{ $narrator['label'] }}</div>
                                @if (filled($narrator['intro_url']))
                                    <audio controls preload="none" src="{{ $narrator['intro_url'] }}" style="width:100%;max-width:280px;height:36px;margin-top:6px;"></audio>
                                @endif
                            </div>
                        @endforeach
                        @if (count($narrator_showcase) === 0)
                            <p style="margin:0;font-size:13px;color:var(--color-mid-grey);">No voices are configured in <code style="font-size:12px;">config/poi_narration.php</code>.</p>
                        @endif
                    </div>
                </div>
            </aside>
        </div>
    </div>
@endsection
