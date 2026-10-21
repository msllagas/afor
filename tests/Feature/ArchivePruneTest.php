<?php

use App\Models\Board;
use App\Models\BoardList;
use App\Models\Card;

beforeEach(function () {
    config(['limits.archive_retention_days' => 30]);
});

test('archived boards are purged with their lists and cards once their restore window has passed', function () {
    $expired = Board::factory()->archived()->create(['archived_at' => now()->subDays(31)]);
    $expiredCard = Card::factory()->for(BoardList::factory()->for($expired))->create();
    $restorable = Board::factory()->archived()->create(['archived_at' => now()->subDays(29)]);
    $active = Board::factory()->create();

    $this->artisan('model:prune', ['--model' => [Board::class]])->assertSuccessful();

    expect(Board::withTrashed()->pluck('id')->all())->toEqualCanonicalizing([$restorable->id, $active->id])
        ->and(Card::withTrashed()->whereKey($expiredCard->id)->exists())->toBeFalse();
});

test('archived lists are purged with their cards once their restore window has passed', function () {
    $expired = BoardList::factory()->archived()->create(['archived_at' => now()->subDays(31)]);
    $expiredCard = Card::factory()->for($expired)->create();
    $restorable = BoardList::factory()->archived()->create(['archived_at' => now()->subDays(29)]);
    $active = BoardList::factory()->create();

    $this->artisan('model:prune', ['--model' => [BoardList::class]])->assertSuccessful();

    expect(BoardList::withTrashed()->pluck('id')->all())->toEqualCanonicalizing([$restorable->id, $active->id])
        ->and(Card::withTrashed()->whereKey($expiredCard->id)->exists())->toBeFalse();
});

test('archived cards are purged once their restore window has passed', function () {
    $expired = Card::factory()->create(['deleted_at' => now()->subDays(31)]);
    $restorable = Card::factory()->create(['deleted_at' => now()->subDays(29)]);
    $active = Card::factory()->create();

    $this->artisan('model:prune', ['--model' => [Card::class]])->assertSuccessful();

    expect(Card::withTrashed()->pluck('id')->all())
        ->toEqualCanonicalizing([$restorable->id, $active->id])
        ->not->toContain($expired->id);
});
