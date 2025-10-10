<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('board screen can be rendered', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get(route('boards.index'));

    $response->assertStatus(200);
});
