<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\Town;
use App\Support\NarrationVoiceCatalog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class NarrationListController extends Controller
{
    public function index(Request $request): View
    {
        $pois = Poi::query()
            ->whereNotNull('narration_audio_path')
            ->where('narration_audio_path', '!=', '')
            ->with(['town:id,name'])
            ->get(['id', 'name', 'town_id', 'narration_generated_at', 'narration_voice_id', 'narration_voice_label', 'narration_audio_path']);

        $towns = Town::query()
            ->whereNotNull('narration_audio_path')
            ->where('narration_audio_path', '!=', '')
            ->get(['id', 'name', 'narration_generated_at', 'narration_voice_id', 'narration_voice_label', 'narration_audio_path']);

        $rows = collect();

        foreach ($pois as $poi) {
            $rows->push((object) [
                'kind' => 'poi',
                'id' => $poi->id,
                'name' => $poi->name,
                'subtitle' => $poi->town?->name,
                'voice_label' => NarrationVoiceCatalog::displayLabel($poi->narration_voice_label, $poi->narration_voice_id),
                'generated_at' => $poi->narration_generated_at,
                'audio_url' => $poi->narration_audio_url,
                'edit_url' => route('admin.pois.edit', $poi),
                'edit_label' => 'Open POI',
            ]);
        }

        foreach ($towns as $town) {
            $rows->push((object) [
                'kind' => 'town',
                'id' => $town->id,
                'name' => $town->name,
                'subtitle' => null,
                'voice_label' => NarrationVoiceCatalog::displayLabel($town->narration_voice_label, $town->narration_voice_id),
                'generated_at' => $town->narration_generated_at,
                'audio_url' => $town->narration_audio_url,
                'edit_url' => route('admin.towns.edit', $town),
                'edit_label' => 'Open town',
            ]);
        }

        $sorted = $rows->sortByDesc(function (object $r): int {
            return $r->generated_at?->getTimestamp() ?? 0;
        })->values();

        $perPage = min(max((int) $request->integer('per_page', 25), 10), 100);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $sorted->count();
        $slice = $sorted->slice(($page - 1) * $perPage, $perPage)->values();

        $narrations = new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => 'page',
                'query' => $request->query(),
            ]
        );

        return view('admin.narrations.index', [
            'narrations' => $narrations,
        ]);
    }
}
