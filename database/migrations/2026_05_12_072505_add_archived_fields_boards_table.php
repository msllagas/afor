<?php

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
        Schema::table('boards', function (Blueprint $table) {
            $table->foreignIdFor(User::class, 'archived_by')
                ->after('workspace_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamp('archived_at')
                ->nullable()
                ->after('archived_by');

            $table->index(['workspace_id', 'archived_at', 'archived_by']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boards', function (Blueprint $table) {
            $table->dropIndex(['workspace_id', 'archived_at', 'archived_by']);
            $table->dropConstrainedForeignId('archived_by');
            $table->dropColumn('archived_at');
        });
    }
};
