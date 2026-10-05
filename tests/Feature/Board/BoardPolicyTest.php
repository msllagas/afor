<?php

use App\Models\Board;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Gate;

/**
 * Every actor that matters for board access. The former member left the workspace,
 * but their board row stayed behind, which must never be enough to reach the board.
 */
beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->boardMember = User::factory()->create();
    $this->workspaceMember = User::factory()->create();
    $this->formerMember = User::factory()->create();
    $this->outsider = User::factory()->create();

    $this->workspace = Workspace::factory()->forUser($this->owner)->create();
    $this->workspace->users()->attach([$this->boardMember->id, $this->workspaceMember->id]);
    $this->board = Board::factory()
        ->for($this->workspace)
        ->withMembers([$this->boardMember, $this->formerMember])
        ->create();
});

/**
 * What the gate decides for each actor: 'allowed', or the status code the refusal turns into.
 *
 * @param  array<int, string>  $actors
 * @return array<string, int|string>
 */
function boardGateDecisions(object $test, array $actors, string $ability, mixed $arguments): array
{
    return collect($actors)->mapWithKeys(function (string $actor) use ($test, $ability, $arguments) {
        $response = Gate::forUser($test->{$actor})->inspect($ability, $arguments);

        return [$actor => $response->allowed() ? 'allowed' : ($response->status() ?? 403)];
    })->all();
}

test('board abilities follow the access matrix', function (string $ability, array $expected) {
    $decisions = boardGateDecisions($this, array_keys($expected), $ability, $this->board);

    expect($decisions)->toBe($expected);
})->with([
    'view' => ['view', [
        'owner'           => 'allowed',
        'boardMember'     => 'allowed',
        'workspaceMember' => 404,
        'formerMember'    => 404,
        'outsider'        => 404,
    ]],
    'update' => ['update', [
        'owner'           => 'allowed',
        'boardMember'     => 'allowed',
        'workspaceMember' => 404,
        'formerMember'    => 404,
        'outsider'        => 404,
    ]],
    'delete' => ['delete', [
        'owner'           => 'allowed',
        'boardMember'     => 403,
        'workspaceMember' => 404,
        'formerMember'    => 404,
        'outsider'        => 404,
    ]],
    'manage members' => ['manageMembers', [
        'owner'           => 'allowed',
        'boardMember'     => 403,
        'workspaceMember' => 404,
        'formerMember'    => 404,
        'outsider'        => 404,
    ]],
    'leave' => ['leave', [
        'owner'           => 403,
        'boardMember'     => 'allowed',
        'workspaceMember' => 404,
        'formerMember'    => 404,
        'outsider'        => 404,
    ]],
]);

test('only the workspace owner can add boards to it', function () {
    $decisions = boardGateDecisions(
        $this,
        ['owner', 'boardMember', 'workspaceMember', 'formerMember', 'outsider'],
        'create',
        [Board::class, $this->workspace],
    );

    expect($decisions)->toBe([
        'owner'           => 'allowed',
        'boardMember'     => 403,
        'workspaceMember' => 403,
        'formerMember'    => 404,
        'outsider'        => 404,
    ]);
});
