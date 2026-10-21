<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Usage Limits
    |--------------------------------------------------------------------------
    |
    | Caps that keep storage and load in check while staying generous. Lists
    | and cards are deliberately unlimited. Archived boards count toward the
    | board cap; deleting them frees a slot.
    |
    */

    'boards_per_workspace' => (int) env('LIMIT_BOARDS_PER_WORKSPACE', 100),

    // The owner is not counted.
    'members_per_workspace' => (int) env('LIMIT_MEMBERS_PER_WORKSPACE', 50),

    // Workspaces a user can join, not counting the one they own.
    'joined_workspaces_per_user' => (int) env('LIMIT_JOINED_WORKSPACES_PER_USER', 50),

    // Stored HTML, so the visible text is shorter than this.
    'card_description_length' => (int) env('LIMIT_CARD_DESCRIPTION_LENGTH', 32768),

    // Changes a user can make per minute.
    'writes_per_minute' => (int) env('LIMIT_WRITES_PER_MINUTE', 120),

];
