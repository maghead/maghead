<?php echo '<?php'; ?>

<?php if( $namespace = $schema->getNamespace() ) : ?>
namespace <?php echo $namespace; ?>;
<?php endif ?>

use LazyRecord\Schema;

class <?php { $c = $schema->getModelClass(); $cs = explode('\\',$c); echo end($cs); } ?>SchemaProxy extends Schema <?php  ?>
{

	public function __construct()
	{
		$this->columns = <?php var_export($schema_data['columns']); ?>;
		$this->columnNames = <?php var_export($schema_data['column_names']); ?>;
		$this->primaryKey =  <?php var_export($schema_data['primary_key']); ?>;
		$this->table = <?php var_export($schema_data['table']); ?>;
		$this->modelClass = <?php var_export($schema_data['model_class']); ?>;
	}

}
