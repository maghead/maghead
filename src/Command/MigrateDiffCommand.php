<?php

namespace Maghead\Command;

class MigrateDiffCommand extends BaseCommand
{
    public function brief()
    {
        return 'Generate a new migration script from diff';
    }

    public function aliases()
    {
        return array('d', 'di');
    }
}
