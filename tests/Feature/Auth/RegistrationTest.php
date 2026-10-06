<?php

use App\Listeners\CreateDefaultWorkspace;
use App\Models\Card;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

use function Pest\Laravel\assertDatabaseHas;

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('registering a user automatically creates a default workspace with a board, board lists, and cards', function () {
    $response = $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();

    assertDatabaseHas('workspaces', [
        'name'     => 'Test User',
        'owner_id' => auth()->id(),
    ]);

    $user = User::where('email', 'test@example.com')->first();

    $workspace = $user->ownedWorkspaces()->first();

    assertDatabaseHas('boards', [
        'workspace_id' => $workspace->id,
        'name'         => 'Getting Started',
    ]);

    $board = $workspace->boards()->first();

    assertDatabaseHas('board_lists', [
        'board_id' => $board->id,
        'name'     => 'To Do',
    ]);

    assertDatabaseHas('board_lists', [
        'board_id' => $board->id,
        'name'     => 'In Progress',
    ]);

    assertDatabaseHas('board_lists', [
        'board_id' => $board->id,
        'name'     => 'Done',
    ]);

    $todoList = $board->boardLists()->where('name', 'To Do')->first();

    assertDatabaseHas('cards', [
        'board_list_id' => $todoList->id,
        'name'          => 'Welcome to your board 🎉',
    ]);
});

test('registration attempts are rate limited', function () {
    foreach (range(1, 6) as $attempt) {
        $this->post(route('register.store'), ['email' => 'not-an-email']);
    }

    $this->post(route('register.store'), [
        'name'                  => 'Test User',
        'email'                 => 'test@example.com',
        'password'              => 'password',
        'password_confirmation' => 'password',
    ])->assertTooManyRequests();

    $this->assertGuest();
});

test('a failed default workspace setup leaves no part of it behind', function () {
    $user = User::factory()->create();
    Card::creating(fn () => throw new RuntimeException('The card could not be saved.'));

    expect(fn () => (new CreateDefaultWorkspace)->handle(new Registered($user)))
        ->toThrow(RuntimeException::class)
        ->and($user->ownedWorkspaces()->exists())->toBeFalse();

    $this->assertDatabaseCount('boards', 0);
    $this->assertDatabaseCount('board_lists', 0);
});
