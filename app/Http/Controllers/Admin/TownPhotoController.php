<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Town;
use App\Models\TownPhoto;
use App\Services\TownPhotoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TownPhotoController extends Controller
{
    public function update(Request $request, Town $town, TownPhoto $photo): RedirectResponse
    {
        abort_unless($photo->town_id === $town->id, 404);

        $validator = Validator::make($request->all(), [
            'caption' => ['nullable', 'string', 'max:2000'],
            'source' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('admin.towns.edit', $town)
                ->withErrors($validator)
                ->withInput($request->only('caption', 'source'))
                ->with('photo_edit_id', $photo->id);
        }

        $photo->update($validator->validated());

        return redirect()->route('admin.towns.edit', $town)->with('status', 'Photo details saved.');
    }

    public function destroy(Town $town, TownPhoto $photo, TownPhotoService $photos): RedirectResponse
    {
        $photos->deletePhoto($town, $photo);

        return redirect()->route('admin.towns.edit', $town)->with('status', 'Photo removed.');
    }

    public function primary(Town $town, TownPhoto $photo, TownPhotoService $photos): RedirectResponse
    {
        $photos->setPrimary($town, $photo);

        return redirect()->route('admin.towns.edit', $town)->with('status', 'Main image updated.');
    }
}
