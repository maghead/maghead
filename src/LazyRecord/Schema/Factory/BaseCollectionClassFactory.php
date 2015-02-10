<?php
namespace LazyRecord\Schema\Factory;
use ClassTemplate\ClassTemplate;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\SchemaDeclare;

class BaseCollectionClassFactory
{
    public static function create(SchemaDeclare $schema, $baseCollectionClass)
    {
        $cTemplate = new ClassTemplate($schema->getBaseCollectionClass() , array(
            // 'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->addConsts(array(
            'schema_proxy_class' => $schema->getSchemaProxyClass(),
            'model_class'        => $schema->getModelClass(),
            'table'              => $schema->getTable(),
            'read_source_id'     => $schema->getReadSourceId(),
            'write_source_id'     => $schema->getWriteSourceId(),
        ));

        if ($traitClasses = $schema->getCollectionTraitClasses()) {
            foreach($traitClasses as $traitClass) {
                $cTemplate->useTrait($traitClass);
            }
        }

        $cTemplate->extendClass( '\\' . $baseCollectionClass );
        return $cTemplate;
    }
}

