<?php echo '<?php'; ?>

<?php if ($namespace) : ?>
namespace <?=$namespace?>;
<?php endif ?>

use LazyRecord\BaseModel;

class <?=$base_name?> extends BaseModel
{
	const schema_proxy_class = <?=var_export( $schema_proxy_class )?>;

}
