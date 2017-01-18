UPGRADE TO 4.0.x
======================

1. data inflating will only happen when using accessor method.

```
$book->is_published; // will return the raw value from database.
$book->isPublished() // will return Boolean value (true or false)
```

2. boolean columns will generate `isXXX` accessors.

```
"published" => "isPublished()"
"is_published" => "isPublished()"
```

3. `BaseModel->find` now returns record object instead of Result object.

```
$foundBook = $book->find(321);
```

4. Replace `create` with `createAndLoad`:

```php
$author = new Author;
$author = $author->createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
```

UPGRADE TO 2.0
======================

- `setData()`, `getData()` were renamed to `setStashedData()`, `getStashedData()`
- `column::notNull` changed to `notNull()`
- Renamed `Relationship::order()` to `Relationship::orderBy()`

WIP
======================

- Rebuild `SQLBuilder\Universal\SelectQuery` from Relationship with the `filter` and `where`.
- Add a test case for relationship with custom where conditions, filter and order by and group by.
