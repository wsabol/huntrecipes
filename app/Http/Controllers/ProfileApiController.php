<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\UserResource;
use App\Models\Recipe;
use App\Services\ProfilePhotoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileApiController extends Controller {
    public function show(Request $request): UserResource {
        return new UserResource($request->user());
    }

    public function update(UpdateProfileRequest $request): UserResource {
        $this->authorize('update', $request->user());
        $request->user()->update($request->validated());

        return new UserResource($request->user()->refresh());
    }

    public function favorites(Request $request) {
        return RecipeResource::collection($request->user()->favoriteRecipes()->with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')->paginate(24));
    }

    public function recipes(Request $request) {
        abort_unless($request->user()->is_chef || $request->user()->is_developer, 403);

        return RecipeResource::collection(Recipe::with(['chef', 'course', 'cuisine', 'type'])->withCount('favoritedBy')->where('chef_id', $request->user()->chef_id)->orderBy('title')->get());
    }

    public function chef(Request $request) {
        abort_unless($request->user()->is_chef, 403);
        $this->authorize('update', $request->user()->chef);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'is_male' => ['required', 'boolean'], 'favorite_foods' => ['nullable', 'string', 'max:255'], 'wisdom' => ['nullable', 'string', 'max:255'], 'story' => ['nullable', 'string', 'max:2000']]);
        $request->user()->chef->update($data);

        return response()->json(['data' => $request->user()->chef->refresh()]);
    }

    public function photo(Request $request, ProfilePhotoService $photos): UserResource {
        $this->authorize('uploadPhoto', $request->user());
        $request->validate(['profile_photo' => ['required', 'image', 'max:4096']]);
        [$path, $largePath] = $photos->store($request->file('profile_photo'));
        $old = $request->user()->profile_picture;
        $oldLarge = $request->user()->profile_picture_lg;
        try {
            $request->user()->update(['profile_picture' => '/storage/' . $path]);
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete([$path, $largePath]);
            throw $exception;
        }
        if (str_starts_with((string)$old, '/storage/users/')) {
            Storage::disk('public')->delete(str_replace('/storage/', '', $old));
            Storage::disk('public')->delete(str_replace('/storage/', '', $oldLarge));
        }

        return new UserResource($request->user()->refresh());
    }
}
