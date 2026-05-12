<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PoiVerificationStatus;
use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\Town;
use App\Support\AustraliaGeography;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PoiController extends Controller
{
    public function index(Request $request)
    {
        $query = Poi::query()->with('town')->orderBy('name');

        if ($request->filled('q')) {
            $query->where('name', 'like', '%' . $request->string('q') . '%');
        }

        if ($request->filled('state')) {
            $query->where('state', $request->string('state'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('category')) {
            $query->whereJsonContains('categories', $request->string('category'));
        }

        if ($request->filled('verification_status')) {
            $query->where('verification_status', $request->string('verification_status'));
        }

        $pois = $query->paginate(12)->withQueryString();

        return view('admin.pois.index', [
            'pois' => $pois,
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => config('poi_taxonomy.categories', []),
        ]);
    }

    public function create()
    {
        return view('admin.pois.create', [
            'towns' => Town::query()->orderBy('name')->get(),
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => config('poi_taxonomy.categories', []),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validatedPoi($request, null);

        $poi = Poi::create($validated);

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'POI created.');
    }

    public function edit(Poi $poi)
    {
        return view('admin.pois.edit', [
            'poi' => $poi->load('town'),
            'towns' => Town::query()->orderBy('name')->get(),
            'stateOptions' => AustraliaGeography::states(),
            'categoryOptions' => $this->categoryOptionsForPoi($poi),
        ]);
    }

    public function update(Request $request, Poi $poi)
    {
        $poi->update($this->validatedPoi($request, $poi));

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'Changes saved.');
    }

    public function destroy(Poi $poi)
    {
        $poi->delete();

        return redirect()->route('admin.pois.index')->with('status', 'POI deleted.');
    }

    /**
     * @return list<string>
     */
    private function categoryOptionsForPoi(?Poi $poi): array
    {
        $base = config('poi_taxonomy.categories', []);
        if (! $poi) {
            return $base;
        }

        foreach ($poi->categoryList() as $c) {
            if (! in_array($c, $base, true)) {
                $base[] = $c;
            }
        }

        return $base;
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedPoi(Request $request, ?Poi $poi): array
    {
        $allowedCats = $this->categoryOptionsForPoi($poi);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'categories' => ['required', 'array', 'min:1'],
            'categories.*' => ['required', 'string', Rule::in($allowedCats)],
            'town_id' => ['required', 'exists:towns,id'],
            'state' => ['required', 'string', Rule::in(AustraliaGeography::states())],
            'status' => ['required', 'in:published,draft,pending'],
            'verification_status' => ['required', Rule::enum(PoiVerificationStatus::class)],
            'verified_at' => ['nullable', 'date'],
            'detour_km' => ['nullable', 'numeric', 'min:0'],
            'short_description' => ['nullable', 'string', 'max:180'],
        ]);

        $validated['state'] = AustraliaGeography::normalizeStateInput($validated['state']);
        $validated['categories'] = array_values(array_unique($validated['categories']));

        if (($validated['verification_status'] ?? '') === PoiVerificationStatus::NotVerified->value) {
            $validated['verified_at'] = null;
        } elseif (empty($validated['verified_at'])) {
            $validated['verified_at'] = null;
        }

        return $validated;
    }
}
