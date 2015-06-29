<?php
require_once('AuthorModelTest.php');

use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;

class AuthorModelMysqlTest extends AuthorModelTest {

    public $driver = 'mysql';

}
