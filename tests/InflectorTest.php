<?php
use Maghead\Inflector;

class InflectorTest extends PHPUnit_Framework_TestCase
{
    public function testSingularize()
    {
        $inflector = new Inflector;
        $this->assertEquals('post', $inflector->singularize('posts'));
    }

    public function testTablelize()
    {
        $inflector = new Inflector;
        $this->assertEquals('user_roles', $inflector->tableize('UserRole'));
    }

    public function testPluralize()
    {
        $inflector = new Inflector;
        $this->assertEquals('posts', $inflector->pluralize('post'));
        $this->assertEquals('blogs', $inflector->pluralize('blog'));
        $this->assertEquals('categories', $inflector->pluralize('category'));

        $this->assertEquals('CreateUser', $inflector->camelize('create_user'));
        $this->assertEquals('create_user', $inflector->underscore('CreateUser'));
        $this->assertEquals('Create User', $inflector->humanize('create_user'));

        $this->assertEquals('UserRole', $inflector->classify('user_roles'));
    }
}
