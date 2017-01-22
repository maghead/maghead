<?php

class InflectorTest extends PHPUnit_Framework_TestCase
{
    public function testBasicInflector()
    {
        $inflector = \Maghead\Inflector::getInstance();
        is( 'posts' , $inflector->pluralize('post') );
        is( 'blogs' , $inflector->pluralize('blog') );
        is( 'categories' , $inflector->pluralize('category') );

        is( 'post' , $inflector->singularize('posts') );
        is( 'CreateUser', $inflector->camelize('create_user'));
        is( 'create_user', $inflector->underscore('CreateUser'));
        is( 'Create User', $inflector->humanize('create_user'));

        is( 'user_roles', $inflector->tableize('UserRole'));
        is( 'UserRole', $inflector->classify('user_roles'));
    }
}

