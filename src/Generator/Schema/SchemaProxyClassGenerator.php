<?php

namespace Maghead\Generator\Schema;

use CodeGen\ClassFile;
use CodeGen\Generator\AppClassGenerator;
use CodeGen\UserClass;
use CodeGen\Block;
use CodeGen\Raw;
use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireComposerAutoloadStatement;
use CodeGen\Statement\RequireClassStatement;
use CodeGen\Statement\ReturnStatement;
use CodeGen\Statement\ConstStatement;
use CodeGen\Statement\DefineStatement;
use CodeGen\Statement\AssignStatement;
use CodeGen\Statement\Statement;
use CodeGen\Expr\NewObject;
use CodeGen\Expr\MethodCall;
use CodeGen\Expr\StaticMethodCall;
use CodeGen\Variable;
use CodeGen\Comment;
use CodeGen\CommentBlock;

use ReflectionObject;


use Maghead\Schema\DeclareSchema;
use SerializerKit\PhpSerializer;


function php_var_export($obj)
{
    $ser = new PhpSerializer();
    $ser->return = false;

    return $ser->encode($obj);
}

class SchemaProxyClassGenerator
{
    public static function create(DeclareSchema $schema)
    {
        $schemaClass = get_class($schema);
        $schemaArray = $schema->export();

        $cTemplate = new ClassFile($schema->getSchemaProxyClass());
        $cTemplate->extendClass('\\Maghead\\Schema\\RuntimeSchema');

        $cTemplate->addConst('SCHEMA_CLASS', get_class($schema));
        $cTemplate->addConst('LABEL', $schema->getLabel());
        $cTemplate->addConst('MODEL_NAME', $schema->getModelName());
        $cTemplate->addConst('MODEL_NAMESPACE', $schema->getNamespace());
        $cTemplate->addConst('MODEL_CLASS', $schema->getModelClass());
        $cTemplate->addConst('REPO_CLASS', $schema->getBaseRepoClass());
        $cTemplate->addConst('COLLECTION_CLASS', $schema->getCollectionClass());
        $cTemplate->addConst('TABLE', $schema->getTable());
        $cTemplate->addConst('PRIMARY_KEY', $schema->primaryKey);
        $cTemplate->addConst('GLOBAL_PRIMARY_KEY', $schema->findGlobalPrimaryKey());
        $cTemplate->addConst('LOCAL_PRIMARY_KEY', $schema->findLocalPrimaryKey());

        $cTemplate->useClass('\\Maghead\\Schema\\RuntimeColumn');
        $cTemplate->useClass('\\Maghead\\Schema\\Relationship\\Relationship');
        $cTemplate->useClass('\\Maghead\\Schema\\Relationship\\HasOne');
        $cTemplate->useClass('\\Maghead\\Schema\\Relationship\\HasMany');
        $cTemplate->useClass('\\Maghead\\Schema\\Relationship\\BelongsTo');
        $cTemplate->useClass('\\Maghead\\Schema\\Relationship\\ManyToMany');

        $cTemplate->addPublicProperty('columnNames', $schema->getColumnNames());
        $cTemplate->addPublicProperty('primaryKey', $schema->getPrimaryKey());
        $cTemplate->addPublicProperty('columnNamesIncludeVirtual', $schema->getColumnNames(true));
        $cTemplate->addPublicProperty('label', $schemaArray['label']);
        $cTemplate->addPublicProperty('readSourceId', $schemaArray['read_id']);
        $cTemplate->addPublicProperty('writeSourceId', $schemaArray['write_id']);
        $cTemplate->addPublicProperty('relations', array());

        $cTemplate->addStaticVar('column_hash', array_fill_keys($schema->getColumnNames(), 1));
        $cTemplate->addStaticVar('mixin_classes', array_reverse($schema->getMixinSchemaClasses()));

        $constructor = $cTemplate->addMethod('public', '__construct', []);
        if (!empty($schemaArray['relations'])) {

            /*
            foreach ($schemaArray['relations'] as $rid => $rel) {
                $refl = new ReflectionObject($rel);
                $constructor->block[] = new AssignStatement("\$this->relations['{$rid}']", new NewObject(
                    $refl->getShortName(), [
                        $rid, $rel['data'],
                    ]
                ));
            }
            */

            $constructor->block[] = '$this->relations = '.php_var_export($schemaArray['relations']).';';
        }

        foreach ($schemaArray['column_data'] as $columnName => $columnAttributes) {
            // $this->columns[ $column->name ] = new RuntimeColumn($column->name, $column->export());
            $constructor->block[] = '$this->columns[ '.var_export($columnName, true).' ] = new RuntimeColumn('
                .var_export($columnName, true).','
                .php_var_export($columnAttributes['attributes']).');';
        }


        /*
        // export column names including virutal columns
        // Aggregate basic translations from labels
        $msgIds = $schema->getMsgIds();
        $cTemplate->setMsgIds($msgIds);
        */
        return $cTemplate;
    }
}
