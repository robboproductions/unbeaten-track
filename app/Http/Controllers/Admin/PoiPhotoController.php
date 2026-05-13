<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poi;
use App\Models\PoiPhoto;
use App\Services\PoiPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PoiPhotoController extends Controller
{
    public function update(Request $request, Poi $poi, PoiPhoto $photo): RedirectResponse
    {
        abort_unless($photo->poi_id === $poi->id, 404);

        $validator = Validator::make($request->all(), [
            'caption' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.pois.edit', $poi)
                ->withErrors($validator)
                ->withInput($request->only('caption', 'source'))
                ->with('photo_edit_id', $photo->id);
        }

        $photo->update($validator->validated());

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'Photo details saved.');
    }

    public function destroy(Poi $poi, PoiPhoto $photo, PoiPhotoService $photos): RedirectResponse
    {
        $photos->deletePhoto($poi, $photo);

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'Photo removed.');
    }

    public function primary(Poi $poi, PoiPhoto $photo, PoiPhotoService $photos): RedirectResponse
    {
        $photos->setPrimary($poi, $photo);

        return redirect()->route('admin.pois.edit', $poi)->with('status', 'Main image updated.');
    }
}
