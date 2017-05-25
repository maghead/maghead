

To alter the meta classes of the declare schema, you may use the property
`classes` to get the related meta objects:

```php
$this->classes->model->useTrait('MyTrait');
$this->classes->model->addProperty('public', 'email');
$this->classes->model->addPublicProperty('email');
$this->classes->model->addProtectedProperty('email');

$this->classes->collection->addProtectedProperty('foo');

$this->classes->repo->addProtectedProperty('foo');
```

And, of course you can change the class name by calling the 'setClass' method:

```php
$this->classes->model->setClass('MyModelClassName');
```
