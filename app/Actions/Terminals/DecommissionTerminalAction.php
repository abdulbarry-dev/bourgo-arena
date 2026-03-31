<?php

namespace App\Actions\Terminals;

use App\Models\HikvisionTerminal;

class DecommissionTerminalAction
{
    public function execute(HikvisionTerminal $terminal): void
    {
        $terminal->decommission();
    }
}
