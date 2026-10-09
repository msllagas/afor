<?php

use App\Events\UserWorkspacesChanged;
use App\Events\WorkspaceChanged;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceService;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    Storage::fake('public');

    $this->owner = User::factory()->create();
    $this->member = User::factory()->create();
    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach($this->member->id);

    $this->membersOwnWorkspace = Workspace::factory()->forUser($this->member)->create();
    $this->strangersWorkspace = Workspace::factory()->forUser()->create();

    Event::fake([WorkspaceChanged::class, UserWorkspacesChanged::class]);
});

/**
 * The workspaces that were told something changed, so a test can name exactly which.
 *
 * @return array<int, string>
 */
function workspacesTold(): array
{
    return Event::dispatched(WorkspaceChanged::class)
        ->map(fn (array $arguments) => $arguments[0]->workspaceId)
        ->unique()
        ->values()
        ->all();
}

function acceptInvite(User $user, Workspace $workspace, ?string $token = null): TestResponse
{
    $token ??= Str::of(app(WorkspaceService::class)->generateInvitationLink($workspace, $workspace->owner))->afterLast('/')->value();

    return test()->actingAs($user)->post(route('workspace-invitations.accept', ['workspace' => $workspace, 'token' => $token]));
}

function changeHowMemberAppears(string $change): TestResponse
{
    $test = test();

    return match ($change) {
        'renaming themselves' => $test->actingAs($test->member)
            ->patch(route('profile.update'), ['name' => 'Angel G', 'email' => $test->member->email]),
        'uploading an avatar' => $test->actingAs($test->member)
            ->patch(route('profile.update-avatar'), ['avatar' => UploadedFile::fake()->image('avatar.jpg')]),
        'removing their avatar' => $test->actingAs($test->member)
            ->delete(route('profile.delete-avatar')),
    };
}

function changeWorkspace(string $change): TestResponse
{
    $test = test();

    return match ($change) {
        'renaming it' => $test->actingAs($test->owner)
            ->patch(route('workspaces.update', $test->workspace), ['name' => 'Camping club']),
        'describing it' => $test->actingAs($test->owner)
            ->patch(route('workspaces.update', $test->workspace), ['description' => 'Trips we plan together']),
        'giving it a logo' => $test->actingAs($test->owner)
            ->patch(route('workspaces.update', $test->workspace), ['logo' => UploadedFile::fake()->image('logo.png')]),
        'removing its logo' => $test->actingAs($test->owner)
            ->patch(route('workspaces.update', $test->workspace), ['remove_logo' => true]),
        'removing a member' => $test->actingAs($test->owner)
            ->delete(route('workspaces.members.user.destroy', [$test->workspace, $test->member])),
        'a member leaving' => $test->actingAs($test->member)
            ->delete(route('workspaces.leave', $test->workspace)),
        'deleting it' => $test->actingAs($test->owner)
            ->delete(route('workspaces.destroy', $test->workspace)),
    };
}

test('changing how you appear tells every workspace you are in', function (string $change) {
    changeHowMemberAppears($change)->assertSessionHasNoErrors();

    expect(workspacesTold())->toEqualCanonicalizing([$this->workspace->id, $this->membersOwnWorkspace->id]);
})->with([
    'renaming themselves',
    'uploading an avatar',
    'removing their avatar',
]);

test('changing only your email tells nobody, since nobody else sees it', function () {
    $this->actingAs($this->member)
        ->patch(route('profile.update'), ['name' => $this->member->name, 'email' => 'angel@example.com'])
        ->assertSessionHasNoErrors();

    Event::assertNotDispatched(WorkspaceChanged::class);
});

test('deleting an account tells every workspace it was in', function () {
    $this->actingAs($this->member)->delete(route('profile.destroy'), ['password' => 'password']);

    expect(workspacesTold())->toEqualCanonicalizing([$this->workspace->id, $this->membersOwnWorkspace->id]);
});

test('every change to a workspace tells the people in it', function (string $change) {
    changeWorkspace($change)->assertSessionHasNoErrors();

    expect(workspacesTold())->toBe([$this->workspace->id]);
})->with([
    'renaming it',
    'describing it',
    'giving it a logo',
    'removing its logo',
    'removing a member',
    'a member leaving',
    'deleting it',
]);

test('a member who tries to change the workspace tells nobody', function (string $method, string $route, array $data) {
    $this->actingAs($this->member)
        ->{$method}(route($route, $this->workspace), $data)
        ->assertForbidden();

    Event::assertNotDispatched(WorkspaceChanged::class);
})->with([
    'renaming it' => ['patchJson', 'workspaces.update', ['name' => 'Camping club']],
    'deleting it' => ['deleteJson', 'workspaces.destroy', []],
]);

test('joining through the invite link tells the workspace and the joiner', function () {
    $joiner = User::factory()->create();

    acceptInvite($joiner, $this->workspace)->assertRedirect();

    expect(workspacesTold())->toBe([$this->workspace->id]);
    Event::assertDispatched(UserWorkspacesChanged::class, fn (UserWorkspacesChanged $event) => $event->userId === $joiner->id);
    Event::assertDispatchedTimes(UserWorkspacesChanged::class, 1);
});

test('opening the invite link again as a member tells nobody', function () {
    acceptInvite($this->member, $this->workspace)->assertRedirect();

    Event::assertNotDispatched(WorkspaceChanged::class);
    Event::assertNotDispatched(UserWorkspacesChanged::class);
});

test('an invite link that is not valid tells nobody', function () {
    acceptInvite(User::factory()->create(), $this->workspace, 'not-a-token')->assertNotFound();

    Event::assertNotDispatched(WorkspaceChanged::class);
    Event::assertNotDispatched(UserWorkspacesChanged::class);
});

test('the events name their channel and carry nothing', function () {
    $workspaceChanged = new WorkspaceChanged($this->workspace);
    $userWorkspacesChanged = new UserWorkspacesChanged($this->member);

    expect($workspaceChanged->broadcastOn())->toEqual([new PrivateChannel("workspace.{$this->workspace->id}")])
        ->and($workspaceChanged->broadcastAs())->toBe('workspace.changed')
        ->and($workspaceChanged->broadcastWith())->toBe([])
        ->and($userWorkspacesChanged->broadcastOn())->toEqual([new PrivateChannel("App.Models.User.{$this->member->id}")])
        ->and($userWorkspacesChanged->broadcastAs())->toBe('workspaces.changed')
        ->and($userWorkspacesChanged->broadcastWith())->toBe([]);
});
