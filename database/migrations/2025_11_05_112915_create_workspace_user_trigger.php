<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::unprepared('
        CREATE TRIGGER prevent_owner_in_pivot BEFORE INSERT ON workspace_user
        FOR EACH ROW
        BEGIN
            IF NEW.user_id = (SELECT user_id FROM workspaces WHERE id = NEW.workspace_id) THEN
                SIGNAL SQLSTATE "45000" SET MESSAGE_TEXT = "Owner cannot be added to their own workspace pivot";
            END IF;
        END
    ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS prevent_owner_in_pivot');
    }
};
