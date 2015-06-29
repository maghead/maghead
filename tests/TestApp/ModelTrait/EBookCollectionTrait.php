<?php
namespace TestApp\ModelTrait;

trait EBookCollectionTrait
{
    public function getCollectionLinks() {
        return array('link1', 'link2');
    }

    public function getCollectionStores() {
        return array('store1','store2');
    }
}


