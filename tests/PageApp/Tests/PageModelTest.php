<?php
use LazyRecord\Testing\ModelTestCase;
use PageApp\Model\Page;
use PageApp\Model\PageCollection;

class PageModelTest extends ModelTestCase
{

    public function getModels()
    {
        return [new \PageApp\Model\PageSchema];
    }


    public function testCreate() {
        $page = new Page;
        $ret = $page->create([ 'title' => 'Book I' ,'brief' => 'Root reivision' ]);
        $this->assertResultSuccess($ret);

        $ret = $page->delete();
        $this->assertResultSuccess($ret);
    }


    public function testSaveRevision() {
        $page = new Page;
        $ret = $page->create([ 'title' => 'Book I' ,'brief' => 'Root reivision' ]);
        $this->assertResultSuccess($ret);

        $pageRev1 = $page->saveWithRevision();
        $this->assertNotEquals($pageRev1->id, $page->id);
        $this->assertGreaterThan($page->id, $pageRev1->id);

        $ret = $pageRev1->delete();
        $this->assertResultSuccess($ret);

        $ret = $page->delete();
        $this->assertResultSuccess($ret);
    }



    public function testRevisionRelationship() {
        $page = new Page;
        $ret = $page->create([ 'title' => 'Book I' ,'brief' => 'Root reivision' ]);
        $this->assertResultSuccess($ret);

        $pageRev1 = $page->saveWithRevision();
        $this->assertNotEquals($pageRev1->id, $page->id);
        $this->assertGreaterThan($page->id, $pageRev1->id);

        $this->assertNotNull($pageRev1->root_revision);
        $this->assertEquals($page->id, $pageRev1->root_revision->id);

        $this->assertNotNull($pageRev1->parent_revision);
        $this->assertNull($pageRev1->parent_revision->parent_revision);
        $this->assertEquals($page->id, $pageRev1->parent_revision->id);

        $ret = $pageRev1->delete();
        $this->assertResultSuccess($ret);

        $ret = $page->delete();
        $this->assertResultSuccess($ret);
    }



}

