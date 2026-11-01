<?php

namespace Tests\Feature;

use App\Models\Chef;
use App\Models\ChefApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class ChefApplicationFlowTest extends TestCase {
    use RefreshDatabase;

    protected function setUp(): void {
        parent::setUp();
        Mail::fake();
    }

    private function application(): ChefApplication {
        return ChefApplication::create([
            'user_id' => User::factory()->create()->id,
            'relationship' => 'family',
            'story' => 'My family story.',
        ])->refresh();
    }

    private function admin(): User {
        return User::factory()->create(['is_developer' => true]);
    }

    public function testApprovalCanCreateANewChefWithZeroId(): void {
        $application = $this->application();

        $this->actingAs($this->admin())->patchJson("/api/v1/chef-applications/{$application->id}", [
            'approved' => true, 'chef_id' => 0,
        ])->assertOk()->assertJsonPath('data.chef_application_status_id', ChefApplication::STATUS_APPROVED);

        $user = $application->user->refresh();
        $this->assertTrue($user->is_chef);
        $this->assertSame($application->id, $user->chef_application_id);
        $this->assertSame($application->story, $user->chef->story);
        $this->assertDatabaseCount('Chef', 1);
        Mail::assertSentCount(1);
    }

    public function testDenialAcceptsTheFormsStringZeroId(): void {
        $application = $this->application();

        $this->actingAs($this->admin())->patchJson("/api/v1/chef-applications/{$application->id}", [
            'approved' => false, 'chef_id' => '0',
        ])->assertOk()->assertJsonPath('data.chef_application_status_id', ChefApplication::STATUS_DENIED);

        $this->assertFalse($application->user->refresh()->is_chef);
        $this->assertDatabaseCount('Chef', 0);
        Mail::assertSentCount(1);
    }

    public function testApprovalStillRejectsANonexistentChef(): void {
        $application = $this->application();

        $this->actingAs($this->admin())->patchJson("/api/v1/chef-applications/{$application->id}", [
            'approved' => true, 'chef_id' => 99999,
        ])->assertUnprocessable()->assertJsonValidationErrors('chef_id');

        $this->assertSame(ChefApplication::STATUS_PENDING, $application->refresh()->chef_application_status_id);
        Mail::assertNothingSent();
    }

    public function testApprovalCanLinkAnExistingChef(): void {
        $application = $this->application();
        $chef = Chef::create(['name' => 'Family Chef']);

        $this->actingAs($this->admin())->patchJson("/api/v1/chef-applications/{$application->id}", [
            'approved' => true, 'chef_id' => $chef->id,
        ])->assertOk();

        $this->assertSame($chef->id, $application->user->refresh()->chef_id);
        $this->assertDatabaseCount('Chef', 1);
    }

    public function testWithdrawalAllowsReapplicationAndShowsTheNewApplication(): void {
        $application = $this->application();
        $this->actingAs($application->user);

        $this->deleteJson("/api/v1/chef-applications/{$application->id}")->assertNoContent();
        $this->get('/account?goto=chef')->assertOk()->assertSee('Become a Chef')->assertDontSee('Your application status is pending');

        $response = $this->postJson('/api/v1/chef-applications', [
            'already_exists' => false, 'relationship' => 'family', 'story' => 'My replacement application.',
        ])->assertCreated();

        $this->assertNotSame($application->id, $response->json('data.id'));
        $this->get('/account?goto=chef')->assertOk()->assertSee('My replacement application.')->assertDontSee('My family story.');
    }

    public function testWithdrawnApplicationsCannotBeReviewed(): void {
        $application = $this->application();
        $this->actingAs($application->user)->deleteJson("/api/v1/chef-applications/{$application->id}")->assertNoContent();

        $this->actingAs($this->admin());
        foreach ([true, false] as $approved) {
            $this->patchJson("/api/v1/chef-applications/{$application->id}", [
                'approved' => $approved, 'chef_id' => 0,
            ])->assertConflict();
        }

        $this->assertFalse($application->user->refresh()->is_chef);
        $this->assertDatabaseCount('Chef', 0);
        Mail::assertNothingSent();
    }

    public function testApprovedApplicationCannotBeApprovedDeniedOrWithdrawnAgain(): void {
        $application = $this->application();
        $this->actingAs($this->admin());
        $this->patchJson("/api/v1/chef-applications/{$application->id}", ['approved' => true])->assertOk();
        $chefId = $application->user->refresh()->chef_id;

        foreach ([true, false] as $approved) {
            $this->patchJson("/api/v1/chef-applications/{$application->id}", ['approved' => $approved])->assertConflict();
        }
        $this->actingAs($application->user)->deleteJson("/api/v1/chef-applications/{$application->id}")->assertConflict();

        $this->assertSame(ChefApplication::STATUS_APPROVED, $application->refresh()->chef_application_status_id);
        $this->assertFalse($application->is_deleted);
        $this->assertSame($chefId, $application->user->refresh()->chef_id);
        $this->assertTrue($application->user->is_chef);
        $this->assertDatabaseCount('Chef', 1);
        Mail::assertSentCount(1);
    }

    public function testDeniedApplicationCannotBeReviewedAgain(): void {
        $application = $this->application();
        $this->actingAs($this->admin());
        $this->patchJson("/api/v1/chef-applications/{$application->id}", ['approved' => false])->assertOk();

        foreach ([true, false] as $approved) {
            $this->patchJson("/api/v1/chef-applications/{$application->id}", ['approved' => $approved])->assertConflict();
        }

        $this->assertSame(ChefApplication::STATUS_DENIED, $application->refresh()->chef_application_status_id);
        $this->assertFalse($application->user->refresh()->is_chef);
        $this->assertDatabaseCount('Chef', 0);
        Mail::assertSentCount(1);
    }
}
