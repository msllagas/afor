<?php

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('workspace_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token')->unique()->index();

            $table->foreignIdFor(Workspace::class)
                ->index()
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignUuid('invited_by')
                ->comment('User who invited the workspace')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->unique(['workspace_id', 'token', 'invited_by'], 'unique_workspace_invitation');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workspace_invitations');
    }
};
