Migration
==========

## Command 

To generate a migration script: 

    lazy migrate --new CreateUser

To generate a migration script from schema diff:

    lazy migrate --diff UserChanges

To run migration scripts:

    lazy migrate

## Migration Class

    use Maghead\Migration\Migration;
    class YourMigration extends Migration
    {
        function upgrade() {

            // import schema from static schema class
            $this->importSchema(new AuthorSchema);

            // if the model provides schema method
            $this->importModel(new Wine);

            // to add a column
            $this->addColumn('table', $schema->getColumn('new_column') );

            $this->addColumnFromSchema( $schema , 'new_column' );

            // to remove a column
            $this->removeColumn('table','old_column');
        }

        function downgrade()
        {

        }
    }


## Migration Runner

Find migration classes and run them all.

    $runner = new MigrationRunner(array('default','master','slave') );
    $files = $runner->load('db/migrations');

    $migrationClasses = $runner->load();

    foreach( $this->dsIds as $dsId ) {
        foreach( $migrationClasses as $class ) {
            $migration = new $class( $dsId );
            $migration->upgrade();
        }
    }


