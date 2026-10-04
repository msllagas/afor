<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Route;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    Route::middleware('web')->get('/_test/abort/{status}', fn (int $status) => abort($status));
});

test('renders the app error page for each handled status', function (int $status) {
    config(['app.debug' => false]);

    $response = $this->get("/_test/abort/{$status}");

    $response->assertStatus($status)
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', $status)
        );
})->with([403, 404, 419, 429, 500, 503]);

test('renders the app error page for an unknown url', function () {
    $response = $this->get('/this-page-does-not-exist');

    $response->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
        );
});

test('includes the signed in user on the error page for pages they cannot reach', function () {
    $user = User::factory()->create();
    $board = Board::factory()->for(Workspace::factory()->forUser())->create();

    $response = $this->actingAs($user)->get(route('boards.show', $board));

    $response->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
            ->where('auth.user.id', $user->id)
        );
});

test('renders the error page for inertia visits that fail', function () {
    $member = User::factory()->create();
    $workspace = Workspace::factory()->forUser()->create();
    $workspace->users()->attach($member);

    $response = $this->actingAs($member)
        ->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
        ->delete(route('workspaces.destroy', $workspace));

    $response->assertForbidden()
        ->assertHeader('X-Inertia', 'true')
        ->assertJsonPath('component', 'ErrorPage')
        ->assertJsonPath('props.status', 403);
});

test('keeps json error responses for json requests', function () {
    config(['app.debug' => false]);

    $response = $this->getJson('/this-page-does-not-exist');

    $response->assertNotFound()
        ->assertHeaderMissing('X-Inertia')
        ->assertExactJsonStructure(['message']);
});

test('keeps laravel\'s debug page for server errors while debugging', function () {
    config(['app.debug' => true]);

    $response = $this->get('/_test/abort/500');

    $response->assertInternalServerError();
    expect($response->headers->has('X-Inertia'))->toBeFalse()
        ->and($response->getContent())->not->toContain('ErrorPage');
});

test('leaves statuses without an error page to laravel', function () {
    config(['app.debug' => false]);

    $response = $this->get('/_test/abort/418');

    $response->assertStatus(418);
    expect($response->getContent())->not->toContain('ErrorPage');
});

test('explains that an invite link no longer works when opening one that cannot be found', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $response = $this->get(route('workspace-invitations.show', [
        'workspace' => $workspace,
        'token'     => 'RESET-OR-MADE-UP',
    ]));

    $response->assertNotFound()
        ->assertInertia(fn (Assert $page) => $page
            ->component('ErrorPage')
            ->where('status', 404)
            ->where('reason', 'invitation')
        );
});

test('explains that an invite link no longer works when accepting one that cannot be found', function () {
    $workspace = Workspace::factory()->forUser()->create();

    $response = $this->actingAs(User::factory()->create())
        ->withHeaders(['X-Inertia' => 'true', 'X-Requested-With' => 'XMLHttpRequest'])
        ->post(route('workspace-invitations.accept', [
            'workspace' => $workspace,
            'token'     => 'RESET-OR-MADE-UP',
        ]));

    $response->assertNotFound()
        ->assertJsonPath('component', 'ErrorPage')
        ->assertJsonPath('props.reason', 'invitation');
});

test('does not blame invite links for other missing pages', function () {
    $response = $this->get('/this-page-does-not-exist');

    $response->assertInertia(fn (Assert $page) => $page
        ->component('ErrorPage')
        ->where('reason', null)
    );
});
