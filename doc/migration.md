Migration
==========

    use LazyRecord\Migration\BaseMigration;
    class YourMigration extends BaseMigration
    {
        function up() {

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

        function down()
        {

        }
    }

