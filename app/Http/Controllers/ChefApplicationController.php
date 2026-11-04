<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewChefApplicationRequest;
use App\Models\Chef;
use App\Models\ChefApplication;
use App\Services\ChefApplicationMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChefApplicationController extends Controller {
    public function index(Request $request) {
        $this->authorize('viewAny', ChefApplication::class);
        $query = ChefApplication::with('user')->where('is_deleted', false);
        $query->when($request->integer('chef_application_status_id'), fn ($q, $value) => $q->where('chef_application_status_id', $value));
        $query->when($request->integer('user_id'), fn ($q, $value) => $q->where('user_id', $value));
        $query->when($request->date('date_from'), fn ($q, $value) => $q->whereDate('date_created', '>=', $value));
        $query->when($request->date('date_to'), fn ($q, $value) => $q->whereDate('date_created', '<=', $value));
        $data = $query->orderBy('id')->get()->map(fn (ChefApplication $application) => [
            ...$application->toArray(),
            'name' => $application->user->name,
            'email' => $application->user->email,
            'relation_name' => ChefApplication::relationshipName($application->relationship),
            'chef_application_status' => ChefApplication::statusName($application->chef_application_status_id),
        ]);

        return response()->json(['data' => $data]);
    }

    public function store(Request $request, ChefApplicationMailer $mailer) {
        $this->authorize('create', ChefApplication::class);
        $data = $request->validate(['already_exists' => ['required', 'boolean'], 'relationship' => ['required', 'string', 'max:50'], 'story' => ['required', 'string', 'max:2000']]);
        $app = ChefApplication::updateOrCreate(['user_id' => $request->user()->id, 'is_deleted' => false], $data + ['chef_application_status_id' => 1, 'date_created' => now()]);
        $mailer->sendSubmittedNotice($app);

        return response()->json(['data' => $app], 201);
    }

    public function destroy(Request $request, ChefApplication $chefApplication) {
        $this->authorize('delete', $chefApplication);
        DB::transaction(function () use ($chefApplication) {
            $chefApplication = ChefApplication::whereKey($chefApplication->id)->lockForUpdate()->firstOrFail();
            abort_if($chefApplication->is_deleted || (int)$chefApplication->chef_application_status_id !== ChefApplication::STATUS_PENDING, 409, 'This application is no longer pending.');
            $chefApplication->update(['is_deleted' => true]);
        });

        return response()->noContent();
    }

    public function review(ReviewChefApplicationRequest $request, ChefApplication $chefApplication, ChefApplicationMailer $mailer) {
        $this->authorize('review', $chefApplication);
        $application = DB::transaction(function () use ($request, $chefApplication) {
            $chefApplication = ChefApplication::whereKey($chefApplication->id)->lockForUpdate()->firstOrFail();
            abort_if($chefApplication->is_deleted || (int)$chefApplication->chef_application_status_id !== ChefApplication::STATUS_PENDING, 409, 'This application is no longer pending.');
            $chefApplication->load('user');
            if ($request->boolean('approved')) {
                $chefId = $request->integer('chef_id');
                $chef = $chefId ? Chef::findOrFail($chefId) : Chef::create(['name' => $chefApplication->user->name, 'is_male' => false, 'wisdom' => '', 'favorite_foods' => '', 'story' => $chefApplication->story]);
                abort_if($chef->user()->whereKeyNot($chefApplication->user_id)->exists(), 422, 'That chef profile is already linked to another user.');
                $chefApplication->user->update(['chef_id' => $chef->id, 'chef_application_id' => $chefApplication->id, 'is_chef' => true]);
                $chefApplication->update(['chef_application_status_id' => ChefApplication::STATUS_APPROVED]);
            } else {
                $chefApplication->update(['chef_application_status_id' => ChefApplication::STATUS_DENIED]);
            }

            return $chefApplication->refresh();
        });
        $mailer->sendDecision($application, $request->boolean('approved'));

        return response()->json(['data' => $application]);
    }

    public function reopen(ChefApplication $chefApplication) {
        $this->authorize('review', $chefApplication);
        $application = DB::transaction(function () use ($chefApplication) {
            $chefApplication = ChefApplication::whereKey($chefApplication->id)->lockForUpdate()->firstOrFail();
            abort_if($chefApplication->is_deleted, 409, 'This application was withdrawn and cannot be reopened.');
            abort_unless(in_array((int)$chefApplication->chef_application_status_id, [ChefApplication::STATUS_APPROVED, ChefApplication::STATUS_DENIED], true), 409, 'Only approved or denied applications can be reopened.');

            if ((int)$chefApplication->chef_application_status_id === ChefApplication::STATUS_APPROVED) {
                // Unlink the user from the chef profile but keep the profile and its recipes.
                $chefApplication->user()->update(['is_chef' => false, 'chef_id' => 0]);
            }
            $chefApplication->update(['chef_application_status_id' => ChefApplication::STATUS_PENDING]);

            return $chefApplication->refresh();
        });

        return response()->json(['data' => $application]);
    }
}
