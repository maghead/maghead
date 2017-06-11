<?php

namespace PageApp\Model;

use PHPUnit\Framework\TestCase;

class PageSchemaTest extends TestCase
{
    public function testTrait()
    {
        $schema = new PageSchema;
        $code = $schema->classes->baseModel->render();
        $this->assertRegExp('/use RevisionModelTrait/', $code);
    }
}



