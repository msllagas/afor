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
        Schema::create('board_lists', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->integer('order')->default(0);
            $table->string('color')->default('neutral');
            $table->foreignIdFor(Board::class)
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignIdFor(User::class, 'archived_by')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamp('archived_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['board_id', 'order']);
            $table->index('archived_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('board_lists');
    }
};
