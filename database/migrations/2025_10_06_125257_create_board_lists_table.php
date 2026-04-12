<?php

use App\Models\Board;
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

            $table->boolean('is_archived')->default(false);

            $table->timestamps();
            $table->softDeletes();
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
