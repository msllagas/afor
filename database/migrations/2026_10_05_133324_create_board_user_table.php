<?php

use App\Models\Board;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('board_user', function (Blueprint $table) {
            $table->foreignIdFor(Board::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(User::class)
                ->comment('Workspace member who was added to the board. The workspace owner is never listed here.')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamps();

            $table->primary(['board_id', 'user_id']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_user');
    }
};
