<?php

namespace Maghead\Schema;

use RuntimeException;
use Maghead\ConfigLoader;
use ClassTemplate\ClassFile;
use Maghead\Schema;
use Maghead\Schema\Factory\BaseModelClassFactory;
use Maghead\Schema\Factory\BaseRepoClassFactory;
use Maghead\Schema\Factory\BaseCollectionClassFactory;
use Maghead\Schema\Factory\CollectionClassFactory;
use Maghead\Schema\Factory\ModelClassFactory;
use Maghead\Schema\Factory\SchemaProxyClassFactory;

/**
 * Builder for building static schema class file.
 */
class SchemaGenerator
{
    protected $config;

    protected $forceUpdate = false;

    public function __construct(ConfigLoader $config)
    {
        $this->config = $config; // ConfigLoader::getInstance();
    }

    public function setForceUpdate($force = true)
    {
        $this->forceUpdate = $force;
    }

    protected function getBaseModelClass()
    {
        if ($this->config && $this->config->loaded) {
            return $this->config->getBaseModelClass();
        }

        return 'Maghead\BaseModel';
    }

    protected function getBaseCollectionClass()
    {
        if ($this->config && $this->config->loaded) {
            return $this->config->getBaseCollectionClass();
        }

        return 'Maghead\BaseCollection';
    }

    /**
     * This method checks the exising schema file and the generated class file mtime.
     * If the schema file is newer or the forceUpdate flag is specified, then 
     * the generated class files should be updated.
     *
     * @param ClassTemplate\ClassFile $cTemplate
     * @param DeclareSchema           $schema
     */
    protected function updateClassFile(ClassFile $cTemplate, DeclareSchema $schema, $canOverwrite = false)
    {
        // always update the proxy schema file
        $classFilePath = $schema->getRelatedClassPath($cTemplate->getShortClassName());

        // classes not Model/Collection class are overwriteable
        if (file_exists($classFilePath)) {
            if ($canOverwrite && ($schema->isNewerThanFile($classFilePath) || $this->forceUpdate)) {
                $this->writeClassTemplateToPath($cTemplate, $classFilePath);

                return [$cTemplate->getClassName(), $classFilePath];
            }
        } else {
            if ($this->writeClassTemplateToPath($cTemplate, $classFilePath)) {
                return [$cTemplate->getClassName(), $classFilePath];
            }
        }
    }

    /**
     * Generate modal class file, overwrite by default.
     *
     * @param Schema $schema
     * @param bool   $force  = true
     */
    public function generateModelClass(DeclareSchema $schema)
    {
        $cTemplate = ModelClassFactory::create($schema);

        return $this->updateClassFile($cTemplate, $schema, false); // do not overwrite
    }

    /**
     * Generate collection class from a schema object.
     *
     * @param DeclareSchema $schema
     *
     * @return array class name, class file path
     */
    public function generateCollectionClass(DeclareSchema $schema)
    {
        $cTemplate = CollectionClassFactory::create($schema);

        return $this->updateClassFile($cTemplate, $schema, false);
    }

    /**
     * Write class template to the schema directory.
     *
     * @param string $directory The schema class directory.
     * @param ClassTemplate\ClassFile class template object.
     * @param bool $overwrite Overwrite class file. 
     *
     * @return array
     */
    protected function writeClassTemplateToPath(ClassFile $cTemplate, $filepath)
    {
        if (false === file_put_contents($filepath, $cTemplate->render())) {
            throw RuntimeException("Can not write file $filepath");
        }

        return true;
    }

    public function generateSchemaFiles(SchemaInterface $schema)
    {
        $classMap = array();
        $cTemplates = array();

        // always update schema proxy and base classes
        $cTemplates[] = SchemaProxyClassFactory::create($schema);
        $cTemplates[] = BaseModelClassFactory::create($schema, $this->getBaseModelClass());
        $cTemplates[] = BaseRepoClassFactory::create($schema, 'Maghead\\BaseRepo');
        $cTemplates[] = BaseCollectionClassFactory::create($schema, $this->getBaseCollectionClass());
        foreach ($cTemplates as $cTemplate) {
            if ($result = $this->updateClassFile($cTemplate, $schema, true)) {
                list($className, $classFile) = $result;
                $classMap[ $className ] = $classFile;
            }
        }

        if ($result = $this->generateCollectionClass($schema)) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }
        if ($result = $this->generateModelClass($schema)) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }

        return $classMap;
    }

    /**
     * Given a schema class list, generate schema files.
     *
     * @param array $classes class list or schema object list.
     *
     * @return array class map array of schema class and file path.
     */
    public function generate(array $schemas)
    {
        // class map [ class => class file path ]
        $classMap = array();
        foreach ($schemas as $schema) {
            $generated = $this->generateSchemaFiles($schema);
            if (!empty($generated)) {
                $classMap = array_merge($classMap, $generated);
            }
        }

        return $classMap;
    }
}
