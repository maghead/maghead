<?php
namespace LazyRecord\Schema;
use CLIFramework\Logger;

class SchemaUtils
{
    static public function print_schema_classes(Logger $logger, array $classes) {
        $logger->info('Found schema classes:');
        foreach( $classes as $class ) {
            $logger->info($logger->formatter->format($class, 'green') , 1);
        }
    }
}



