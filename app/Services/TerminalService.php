<?php

namespace App\Services;

use App\Models\HikvisionTerminal;

class TerminalService
{
    public function heartbeat(HikvisionTerminal $terminal): void
    {
        $terminal->markSeen();
    }

    public function assertAuthorizedForTerminal(?HikvisionTerminal $authenticatedTerminal, HikvisionTerminal $terminal): void
    {
        if ($authenticatedTerminal && $authenticatedTerminal->id !== $terminal->id) {
            abort(403, 'Unauthorized for this terminal');
        }
    }
}
