<?php echo '<?php'; ?>

<?php if( $namespace = $reflection->getNamespaceName() ) : ?>
namespace <?php echo $namespace; ?>;
<?php endif ?>

use LazyRecord\Schema;

class <?php echo $schema['model_class'] ?>SchemaProxy extends Schema <?php  ?>
{

	public function __construct()
	{
		$this->columns = <?php var_export($schema['columns']); ?>;
		$this->columnNames = <?php var_export($schema['column_names']); ?>;
		$this->primaryKey =  <?php var_export($schema['primary_key']); ?>;
		$this->table = <?php var_export($schema['table']); ?>;
		$this->modelClass = <?php var_export($schema['model_class']); ?>;
	}

}
