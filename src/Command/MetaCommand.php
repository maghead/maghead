<?php

namespace Maghead\Command;

use Maghead\Manager\MetadataManager;
use Maghead\Manager\DataSourceManager;

class MetaCommand extends BaseCommand
{
    public function brief()
    {
        return 'Set, get or list meta.';
    }

    public function usage()
    {
        return
              "\tmaghead meta [node]\n"
            ."\tmaghead meta [node] [key] [value]\n"
            ."\tmaghead meta [node] [key]\n";
    }

    public function execute($nodeId = 'master')
    {
        $conn = $this->dataSourceManager->getConnection($nodeId);
        $queryDriver = $conn->getQueryDriver();

        $args = func_get_args();
        array_shift($args);

        if (empty($args)) {
            $meta = new MetadataManager($conn, $queryDriver);
            printf("%26s | %-20s\n", 'Key', 'Value');
            printf("%s\n", str_repeat('=', 50));
            foreach ($meta as $key => $value) {
                printf("%26s   %-20s\n", $key, $value);
            }
        } elseif (count($args) == 1) {
            $key = $args[0];
            $meta = new MetadataManager($conn, $queryDriver);
            $value = $meta[$key];
            $this->logger->info("$key = $value");
        } elseif (count($args) == 2) {
            list($key, $value) = $args;
            $this->logger->info("Setting meta $key to $value.");
            $meta = new MetadataManager($conn, $queryDriver);
            $meta[$key] = $value;
        }
    }
}
