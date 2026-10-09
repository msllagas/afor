<?php

namespace App\Events;

use App\Models\Board;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class BoardChanged implements ShouldBroadcastNow, ShouldDispatchAfterCommit, ShouldRescue
{
    use Dispatchable, InteractsWithSockets;

    public readonly string $boardId;

    public readonly string $workspaceId;

    public function __construct(Board $board)
    {
        $this->boardId = $board->id;
        $this->workspaceId = $board->workspace_id;
        $this->dontBroadcastToCurrentUser();
    }

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("board.{$this->boardId}"),
            new PrivateChannel("workspace.{$this->workspaceId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'board.changed';
    }

    /**
     * @return array<string, never>
     */
    public function broadcastWith(): array
    {
        return [];
    }
}
