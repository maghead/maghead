<?php

namespace LazyRecord\Command;

use LazyRecord\Migration\MigrationRunner;

class MigrateBaseCommand extends BaseCommand
{
    public function options($opts)
    {
        parent::options($opts);
        $opts->add('script-dir', 'Migration script directory. (default: db/migrations)');
        $opts->add('b|backup', 'Backup database before running migration script.');
    }
}
