<?php

namespace Maghead\Command;

class MigrateBaseCommand extends BaseCommand
{
    public function options($opts)
    {
        parent::options($opts);
        $opts->add('script-dir', 'Migration script directory. (default: db/migrations)')
            ->defaultValue('db/migrations')
            ;
        $opts->add('b|backup', 'Backup database before running migration script.');
    }
}
